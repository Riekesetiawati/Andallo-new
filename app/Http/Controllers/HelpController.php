<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class HelpController extends Controller
{
    public function index()
    {
        return view('help.index', [
            'faqs' => config('andallo.faqs'),
            'support' => [
                'email' => Setting::getValue('support_email'),
                'phone' => Setting::getValue('support_phone'),
                'hours' => Setting::getValue('support_hours'),
            ],
        ]);
    }

    public function about()
    {
        return view('help.about');
    }
}
