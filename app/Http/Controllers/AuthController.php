<?php

namespace App\Http\Controllers;

use App\Enums\ProviderStatus;
use App\Enums\UserRole;
use App\Models\Profile;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.mb_strtolower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Coba lagi dalam beberapa menit.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        RateLimiter::clear($key);

        $user = $request->user();
        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Akun ini dinonaktifkan. Hubungi bantuan Andallo.',
            ]);
        }

        return redirect()->intended($this->homeFor($user));
    }

    public function showRegister()
    {
        return view('auth.register', ['asProvider' => false]);
    }

    public function showProviderRegister()
    {
        return view('auth.register', ['asProvider' => true]);
    }

    public function register(Request $request)
    {
        $asProvider = $request->boolean('as_provider');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_name' => [$asProvider ? 'required' : 'nullable', 'string', 'max:160'],
            'city' => [$asProvider ? 'required' : 'nullable', 'string', 'max:80'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $this->normalizePhone($data['phone']),
            'password' => $data['password'],
            'role' => $asProvider ? UserRole::Provider : UserRole::Customer,
        ]);

        if ($asProvider) {
            $provider = Provider::query()->create([
                'user_id' => $user->id,
                'business_name' => $data['business_name'],
                'whatsapp' => $user->phone,
                'city' => $data['city'],
                'service_area' => $data['city'],
                'verification_status' => ProviderStatus::Draft,
                'description' => '',
            ]);
            Profile::query()->create([
                'provider_id' => $provider->id,
                'description' => '',
            ]);
        } else {
            Profile::query()->create([
                'user_id' => $user->id,
                'description' => '',
            ]);
        }

        Auth::login($user);

        return redirect($this->homeFor($user))->with('success', 'Akun berhasil dibuat.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda sudah keluar.');
    }

    private function homeFor(User $user): string
    {
        if ($user->isAdmin()) {
            return route('admin.dashboard');
        }
        if ($user->isProvider()) {
            return route('provider.dashboard');
        }

        return route('home');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits;
    }
}
