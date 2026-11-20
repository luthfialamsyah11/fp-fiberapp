<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'token'       => 'required|string|min:16|max:512',
            'platform'    => 'nullable|in:android,ios,web',
            'device_name' => 'nullable|string|max:120',
        ]);

        $record = DeviceToken::updateOrCreate(
            ['token' => trim($validated['token'])],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $validated['platform'] ?? 'android',
                'device_name'  => isset($validated['device_name']) ? trim(strip_tags($validated['device_name'])) : null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Push notification device token registered.',
            'data'    => $record,
        ], 201);
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', trim($validated['token']))
            ->delete();

        return response()->json(['message' => 'Device token unregistered.']);
    }
}
