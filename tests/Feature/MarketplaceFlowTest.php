<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\UserRole;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\ProviderSlotReservation;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MarketplaceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-09-23 08:00:00', 'Asia/Jakarta'));
    }

    public function test_home_and_registration_and_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Jasa andalan, untuk setiap kebutuhan.');

        $this->post('/daftar', [
            'name' => 'Raka',
            'email' => 'bukan-email',
            'phone' => '08123456789',
            'password' => 'rahasia12',
            'password_confirmation' => 'rahasia12',
        ])->assertSessionHasErrors('email');

        $this->post('/daftar', [
            'name' => 'Raka',
            'email' => 'raka@example.com',
            'phone' => '08123456789',
            'password' => 'rahasia12',
            'password_confirmation' => 'rahasia12',
        ])->assertRedirect('/');

        $this->post('/keluar')->assertRedirect('/');
        $this->post('/masuk', ['email' => 'raka@example.com', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/masuk', ['email' => 'raka@example.com', 'password' => 'rahasia12'])->assertRedirect('/');
    }

    public function test_search_and_location_change_order(): void
    {
        $near = $this->makeService('Dekat', -6.2615, 106.8106, 'kebersihan');
        $far = $this->makeService('Jauh', -7.2575, 112.7521, 'otomotif');

        $this->post('/lokasi', ['source' => 'manual', 'city' => 'Jakarta Selatan'])->assertRedirect();
        $this->get('/cari-jasa?sort=distance')
            ->assertOk()
            ->assertSeeInOrder(['Dekat', 'Jauh']);

        $this->get('/cari-jasa?category='.$near->category->slug)->assertOk()->assertSee('Dekat')->assertDontSee('Jauh');
        $this->get('/cari-jasa', ['X-Andallo-Partial' => '1'])->assertOk()->assertSee('Dekat');

        $this->post('/lokasi', ['source' => 'clear'])->assertRedirect();
        $this->get('/cari-jasa?sort=distance')->assertOk()->assertSee('koordinat');
        $this->assertNull($far->id > 0 ? session('location.lat') : null);
    }

    public function test_booking_can_be_completed_and_rated(): void
    {
        [$service, $providerUser] = $this->pair();
        $customer = $this->customer();
        $admin = $this->admin();

        $this->actingAs($customer)->post(route('bookings.store', $service), $this->payload())->assertRedirect();
        $booking = Booking::query()->first();
        $this->assertSame(BookingStatus::PendingReview, $booking->status);
        $this->assertSame('100000.00', $booking->total);

        $this->actingAs($admin)->post(route('admin.bookings.forward', $booking))->assertRedirect();
        $booking->refresh();
        $this->assertSame(BookingStatus::AwaitingProvider, $booking->status);
        $this->assertDatabaseHas('notification_logs', ['booking_id' => $booking->id, 'status' => 'simulated']);

        $log = $booking->notificationLogs()->first();
        preg_match('#/tindakan/([A-Za-z0-9]+)#', $log->body, $matches);
        $this->get('/tindakan/'.$matches[1])->assertOk()->assertSee('tidak mengubah status');
        $booking->refresh();
        $this->assertSame(BookingStatus::AwaitingProvider, $booking->status);

        $this->actingAs($providerUser)->post(route('provider.orders.accept', $booking))->assertRedirect();
        $booking->refresh();
        $this->assertSame(BookingStatus::AwaitingPayment, $booking->status);
        $this->assertSame(1, Payment::query()->count());
        $this->actingAs($providerUser)->post(route('provider.orders.accept', $booking));
        $this->assertSame(1, Payment::query()->count());

        $this->actingAs($customer)->post(route('payments.upload', $booking), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();
        $this->assertSame(PaymentStatus::UnderReview, $booking->payment()->first()->status);

        $this->actingAs($admin)->post(route('admin.payments.reject', $booking->payment), [
            'reason' => 'Nominal tidak sesuai',
        ]);
        $this->assertSame(PaymentStatus::Rejected, $booking->payment()->first()->status);

        $this->actingAs($customer)->post(route('payments.upload', $booking), [
            'proof' => UploadedFile::fake()->image('bukti-ulang.jpg'),
        ]);
        $this->assertSame(PaymentStatus::UnderReview, $booking->payment()->first()->status);

        $this->actingAs($customer)->post(route('payments.upload', $booking), [
            'proof' => UploadedFile::fake()->create('bukti.txt', 20, 'text/plain'),
        ])->assertSessionHas('error');

        $this->actingAs($admin)->post(route('admin.payments.approve', $booking->payment), ['note' => 'Dana masuk']);
        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Paid, $booking->payment_status);

        $this->actingAs($providerUser)->post(route('provider.orders.progress', $booking), ['status' => 'on_the_way']);
        $this->actingAs($providerUser)->post(route('provider.orders.progress', $booking), ['status' => 'in_progress']);
        $this->actingAs($providerUser)->post(route('provider.orders.progress', $booking), ['status' => 'awaiting_customer']);
        $this->actingAs($customer)->post(route('bookings.complete', $booking));
        $this->actingAs($customer)->post(route('bookings.review', $booking), ['rating' => 5, 'comment' => 'Rapi']);
        $this->actingAs($customer)->post(route('bookings.review', $booking), ['rating' => 1, 'comment' => 'Kedua'])->assertSessionHas('error');

        $service->refresh();
        $this->assertSame(1, $service->rating_count);
        $this->assertEquals(5, (float) $service->rating_avg);
    }

    public function test_conflicting_schedule_is_rejected(): void
    {
        [$service] = $this->pair();
        $first = $this->customer('satu@example.com');
        $second = $this->customer('dua@example.com');
        $this->actingAs($first)->post(route('bookings.store', $service), $this->payload())->assertRedirect();
        $this->actingAs($second)->post(route('bookings.store', $service), $this->payload())->assertSessionHas('error');
        $this->assertSame(1, ProviderSlotReservation::query()->count());
    }

    public function test_provider_reject_and_expiry_release_slot(): void
    {
        [$service, $providerUser] = $this->pair();
        $customer = $this->customer();
        $admin = $this->admin();

        $this->actingAs($customer)->post(route('bookings.store', $service), $this->payload());
        $rejected = Booking::query()->first();
        $this->actingAs($admin)->post(route('admin.bookings.forward', $rejected));
        $this->actingAs($providerUser)->post(route('provider.orders.reject', $rejected), ['reason' => 'Penuh']);
        $this->assertSame(BookingStatus::Rejected, $rejected->refresh()->status);
        $this->assertSame(0, ProviderSlotReservation::query()->count());

        $this->actingAs($customer)->post(route('bookings.store', $service), $this->payload('09:00'));
        $expired = Booking::query()->latest('id')->first();
        $this->actingAs($admin)->post(route('admin.bookings.forward', $expired));
        Carbon::setTestNow(now()->addMinutes(3));
        Cache::flush();
        app(BookingService::class)->sweepExpired();
        $this->assertSame(BookingStatus::Expired, $expired->refresh()->status);
        $this->assertSame(0, ProviderSlotReservation::query()->count());
    }

    public function test_cancellation_before_and_after_payment(): void
    {
        [$service, $providerUser] = $this->pair();
        $customer = $this->customer();
        $admin = $this->admin();

        $this->actingAs($customer)->post(route('bookings.store', $service), $this->payload());
        $early = Booking::query()->first();
        $this->actingAs($customer)->post(route('bookings.cancel', $early), ['reason' => 'Berubah pikiran']);
        $this->assertSame(BookingStatus::Cancelled, $early->refresh()->status);

        $this->actingAs($customer)->post(route('bookings.store', $service), $this->payload('10:00'));
        $booking = Booking::query()->latest('id')->first();
        $this->actingAs($admin)->post(route('admin.bookings.forward', $booking));
        $this->actingAs($providerUser)->post(route('provider.orders.accept', $booking));
        $this->actingAs($customer)->post(route('payments.upload', $booking), ['proof' => UploadedFile::fake()->image('bukti.jpg')]);
        $this->actingAs($admin)->post(route('admin.payments.approve', $booking->payment()->first()), ['note' => 'OK']);
        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);

        $this->actingAs($customer)->post(route('bookings.cancel', $booking), ['reason' => 'Jadwal berubah']);
        $this->assertSame(BookingStatus::Confirmed, $booking->refresh()->status);
        $request = $booking->cancellationRequests()->first();
        $this->actingAs($admin)->post(route('admin.cancellations.resolve', $request), ['action' => 'approve', 'note' => 'Setuju']);
        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);
        $this->assertSame(PaymentStatus::RefundPending, $booking->payment_status);
        $refund = $booking->refunds()->first();
        $this->assertNotSame(RefundStatus::Success, $refund->status);

        $this->actingAs($admin)->post(route('admin.refunds.confirm', $refund), ['note' => 'Dana ditransfer kembali']);
        $this->assertSame(RefundStatus::Success, $refund->refresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $booking->payment()->first()->status);
    }

    public function test_other_customer_cannot_open_booking(): void
    {
        [$service] = $this->pair();
        $owner = $this->customer('pemilik@example.com');
        $other = $this->customer('lain@example.com');
        $this->actingAs($owner)->post(route('bookings.store', $service), $this->payload());
        $booking = Booking::query()->first();
        $this->actingAs($other)->get(route('bookings.show', $booking))->assertForbidden();
        $this->actingAs($other)->post(route('payments.upload', $booking), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertForbidden();
    }

    public function test_unapproved_provider_is_hidden(): void
    {
        [$service] = $this->pair();
        $service->provider->update(['verification_status' => 'draft']);
        $this->get('/cari-jasa?q=Jasa')->assertOk()->assertDontSee($service->name);
    }

    private function pair(): array
    {
        $service = $this->makeService('Jasa Uji', -6.26, 106.81, 'kebersihan');

        return [$service, $service->provider->user];
    }

    private function makeService(string $name, float $lat, float $lng, string $slug): Service
    {
        $user = User::factory()->create([
            'role' => UserRole::Provider,
            'phone' => '6281234567890',
        ]);
        $provider = Provider::query()->create([
            'user_id' => $user->id,
            'business_name' => 'Usaha '.$name,
            'whatsapp' => '6281234567890',
            'description' => 'Usaha uji',
            'address' => 'Jl. Uji',
            'city' => 'Jakarta Selatan',
            'latitude' => $lat,
            'longitude' => $lng,
            'service_area' => 'Jakarta Selatan',
            'verification_status' => 'approved',
            'is_active' => true,
        ]);
        $category = Category::query()->firstOrCreate(['slug' => $slug], [
            'name' => ucfirst($slug),
            'icon' => 'broom',
            'sort_order' => 1,
        ]);
        $service = Service::query()->create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.$provider->id,
            'description' => 'Deskripsi '.$name,
            'price' => 100000,
            'price_unit' => 'per kunjungan',
            'duration_minutes' => 60,
            'includes' => 'Pengerjaan standar',
            'excludes' => 'Material',
            'requires_visit' => true,
            'is_active' => true,
        ]);
        for ($day = 0; $day <= 6; $day++) {
            AvailabilitySlot::query()->create([
                'provider_id' => $provider->id,
                'weekday' => $day,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ]);
        }

        return $service->load('provider.user', 'category');
    }

    private function customer(string $email = 'pelanggan@example.com'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => UserRole::Customer,
            'phone' => '628111111111',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'phone' => '628100000000']);
    }

    private function payload(string $time = '08:00'): array
    {
        return [
            'scheduled_date' => '2026-09-24',
            'start_time' => $time,
            'city' => 'Jakarta Selatan',
            'address' => 'Jl. Mawar No. 1',
            'customer_notes' => 'Datang dari pintu samping',
            'agree_policy' => '1',
        ];
    }
}
