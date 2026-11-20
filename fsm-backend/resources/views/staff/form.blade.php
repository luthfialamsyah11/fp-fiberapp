@extends('layouts.admin')

@section('title', $staff->exists ? 'Edit Staff Member' : 'Provision Portal Staff')
@section('header', $staff->exists ? 'Edit Staff: ' . $staff->name : 'Provision New Portal Staff')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('admin.staff.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 inline-flex items-center gap-1.5">
            <ion-icon name="arrow-back-outline"></ion-icon> Back to Staff Directory
        </a>
        <span class="text-[11px] text-slate-500 font-medium">Enterprise Multi-Role RBAC</span>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs mb-6 flex items-start gap-2.5 shadow-sm">
            <ion-icon name="alert-circle" class="text-base text-red-500 flex-shrink-0 mt-0.5"></ion-icon>
            <div>
                <span class="font-semibold block mb-0.5">Please correct the following:</span>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm">
        <form action="{{ $staff->exists ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" method="POST" class="space-y-5">
            @csrf
            @if($staff->exists)
                @method('PUT')
            @endif

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $staff->name) }}" required
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                    placeholder="e.g. Sarah Jenkins">
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Work Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $staff->email) }}" required
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                    placeholder="e.g. s.jenkins@bentengsiber.com">
            </div>

            <!-- Phone Number -->
            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1.5">Phone / WhatsApp Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $staff->phone) }}"
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                    placeholder="e.g. +6281234567890">
            </div>

            <!-- Role Selection -->
            <div>
                <label for="role" class="block text-xs font-semibold text-slate-700 mb-1.5">Portal RBAC Role <span class="text-red-500">*</span></label>
                @php $currentRole = old('role', $staff->role ?? 'noc_dispatcher'); @endphp
                <select name="role" id="role" required {{ ($staff->exists && auth()->id() === $staff->id && $staff->isSuperAdmin()) ? 'disabled' : '' }}
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all bg-white">
                    @foreach($roles as $roleKey => $roleTitle)
                        <option value="{{ $roleKey }}" {{ ($currentRole === $roleKey || ($roleKey === 'super_admin' && $currentRole === 'admin')) ? 'selected' : '' }}>
                            {{ $roleTitle }}
                        </option>
                    @endforeach
                </select>
                @if($staff->exists && auth()->id() === $staff->id && $staff->isSuperAdmin())
                    <input type="hidden" name="role" value="{{ $staff->role }}">
                    <p class="text-[11px] text-slate-400 mt-1">You cannot demote your own Super Admin role while logged in.</p>
                @else
                    <p class="text-[11px] text-slate-400 mt-1">Assign appropriate security perimeter (Super Admin, NOC Dispatcher, Warehouse, or Supervisor).</p>
                @endif
            </div>

            <!-- Password Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        {{ $staff->exists ? 'New Password (Optional)' : 'Password' }} @if(!$staff->exists) <span class="text-red-500">*</span> @endif
                    </label>
                    <input type="password" name="password" id="password" {{ $staff->exists ? '' : 'required' }}
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                        placeholder="{{ $staff->exists ? 'Leave blank to retain current' : 'Min 6 characters' }}">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Confirm Password @if(!$staff->exists) <span class="text-red-500">*</span> @endif
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation" {{ $staff->exists ? '' : 'required' }}
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                        placeholder="Re-enter password">
                </div>
            </div>

            <!-- Account Active Status -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <div>
                    <span class="block text-xs font-semibold text-slate-800">Account Active Status</span>
                    <span class="text-[11px] text-slate-400">Inactive staff members cannot log in to the web console.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $staff->is_active ?? true) ? 'checked' : '' }} 
                        {{ ($staff->exists && auth()->id() === $staff->id) ? 'disabled' : '' }}
                        class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="pt-5 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.staff.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-all">
                    Cancel
                </a>
                <button type="submit" class="bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-medium text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5">
                    <ion-icon name="checkmark-circle-outline" class="text-base"></ion-icon>
                    {{ $staff->exists ? 'Update Staff Member' : 'Provision Staff Account' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
