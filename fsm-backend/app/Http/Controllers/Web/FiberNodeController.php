<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FiberNode;
use App\Services\SettingService;
use Illuminate\Http\Request;

class FiberNodeController extends Controller
{
    public function index(Request $request)
    {
        $typeFilter = (string) $request->get('node_type', '');
        $search = trim((string) $request->get('search', ''));

        $nodes = FiberNode::with('parent')
            ->withCount(['customers', 'tasks'])
            ->when($typeFilter !== '' && in_array($typeFilter, ['olt', 'odc', 'odp', 'closure'], true), fn ($q) => $q->where('node_type', $typeFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("CASE node_type WHEN 'olt' THEN 1 WHEN 'odc' THEN 2 WHEN 'odp' THEN 3 ELSE 4 END")
            ->orderBy('code')
            ->get();

        $summary = [
            'total'       => $nodes->count(),
            'olt'         => $nodes->where('node_type', 'olt')->count(),
            'odc'         => $nodes->where('node_type', 'odc')->count(),
            'odp'         => $nodes->where('node_type', 'odp')->count(),
            'total_ports' => (int) $nodes->sum('total_ports'),
            'used_ports'  => (int) $nodes->sum('used_ports'),
        ];

        $parentOptions = FiberNode::whereIn('node_type', ['olt', 'odc'])->orderBy('code')->get();
        $mapStyle = SettingService::get('map_style', 'openstreetmap');

        return view('fiber-assets.index', compact('nodes', 'summary', 'parentOptions', 'typeFilter', 'search', 'mapStyle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|string|max:60|unique:fiber_nodes,code',
            'name'        => 'required|string|max:160',
            'node_type'   => 'required|in:olt,odc,odp,closure',
            'parent_id'   => 'nullable|exists:fiber_nodes,id',
            'latitude'    => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude'   => 'nullable|numeric|between:-180,180|required_with:latitude',
            'address'     => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:512',
            'used_ports'  => 'nullable|integer|min:0|max:512',
            'status'      => 'nullable|in:operational,degraded,maintenance',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $validated['code'] = strtoupper(trim(strip_tags($validated['code'])));
        $validated['name'] = trim(strip_tags($validated['name']));
        $validated['used_ports'] = min((int) ($validated['used_ports'] ?? 0), (int) $validated['total_ports']);
        $validated['status'] = $validated['status'] ?? 'operational';

        FiberNode::create($validated);

        return back()->with('success', "Fiber node {$validated['code']} registered in GIS asset registry.");
    }

    public function update(Request $request, FiberNode $fiberNode)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:160',
            'node_type'   => 'required|in:olt,odc,odp,closure',
            'parent_id'   => 'nullable|exists:fiber_nodes,id',
            'latitude'    => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude'   => 'nullable|numeric|between:-180,180|required_with:latitude',
            'address'     => 'nullable|string|max:500',
            'total_ports' => 'required|integer|min:1|max:512',
            'used_ports'  => 'nullable|integer|min:0|max:512',
            'status'      => 'required|in:operational,degraded,maintenance',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $validated['name'] = trim(strip_tags($validated['name']));
        $validated['used_ports'] = min((int) ($validated['used_ports'] ?? 0), (int) $validated['total_ports']);
        if ((int) ($validated['parent_id'] ?? 0) === (int) $fiberNode->id) {
            $validated['parent_id'] = null;
        }

        $fiberNode->update($validated);

        return back()->with('success', "Fiber node {$fiberNode->code} updated.");
    }

    public function destroy(FiberNode $fiberNode)
    {
        $code = $fiberNode->code;
        $fiberNode->delete();
        return back()->with('success', "Fiber node {$code} deleted.");
    }

    /**
     * Technician APK API: return nearby OLT/ODC/ODP GIS nodes for field map overlay.
     */
    public function apiNearby(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        $nodes = FiberNode::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($lat !== null && $lng !== null && is_numeric($lat) && is_numeric($lng)) {
            $fLat = (float) $lat;
            $fLng = (float) $lng;
            $nodes = $nodes->map(function (FiberNode $n) use ($fLat, $fLng) {
                $n->setAttribute('distance_meters', round(SettingService::haversineMeters($fLat, $fLng, $n->latitude, $n->longitude)));
                return $n;
            })->sortBy('distance_meters')->values()->take(50);
        }

        return response()->json(['data' => $nodes]);
    }
}
