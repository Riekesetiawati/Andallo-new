<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->appNotifications()->latest()->paginate(20);
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }
}
