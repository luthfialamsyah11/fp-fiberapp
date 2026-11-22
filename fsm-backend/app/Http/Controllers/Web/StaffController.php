<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Display a listing of portal staff users.
     */
    public function index(Request $request)
    {
        $allowedRoles = User::PORTAL_ROLES;

        $staffQuery = User::whereIn('role', $allowedRoles)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                if ($role === 'super_admin') {
                    $query->whereIn('role', ['admin', 'super_admin']);
                } else {
                    $query->where('role', $role);
                }
            })
            ->orderBy('name');

        $staffMembers = $staffQuery->paginate(15)->withQueryString();

        $roleCounts = [
            'all'              => User::whereIn('role', $allowedRoles)->count(),
            'super_admin'      => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'noc_dispatcher'   => User::where('role', 'noc_dispatcher')->count(),
            'warehouse_admin'  => User::where('role', 'warehouse_admin')->count(),
            'field_supervisor' => User::where('role', 'field_supervisor')->count(),
        ];

        return view('staff.index', compact('staffMembers', 'roleCounts'));
    }

    /**
     * Show form to create a new portal staff member.
     */
    public function create()
    {
        $staff = new User();
        $roles = [
            'super_admin'      => 'Super Admin (Full Root Permissions)',
            'noc_dispatcher'   => 'NOC Dispatcher (Dispatch, CRM, GIS, Monitoring)',
            'warehouse_admin'  => 'Warehouse Admin (Inventory & BOM Management)',
            'field_supervisor' => 'Field Supervisor (Live Tracking, Proofs, Reports)',
        ];

        return view('staff.form', compact('staff', 'roles'));
    }

    /**
     * Store a newly created portal staff member.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
            'role'      => ['required', 'string', Rule::in(['super_admin', 'noc_dispatcher', 'warehouse_admin', 'field_supervisor'])],
            'phone'     => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        User::create($validated);

        return redirect()->route('admin.staff.index')->with('success', "Staff member '{$validated['name']}' provisioned successfully.");
    }

    /**
     * Show form to edit an existing portal staff member.
     */
    public function edit(User $staff)
    {
        if (!in_array($staff->role, User::PORTAL_ROLES, true)) {
            abort(404, 'User is not a portal staff member.');
        }

        $roles = [
            'super_admin'      => 'Super Admin (Full Root Permissions)',
            'noc_dispatcher'   => 'NOC Dispatcher (Dispatch, CRM, GIS, Monitoring)',
            'warehouse_admin'  => 'Warehouse Admin (Inventory & BOM Management)',
            'field_supervisor' => 'Field Supervisor (Live Tracking, Proofs, Reports)',
        ];

        return view('staff.form', compact('staff', 'roles'));
    }

    /**
     * Update an existing portal staff member.
     */
    public function update(Request $request, User $staff)
    {
        if (!in_array($staff->role, User::PORTAL_ROLES, true)) {
            abort(404, 'User is not a portal staff member.');
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'password'  => 'nullable|string|min:8|confirmed',
            'role'      => ['required', 'string', Rule::in(['super_admin', 'noc_dispatcher', 'warehouse_admin', 'field_supervisor', 'admin'])],
            'phone'     => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        // Safety: Prevent logged-in user from deactivating or demoting their own super_admin account
        if (auth()->id() === $staff->id) {
            $validated['is_active'] = true;
            if ($staff->isSuperAdmin()) {
                $validated['role'] = $staff->role;
            }
        } else {
            $validated['is_active'] = $request->boolean('is_active');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $staff->update($validated);

        return redirect()->route('admin.staff.index')->with('success', "Staff account '{$staff->name}' updated successfully.");
    }

    /**
     * Toggle active status.
     */
    public function toggleStatus(User $staff)
    {
        if (!in_array($staff->role, User::PORTAL_ROLES, true)) {
            abort(404, 'User is not a portal staff member.');
        }

        if (auth()->id() === $staff->id) {
            return back()->with('error', 'You cannot deactivate your own active session.');
        }

        $staff->is_active = !$staff->is_active;
        $staff->save();

        $state = $staff->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Staff account '{$staff->name}' has been {$state}.");
    }

    /**
     * Remove portal staff member.
     */
    public function destroy(User $staff)
    {
        if (auth()->id() === $staff->id) {
            return back()->with('error', 'You cannot delete your own active session.');
        }

        if (!in_array($staff->role, User::PORTAL_ROLES, true)) {
            abort(404);
        }

        $name = $staff->name;
        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', "Staff account '{$name}' deleted.");
    }
}
