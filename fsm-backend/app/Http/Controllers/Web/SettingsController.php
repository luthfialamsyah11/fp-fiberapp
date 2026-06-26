<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // Fetch or simulate system settings
        $settings = [
            'company_name' => 'FiberOps',
            'support_email' => 'support@fiberops.net',
            'support_phone' => '021-9988-7766',
            'gps_refresh_interval' => '30', // seconds
            'map_style' => 'openstreetmap',
            'auto_assign' => '0',
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // Simulate saving settings (return back with success)
        return back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }
}
