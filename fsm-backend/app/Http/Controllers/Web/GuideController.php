<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SettingService;

class GuideController extends Controller
{
    public function index()
    {
        $settings = SettingService::all();
        $onlineWindowSeconds = User::ONLINE_WINDOW_SECONDS;

        return view('guide.index', compact('settings', 'onlineWindowSeconds'));
    }
}
