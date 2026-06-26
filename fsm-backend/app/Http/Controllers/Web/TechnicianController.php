<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        $technicians = User::where('role', 'technician')
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('technicians.index', compact('technicians'));
    }

    public function create()
    {
        $technician = new User();
        return view('technicians.form', compact('technician'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'technician';
        $validated['is_active'] = $request->has('is_active');

        User::create($validated);

        return redirect()->route('admin.technicians.index')->with('success', 'Teknisi berhasil ditambahkan.');
    }

    public function edit(User $technician)
    {
        if ($technician->role !== 'technician') {
            abort(404);
        }
        return view('technicians.form', compact('technician'));
    }

    public function update(Request $request, User $technician)
    {
        if ($technician->role !== 'technician') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($technician->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->has('is_active');

        $technician->update($validated);

        return redirect()->route('admin.technicians.index')->with('success', 'Data teknisi berhasil diperbarui.');
    }

    public function destroy(User $technician)
    {
        if ($technician->role !== 'technician') {
            abort(404);
        }

        // Technically we might want to soft delete or prevent deletion if tasks exist
        // For simplicity, we just delete or deactivate. Let's just deactivate instead of delete to be safe.
        $technician->update(['is_active' => false]);

        return redirect()->route('admin.technicians.index')->with('success', 'Teknisi berhasil dinonaktifkan.');
    }
}
