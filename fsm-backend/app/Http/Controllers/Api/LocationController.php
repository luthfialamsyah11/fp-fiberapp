<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TechnicianLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // Guard against Null Island / invalid (0,0) GPS readings
        if (abs((float) $request->latitude) < 0.0001 && abs((float) $request->longitude) < 0.0001) {
            return response()->json(['message' => 'Koordinat GPS tidak valid (Null Island)'], 422);
        }

        $location = TechnicianLocation::create([
            'technician_id' => auth()->id(),
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
        ]);

        // Mark technician as online
        auth()->user()->update([
            'is_online' => true
        ]);

        return response()->json([
            'message' => 'Lokasi berhasil diperbarui',
            'data'    => $location
        ]);
    }
}
