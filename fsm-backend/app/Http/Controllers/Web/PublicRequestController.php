<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicRequestController extends Controller
{
    public const SERVICE_CATEGORIES = [
        'new_install' => [
            'label'            => 'New Fiber Installation',
            'default_title'    => 'FTTH New Fiber Installation Request',
            'default_priority' => 'medium',
        ],
        'outage_los' => [
            'label'            => 'Total Outage / LOS Red Light',
            'default_title'    => 'Fiber Outage / Red LOS Emergency Repair',
            'default_priority' => 'high',
        ],
        'slow_speed' => [
            'label'            => 'Slow Speed / High Optical Loss',
            'default_title'    => 'Degraded Speed & Optical Attenuation Check',
            'default_priority' => 'medium',
        ],
        'relocation' => [
            'label'            => 'Relocation / Maintenance',
            'default_title'    => 'ONT Relocation & Cable Maintenance',
            'default_priority' => 'low',
        ],
        'other' => [
            'label'            => 'General Technical Support',
            'default_title'    => 'General Fiber Field Support Request',
            'default_priority' => 'medium',
        ],
    ];

    public function create()
    {
        $settings = SettingService::all();
        $mapStyle = $settings['map_style'] ?? 'openstreetmap';

        return view('public.request', compact('settings', 'mapStyle'));
    }

    public function store(Request $request)
    {
        // Anti-spam honeypot: bots filling hidden website_url field are rejected cleanly
        if ($request->filled('website_url')) {
            return back()->withErrors(['form' => 'Automated submission rejected. Please try again.']);
        }

        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:50',
            'customer_email'   => 'nullable|email|max:255',
            'service_category' => 'required|in:new_install,outage_los,slow_speed,relocation,other',
            'title'            => 'nullable|string|max:255',
            'priority'         => 'required|in:low,medium,high',
            'address'          => 'required|string|max:1000',
            'latitude'         => 'nullable|numeric|between:-90,90',
            'longitude'        => 'nullable|numeric|between:-180,180',
            'description'      => 'required|string|max:2000',
            'client_photo'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $catMeta = self::SERVICE_CATEGORIES[$validated['service_category']] ?? self::SERVICE_CATEGORIES['other'];
        $cleanTitle = trim(strip_tags((string) ($validated['title'] ?? '')));
        if ($cleanTitle === '') {
            $cleanTitle = $catMeta['default_title'];
        }

        $photoPath = null;
        if ($request->hasFile('client_photo')) {
            $photoPath = $request->file('client_photo')->store('client_requests', 'public');
        }

        $cleanAddress = strip_tags(trim($validated['address']));
        $task = Task::create([
            'title'            => $cleanTitle,
            'customer_name'    => strip_tags(trim($validated['customer_name'])),
            'customer_phone'   => strip_tags(trim($validated['customer_phone'])),
            'customer_email'   => !empty($validated['customer_email']) ? trim($validated['customer_email']) : null,
            'service_category' => $validated['service_category'],
            'source'           => 'client_portal',
            'priority'         => $validated['priority'],
            'address'          => $cleanAddress,
            'location'         => $cleanAddress,
            'latitude'         => $validated['latitude'] ?? null,
            'longitude'        => $validated['longitude'] ?? null,
            'description'      => strip_tags(trim($validated['description'])),
            'client_photo'     => $photoPath,
            'status'           => 'pending',
            'tracking_token'   => Str::random(32),
        ]);

        TaskStatusHistory::create([
            'task_id'     => $task->id,
            'actor_id'    => null,
            'actor_role'  => 'client',
            'from_status' => null,
            'to_status'   => 'pending',
            'reason'      => '[Client Portal] Service request submitted by ' . $task->customer_name . ' (' . $catMeta['label'] . ').',
            'meta'        => [
                'source'           => 'client_portal',
                'service_category' => $task->service_category,
                'has_photo'        => !empty($photoPath),
            ],
            'ip_address'  => $request->ip(),
        ]);

        $systemUserId = User::where('role', 'admin')->orderBy('id')->value('id');
        if ($systemUserId) {
            ProgressUpdate::create([
                'task_id'          => $task->id,
                'user_id'          => $systemUserId,
                'note'             => '[Client Portal] Service request submitted by ' . $task->customer_name . ' (' . $catMeta['label'] . '). Awaiting NOC dispatcher assignment.',
                'progress_percent' => 0,
            ]);
        }

        if ($task->priority === 'high') {
            SettingService::dispatchWebhook('TASK_EMERGENCY_CREATED', [
                'task_id'          => $task->id,
                'source'           => 'client_portal',
                'service_category' => $catMeta['label'],
                'title'            => $task->title,
                'customer_name'    => $task->customer_name,
                'priority'         => 'high',
                'location'         => $task->location,
                'technician'       => 'Unassigned (Client Request)',
            ]);
        }

        return redirect()->route('public.request.receipt', ['token' => $task->tracking_token]);
    }

    public function receipt(string $token)
    {
        $task = Task::where('tracking_token', $token)
            ->with(['technician'])
            ->firstOrFail();

        $settings = SettingService::all();

        return view('public.request-receipt', compact('task', 'settings'));
    }
}
