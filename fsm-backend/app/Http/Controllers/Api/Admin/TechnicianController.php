<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicianLocation;
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
            'name'     => 'required|string',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone'    => 'nullable|string',
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
        $user->update($request->only('name', 'phone', 'is_active'));
        return response()->json(['data' => $user]);
    }

    public function destroy(User $user)
    {
        $user->update(['is_active' => false]);
        return response()->json(['message' => 'Teknisi dinonaktifkan']);
    }
}