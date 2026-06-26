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
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        TechnicianLocation::create([
            'technician_id' => auth()->id(),
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
        ]);

        return response()->json(['message' => 'Lokasi diperbarui']);
    }
}