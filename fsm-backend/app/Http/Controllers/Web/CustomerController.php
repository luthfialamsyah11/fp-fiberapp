<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FiberNode;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $statusFilter = (string) $request->get('status', '');
        $thirtyDaysAgo = now()->subDays(30);

        $customers = Customer::with('fiberNode')
            ->withCount([
                'tasks',
                'tasks as recent_outages_count' => function ($q) use ($thirtyDaysAgo) {
                    $q->where('created_at', '>=', $thirtyDaysAgo);
                },
            ])
            ->when($statusFilter !== '' && in_array($statusFilter, ['active', 'suspended', 'terminated'], true), fn ($q) => $q->where('status', $statusFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('pppoe_username', 'like', "%{$search}%")
                        ->orWhere('ont_serial_number', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('recent_outages_count')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $fiberNodes = FiberNode::orderBy('node_type')->orderBy('code')->get();
        $summary = [
            'total'          => Customer::count(),
            'active'         => Customer::where('status', 'active')->count(),
            'suspended'      => Customer::where('status', 'suspended')->count(),
            'repeat_outages' => Customer::whereHas('tasks', fn ($q) => $q->where('created_at', '>=', $thirtyDaysAgo), '>=', 2)->count(),
        ];

        return view('customers.index', compact('customers', 'fiberNodes', 'summary', 'search', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $tenantId = TenantContext::id();
        $uniqueCustCode = Rule::unique('customers', 'customer_code');
        $fiberExists = Rule::exists('fiber_nodes', 'id');
        if ($tenantId !== null) {
            $uniqueCustCode->where('tenant_id', $tenantId);
            $fiberExists->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'customer_code'     => ['nullable', 'string', 'max:40', $uniqueCustCode],
            'name'              => 'required|string|max:200',
            'phone'             => 'required|string|max:50',
            'email'             => 'nullable|email|max:255',
            'address'           => 'required|string|max:1000',
            'latitude'          => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude'         => 'nullable|numeric|between:-180,180|required_with:latitude',
            'service_plan'      => 'nullable|string|max:120',
            'pppoe_username'    => 'nullable|string|max:120',
            'ont_serial_number' => 'nullable|string|max:120',
            'fiber_node_id'     => ['nullable', $fiberExists],
            'odp_port_number'   => 'nullable|integer|min:1|max:256',
            'status'            => 'nullable|in:active,suspended,terminated',
        ]);

        if (empty($validated['customer_code'])) {
            $count = Customer::count();
            do {
                $count++;
                $candidate = 'CUST-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            } while (Customer::where('customer_code', $candidate)->exists());
            $validated['customer_code'] = $candidate;
        }

        $validated['name'] = strip_tags(trim($validated['name']));
        $validated['address'] = strip_tags(trim($validated['address']));
        $validated['status'] = $validated['status'] ?? 'active';

        Customer::create($validated);

        return back()->with('success', "Subscriber {$validated['customer_code']} ({$validated['name']}) registered successfully.");
    }

    public function update(Request $request, Customer $customer)
    {
        $tenantId = TenantContext::id();
        $fiberExists = Rule::exists('fiber_nodes', 'id');
        if ($tenantId !== null) {
            $fiberExists->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:200',
            'phone'             => 'required|string|max:50',
            'email'             => 'nullable|email|max:255',
            'address'           => 'required|string|max:1000',
            'latitude'          => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude'         => 'nullable|numeric|between:-180,180|required_with:latitude',
            'service_plan'      => 'nullable|string|max:120',
            'pppoe_username'    => 'nullable|string|max:120',
            'ont_serial_number' => 'nullable|string|max:120',
            'fiber_node_id'     => ['nullable', $fiberExists],
            'odp_port_number'   => 'nullable|integer|min:1|max:256',
            'status'            => 'required|in:active,suspended,terminated',
        ]);

        $validated['name'] = strip_tags(trim($validated['name']));
        $validated['address'] = strip_tags(trim($validated['address']));
        $customer->update($validated);

        return back()->with('success', "Subscriber {$customer->customer_code} updated.");
    }

    public function destroy(Customer $customer)
    {
        $code = $customer->customer_code;
        $customer->delete();
        return back()->with('success', "Subscriber {$code} removed.");
    }
}
