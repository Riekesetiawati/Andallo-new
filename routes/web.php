<?php

use App\Http\Controllers\ActionTokenController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\OrderController;
use App\Http\Controllers\Provider\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/cari-jasa', [CatalogController::class, 'index'])->name('services.index');
Route::get('/jasa/{service}', [CatalogController::class, 'show'])->name('services.show');
Route::get('/jasa/{service}/slot', [CatalogController::class, 'slots'])->name('services.slots');
Route::get('/penyedia', [CatalogController::class, 'providers'])->name('providers.index');
Route::get('/penyedia/{provider}', [CatalogController::class, 'provider'])->name('providers.show');
Route::get('/bandingkan', [CompareController::class, 'index'])->name('compare.index');
Route::post('/bandingkan', [CompareController::class, 'toggle'])->name('compare.toggle');
Route::get('/bantuan', [HelpController::class, 'index'])->name('help');
Route::get('/tentang', [HelpController::class, 'about'])->name('about');
Route::post('/lokasi', [LocationController::class, 'update'])->name('location.update');
Route::get('/pembayaran/kembali', [PaymentController::class, 'gatewayReturn'])->name('payments.return');
Route::post('/webhook/pembayaran', [PaymentController::class, 'webhook'])->name('payments.webhook');

Route::get('/tindakan/{token}', [ActionTokenController::class, 'show'])->name('actions.show');
Route::post('/tindakan/{token}', [ActionTokenController::class, 'store'])->name('actions.store');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.store');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::get('/daftar/mitra', [AuthController::class, 'showProviderRegister'])->name('register.provider');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/pesanan', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/jasa/{service}/pesan', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/jasa/{service}/pesan', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/pesanan/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/pesanan/{booking}/batal', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/pesanan/{booking}/selesai', [BookingController::class, 'confirmComplete'])->name('bookings.complete');
    Route::post('/pesanan/{booking}/ulasan', [BookingController::class, 'review'])->name('bookings.review');
    Route::post('/pesanan/{booking}/penyelesaian', [BookingController::class, 'settlement'])->name('bookings.settlement');
    Route::post('/pesanan/{booking}/bukti', [PaymentController::class, 'upload'])->name('payments.upload');
    Route::get('/pembayaran/{payment}/bukti', [PaymentController::class, 'proof'])->name('payments.proof');
    Route::post('/pesanan/{booking}/komplain', [ComplaintController::class, 'store'])->name('complaints.store');
});

Route::middleware(['auth', 'role:provider'])->prefix('mitra')->name('provider.')->group(function () {
    Route::get('/', [WorkspaceController::class, 'dashboard'])->name('dashboard');
    Route::get('/profil', [WorkspaceController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profil', [WorkspaceController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profil/ajukan', [WorkspaceController::class, 'submitReview'])->name('profile.submit');
    Route::get('/layanan', [WorkspaceController::class, 'services'])->name('services.index');
    Route::post('/layanan', [WorkspaceController::class, 'storeService'])->name('services.store');
    Route::put('/layanan/{service}', [WorkspaceController::class, 'updateService'])->name('services.update');
    Route::delete('/layanan/{service}', [WorkspaceController::class, 'destroyService'])->name('services.destroy');
    Route::post('/portofolio', [WorkspaceController::class, 'storePortfolio'])->name('portfolios.store');
    Route::get('/jadwal', [WorkspaceController::class, 'schedule'])->name('schedule.edit');
    Route::put('/jadwal', [WorkspaceController::class, 'updateSchedule'])->name('schedule.update');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/pesanan/{booking}/terima', [OrderController::class, 'accept'])->name('orders.accept');
    Route::post('/pesanan/{booking}/tolak', [OrderController::class, 'reject'])->name('orders.reject');
    Route::post('/pesanan/{booking}/progres', [OrderController::class, 'progress'])->name('orders.progress');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/pengguna', [AdminDashboardController::class, 'users'])->name('users');
    Route::post('/pengguna/{user}/status', [AdminDashboardController::class, 'toggleUser'])->name('users.toggle');
    Route::get('/mitra', [AdminDashboardController::class, 'providers'])->name('providers');
    Route::post('/mitra/{provider}', [AdminDashboardController::class, 'verifyProvider'])->name('providers.verify');
    Route::get('/kategori', [AdminDashboardController::class, 'categories'])->name('categories');
    Route::post('/kategori', [AdminDashboardController::class, 'storeCategory'])->name('categories.store');
    Route::put('/kategori/{category}', [AdminDashboardController::class, 'updateCategory'])->name('categories.update');
    Route::get('/jasa', [AdminDashboardController::class, 'services'])->name('services');
    Route::post('/jasa/{service}/status', [AdminDashboardController::class, 'toggleService'])->name('services.toggle');
    Route::get('/pesanan', [AdminDashboardController::class, 'bookings'])->name('bookings');
    Route::post('/pesanan/{booking}/teruskan', [AdminDashboardController::class, 'forward'])->name('bookings.forward');
    Route::post('/pesanan/{booking}/tolak', [AdminDashboardController::class, 'rejectBooking'])->name('bookings.reject');
    Route::get('/pembayaran', [AdminDashboardController::class, 'payments'])->name('payments');
    Route::post('/pembayaran/{payment}/setujui', [AdminDashboardController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/pembayaran/{payment}/tolak', [AdminDashboardController::class, 'rejectPayment'])->name('payments.reject');
    Route::get('/pembatalan', [AdminDashboardController::class, 'cancellations'])->name('cancellations');
    Route::post('/pembatalan/{cancellation}', [AdminDashboardController::class, 'resolveCancellation'])->name('cancellations.resolve');
    Route::get('/refund', [AdminDashboardController::class, 'refunds'])->name('refunds');
    Route::post('/refund/{refund}/berhasil', [AdminDashboardController::class, 'confirmRefund'])->name('refunds.confirm');
    Route::post('/refund/{refund}/gagal', [AdminDashboardController::class, 'failRefund'])->name('refunds.fail');
    Route::get('/komplain', [AdminDashboardController::class, 'complaints'])->name('complaints');
    Route::post('/komplain/{complaint}', [AdminDashboardController::class, 'updateComplaint'])->name('complaints.update');
    Route::get('/notifikasi', [AdminDashboardController::class, 'logs'])->name('logs');
    Route::post('/notifikasi/{log}/ulang', [AdminDashboardController::class, 'retryLog'])->name('logs.retry');
    Route::get('/pengaturan', [AdminDashboardController::class, 'settings'])->name('settings');
    Route::put('/pengaturan', [AdminDashboardController::class, 'updateSettings'])->name('settings.update');
});
