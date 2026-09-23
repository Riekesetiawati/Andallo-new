<?php

namespace App\Services;

use App\Models\ActionToken;
use App\Models\AppNotification;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MarketplaceNotifier
{
    public function notifyUser(User $user, string $type, string $title, string $body, array $data = []): void
    {
        AppNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function whatsapp(Booking $booking, ?User $user, string $phone, string $template, string $body): NotificationLog
    {
        $driver = config('andallo.whatsapp.driver', 'simulation');
        $log = new NotificationLog([
            'booking_id' => $booking->id,
            'user_id' => $user?->id,
            'channel' => $driver === 'cloud_api' ? 'whatsapp_cloud' : 'simulation',
            'recipient' => preg_replace('/\D+/', '', $phone),
            'template' => $template,
            'body' => $body,
            'attempts' => 1,
        ]);

        if ($driver === 'cloud_api') {
            $token = config('andallo.whatsapp.token');
            $phoneId = config('andallo.whatsapp.phone_number_id');
            if (! $token || ! $phoneId) {
                $log->status = 'failed';
                $log->error = 'Kredensial WhatsApp Business API belum diatur.';
                $log->save();

                return $log;
            }

            try {
                $version = config('andallo.whatsapp.api_version', 'v21.0');
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'to' => $log->recipient,
                        'type' => 'text',
                        'text' => ['preview_url' => false, 'body' => $body],
                    ]);

                if ($response->successful()) {
                    $log->status = 'sent';
                    $log->sent_at = now();
                } else {
                    $log->status = 'failed';
                    $log->error = 'WhatsApp API menolak pesan. Penyedia tidak dianggap bersalah. Coba kirim ulang dari log notifikasi.';
                }
            } catch (\Throwable $exception) {
                $log->status = 'failed';
                $log->error = 'Pengiriman gagal: '.$exception->getMessage();
            }

            $log->save();
            $booking->forceFill(['whatsapp_dispatch_status' => $log->status])->save();

            return $log;
        }

        $log->status = 'simulated';
        $log->error = 'Mode simulasi pengembangan. Pesan dicatat di Andallo dan belum terkirim ke WhatsApp.';
        $log->save();
        $booking->forceFill(['whatsapp_dispatch_status' => 'simulated'])->save();

        return $log;
    }

    public function retry(NotificationLog $log): NotificationLog
    {
        $booking = $log->booking;
        if (! $booking) {
            $log->status = 'failed';
            $log->error = 'Pesanan untuk log ini tidak ditemukan.';
            $log->save();

            return $log;
        }

        return $this->whatsapp(
            $booking,
            $log->user,
            (string) $log->recipient,
            $log->template,
            $log->body,
        );
    }

    public function providerActionUrl(Booking $booking): string
    {
        $plain = Str::random(48);
        ActionToken::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $booking->provider->user_id,
            'action' => 'provider_response',
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addHours(48),
        ]);

        return route('actions.show', $plain);
    }

    public function bookingSummary(Booking $booking, bool $includeCost = true): string
    {
        $lines = [
            'Andallo · Pesanan '.$booking->code,
            'Jasa: '.$booking->service_name_snapshot,
            'Jadwal: '.$booking->scheduleLabel(),
            'Kota: '.$booking->city,
        ];

        if ($includeCost) {
            $lines[] = 'Total: '.rupiah($booking->total).' ('.$booking->price_unit_snapshot.')';
        }

        return implode("\n", $lines);
    }
}
