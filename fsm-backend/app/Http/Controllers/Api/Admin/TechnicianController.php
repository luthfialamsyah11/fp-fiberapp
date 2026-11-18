<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TechnicianController extends Controller
{
    public function index()
    {
        $technicians = User::where('role', 'technician')
            ->with('latestLocation')
            ->get();

        return response()->json(['data' => $technicians]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone'    => 'nullable|string|max:50',
        ]);

        $technician = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'role'     => 'technician',
        ]);

        return response()->json(['data' => $technician], 201);
    }

    public function update(Request $request, User $user)
    {
        if ($user->role !== 'technician') {
            return response()->json(['message' => 'Technician not found.'], 404);
        }

        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'phone'     => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        if (array_key_exists('is_active', $validated) && !$validated['is_active']) {
            $validated['is_online'] = false;
        }

        $user->update($validated);

        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json(['data' => $user]);
    }

    public function destroy(User $user)
    {
        if ($user->role !== 'technician') {
            return response()->json(['message' => 'Technician not found.'], 404);
        }

        $user->update(['is_active' => false, 'is_online' => false]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Technician deactivated successfully.']);
    }
}
