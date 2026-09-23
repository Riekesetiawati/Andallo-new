<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile ?: new Profile(['description' => '']);

        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $digits = preg_replace('/\D+/', '', $data['phone']) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        $user->fill([
            'name' => $data['name'],
            'phone' => $digits,
            'address' => $data['address'] ?? null,
        ])->save();

        $profile = $user->profile ?: new Profile(['user_id' => $user->id]);
        $profile->user_id = $user->id;
        $profile->provider_id = null;
        $profile->description = $data['description'] ?? '';
        if ($request->hasFile('photo')) {
            $profile->photo_path = $request->file('photo')->store('profiles', 'public');
        }
        $profile->save();

        return back()->with('success', 'Profil diperbarui.');
    }
}
