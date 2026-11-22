<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\FiberNode;
use App\Models\TenantRouter;
use App\Services\Network\MikrotikService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $statusFilter = (string) $request->get('status', '');
        $thirtyDaysAgo = now()->subDays(30);

        $customers = Customer::with(['fiberNode', 'router'])
            ->withCount([
                'tasks',
                'tasks as recent_outages_count' => function ($q) use ($thirtyDaysAgo) {
                    $q->where('created_at', '>=', $thirtyDaysAgo);
                },
            ])
            ->when($statusFilter !== '' && in_array($statusFilter, ['active', 'isolated', 'suspended', 'terminated'], true), fn ($q) => $q->where('status', $statusFilter))
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
        $routers = Schema::hasTable('tenant_routers')
            ? TenantRouter::where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get()
            : collect();
        $summary = [
            'total'          => Customer::count(),
            'active'         => Customer::where('status', 'active')->count(),
            'suspended'      => Customer::whereIn('status', ['suspended', 'isolated'])->count(),
            'repeat_outages' => Customer::whereHas('tasks', fn ($q) => $q->where('created_at', '>=', $thirtyDaysAgo), '>=', 2)->count(),
        ];

        return view('customers.index', compact('customers', 'fiberNodes', 'routers', 'summary', 'search', 'statusFilter'));
    }

    public function store(Request $request)
    {
        $tenantId = TenantContext::id();
        $uniqueCustCode = Rule::unique('customers', 'customer_code');
        $fiberExists = Rule::exists('fiber_nodes', 'id');
        $routerExists = Rule::exists('tenant_routers', 'id');
        if ($tenantId !== null) {
            $uniqueCustCode->where('tenant_id', $tenantId);
            $fiberExists->where('tenant_id', $tenantId);
            $routerExists->where('tenant_id', $tenantId);
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
            'router_id'         => ['nullable', $routerExists],
            'odp_port_number'   => 'nullable|integer|min:1|max:256',
            'status'            => 'nullable|in:active,isolated,suspended,terminated',
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
            'status'            => 'required|in:active,isolated,suspended,terminated',
        ]);

        $validated['name'] = strip_tags(trim($validated['name']));
        $validated['address'] = strip_tags(trim($validated['address']));
        $customer->update($validated);

        return back()->with('success', "Subscriber {$customer->customer_code} updated.");
    }

    public function provisionPppoe(Customer $customer)
    {
        try {
            $res = app(\App\Services\Network\MikrotikService::class)->provisionCustomerPppoe($customer);
            return back()->with('success', "MikroTik PPPoE provisioned for {$customer->customer_code} (Username: {$customer->fresh()->pppoe_username}).");
        } catch (\Throwable $e) {
            return back()->with('error', "MikroTik provisioning failed: {$e->getMessage()}");
        }
    }

    public function isolate(Customer $customer)
    {
        try {
            $ok = app(\App\Services\Network\MikrotikService::class)->isolateCustomer($customer);
            if (!$ok) {
                return back()->with('error', "Customer {$customer->customer_code} has no PPPoE username configured.");
            }
            return back()->with('success', "Subscriber {$customer->customer_code} isolated (ISOLIR profile applied and active session kicked).");
        } catch (\Throwable $e) {
            return back()->with('error', "MikroTik isolation failed: {$e->getMessage()}");
        }
    }

    public function unIsolate(Customer $customer)
    {
        try {
            $ok = app(\App\Services\Network\MikrotikService::class)->unIsolateCustomer($customer);
            if (!$ok) {
                return back()->with('error', "Customer {$customer->customer_code} has no PPPoE username configured.");
            }
            return back()->with('success', "Subscriber {$customer->customer_code} restored to active profile ({$customer->service_plan}).");
        } catch (\Throwable $e) {
            return back()->with('error', "MikroTik restore failed: {$e->getMessage()}");
        }
    }

    public function destroy(Customer $customer)
    {
        $code = $customer->customer_code;
        $customer->delete();
        return back()->with('success', "Subscriber {$code} removed.");
    }

    /**
     * Download clean CSV import template for rapid ISP onboarding.
     */
    public function importTemplate()
    {
        $csv = "customer_code,name,phone,email,address,service_plan,pppoe_username,ont_serial_number,status\n"
             . "CUST-1001,Apex Coffee Roasters,+14155550101,noc@apexcoffee.com,\"742 Market St, Suite 200\",Fiber-100Mbps,pppoe_apex01,ZTEGC81A2201,active\n"
             . "CUST-1002,Harbor Dental Clinic,+14155550102,it@harbordental.com,\"180 Pier Ave\",Fiber-300Mbps,pppoe_harbor02,HWTC992B4410,active\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bentengsiber-subscribers-template.csv"',
        ]);
    }

    /**
     * 1-Click Bulk CSV Subscriber Import (up to 2,000 rows per upload, strictly tenant-scoped).
     */
    public function importCsv(Request $request)
    {
        $tenantId = TenantContext::id();
        $routerExists = Rule::exists('tenant_routers', 'id');
        if ($tenantId !== null) {
            $routerExists->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'csv_file'  => 'required|file|mimes:csv,txt|max:5120',
            'router_id' => ['nullable', $routerExists],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to read uploaded CSV file.');
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return back()->with('error', 'Uploaded CSV file is empty.');
        }

        $headers = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $headerRow);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $processed = 0;
        $routerId = $validated['router_id'] ?? null;

        while (($row = fgetcsv($handle)) !== false && $processed < 2000) {
            $processed++;
            if (count($row) < 2) {
                $skipped++;
                continue;
            }

            $data = [];
            foreach ($headers as $idx => $col) {
                $data[$col] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
            }

            $name = strip_tags($data['name'] ?? '');
            $phone = strip_tags($data['phone'] ?? '');
            $pppoe = strip_tags($data['pppoe_username'] ?? '');
            if ($name === '' && $pppoe === '') {
                $skipped++;
                continue;
            }

            $code = strip_tags($data['customer_code'] ?? '');
            $status = in_array(strtolower($data['status'] ?? 'active'), ['active', 'isolated', 'suspended', 'terminated'], true)
                ? strtolower($data['status'] ?? 'active')
                : 'active';

            $existing = null;
            if ($code !== '') {
                $existing = Customer::where('customer_code', $code)->first();
            }
            if (!$existing && $pppoe !== '') {
                $existing = Customer::where('pppoe_username', $pppoe)->first();
            }

            $attributes = [
                'name'              => $name !== '' ? $name : $pppoe,
                'phone'             => $phone !== '' ? $phone : '-',
                'email'             => filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
                'address'           => strip_tags($data['address'] ?? '') ?: 'Imported via CSV',
                'service_plan'      => strip_tags($data['service_plan'] ?? '') ?: 'Standard FTTH',
                'pppoe_username'    => $pppoe !== '' ? $pppoe : null,
                'ont_serial_number' => strip_tags($data['ont_serial_number'] ?? '') ?: null,
                'router_id'         => $routerId ?: ($existing?->router_id),
                'status'            => $status,
            ];

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                if ($code === '') {
                    $code = 'CSV-' . strtoupper(substr(md5(($pppoe ?: $name) . microtime(true) . $processed), 0, 8));
                }
                Customer::create(array_merge($attributes, ['customer_code' => $code]));
                $created++;
            }
        }

        fclose($handle);

        return back()->with('success', "CSV import complete: {$created} created, {$updated} updated, {$skipped} skipped.");
    }

    /**
     * 1-Click Live Sync of PPPoE Secrets directly from a selected MikroTik Router into CRM.
     */
    public function syncFromMikrotik(Request $request)
    {
        $tenantId = TenantContext::id();
        $routerExists = Rule::exists('tenant_routers', 'id');
        if ($tenantId !== null) {
            $routerExists->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'router_id' => ['nullable', $routerExists],
        ]);

        $router = !empty($validated['router_id']) ? TenantRouter::find($validated['router_id']) : null;
        $isolationProfile = strtolower((string) ($router?->isolation_profile ?: 'ISOLIR'));

        try {
            $secrets = MikrotikService::forTenant()->listRouterSecrets($router, 500);
        } catch (\Throwable $e) {
            return back()->with('error', 'MikroTik sync failed: ' . $e->getMessage());
        }

        $created = 0;
        $updated = 0;
        foreach ($secrets as $sec) {
            $username = trim((string) ($sec['name'] ?? ''));
            if ($username === '') {
                continue;
            }

            $profile = trim((string) ($sec['profile'] ?? 'Standard FTTH'));
            $comment = strip_tags(trim((string) ($sec['comment'] ?? '')));
            $isDisabled = strtolower((string) ($sec['disabled'] ?? 'false')) === 'true';
            $status = $isDisabled
                ? 'suspended'
                : (strtolower($profile) === $isolationProfile ? 'isolated' : 'active');

            $parsedName = $username;
            $parsedPhone = '-';
            if ($comment !== '') {
                $parts = array_map('trim', explode('|', $comment));
                if (!empty($parts[0])) {
                    $parsedName = mb_substr($parts[0], 0, 180);
                }
                if (!empty($parts[1])) {
                    $parsedPhone = mb_substr($parts[1], 0, 40);
                }
            }

            $existing = Customer::where('pppoe_username', $username)->first();
            if ($existing) {
                $existing->update([
                    'service_plan' => $status === 'isolated' ? ($existing->service_plan ?: 'Standard FTTH') : $profile,
                    'router_id'    => $router?->id ?? $existing->router_id,
                    'status'       => $status,
                ]);
                $updated++;
            } else {
                $code = 'MT-' . strtoupper(substr(md5($username . '-' . ($tenantId ?? 1)), 0, 7));
                Customer::create([
                    'customer_code'  => $code,
                    'name'           => $parsedName,
                    'phone'          => $parsedPhone,
                    'address'        => $router ? "Synced from Router {$router->name}" : 'Synced from MikroTik RouterOS',
                    'service_plan'   => $status === 'isolated' ? 'Standard FTTH' : $profile,
                    'pppoe_username' => $username,
                    'router_id'      => $router?->id,
                    'status'         => $status,
                ]);
                $created++;
            }
        }

        $routerLabel = $router ? $router->name : 'Default MikroTik';
        return back()->with('success', "1-Click MikroTik sync ({$routerLabel}) finished: {$created} new subscribers imported, {$updated} existing updated.");
    }
}
