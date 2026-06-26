<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TechnicianLocation;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        // Get technicians with their latest coordinates and active task
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['accepted', 'on-going'])->latest();
            }])
            ->get();

        // Get location history logs for all technicians (limited to last 50 points total for display)
        $historyLogs = TechnicianLocation::with('technician')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // If a specific technician is selected for route tracking
        $selectedTechId = $request->get('technician_id');
        $selectedRoute = [];
        $selectedTech = null;
        if ($selectedTechId) {
            $selectedTech = User::find($selectedTechId);
            if ($selectedTech) {
                $selectedRoute = TechnicianLocation::where('technician_id', $selectedTechId)
                    ->orderBy('created_at', 'asc')
                    ->take(30)
                    ->get();
            }
        }

        return view('tracking.index', compact('technicians', 'historyLogs', 'selectedTech', 'selectedRoute'));
    }
}
