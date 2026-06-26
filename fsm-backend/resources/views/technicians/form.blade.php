@extends('layouts.admin')

@section('title', $technician->exists ? 'Edit Technician' : 'Add Technician')
@section('header', $technician->exists ? 'Edit Technician Profile' : 'Register New Technician')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.technicians.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 flex items-center gap-1.5">
        <ion-icon name="arrow-back-outline"></ion-icon> Back to Directory
    </a>
</div>

<div class="max-w-2xl mx-auto bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
    <div class="mb-6">
        <h2 class="text-base font-bold text-textPrimary">{{ $technician->exists ? 'Modify Technician Attributes' : 'Create Technician Account' }}</h2>
        <p class="text-xs text-textSecondary">Provide credentials and mobile application authentication details for the field agent.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs mb-6 flex items-start gap-2.5">
            <ion-icon name="alert-circle" class="text-base text-red-500 flex-shrink-0 mt-0.5"></ion-icon>
            <div>
                <span class="font-semibold block mb-0.5">Please correct the following validation issues</span>
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ $technician->exists ? route('admin.technicians.update', $technician->id) : route('admin.technicians.store') }}" method="POST">
        @csrf
        @if($technician->exists)
            @method('PUT')
        @endif

        <div class="space-y-5">
            
            <!-- Full Name -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <ion-icon name="person-outline"></ion-icon>
                    </div>
                    <input type="text" name="name" value="{{ old('name', $technician->name) }}" required 
                        class="pl-9 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                        placeholder="e.g. Alex Pratama">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <ion-icon name="mail-outline"></ion-icon>
                        </div>
                        <input type="email" name="email" value="{{ old('email', $technician->email) }}" required 
                            class="pl-9 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="tech@company.com">
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <ion-icon name="call-outline"></ion-icon>
                        </div>
                        <input type="text" name="phone" value="{{ old('phone', $technician->phone) }}" 
                            class="pl-9 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="e.g. 0812XXXXXXXX">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Password -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Password
                        @if(!$technician->exists) <span class="text-red-500">*</span> @endif
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <ion-icon name="lock-closed-outline"></ion-icon>
                        </div>
                        <input type="password" name="password" {{ !$technician->exists ? 'required' : '' }} 
                            class="pl-9 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="{{ $technician->exists ? 'Leave blank to keep current' : 'Min 6 characters' }}">
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Confirm Password
                        @if(!$technician->exists) <span class="text-red-500">*</span> @endif
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <ion-icon name="lock-closed-outline"></ion-icon>
                        </div>
                        <input type="password" name="password_confirmation" {{ !$technician->exists ? 'required' : '' }} 
                            class="pl-9 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="Repeat password">
                    </div>
                </div>
            </div>
            
            <!-- Active Status Toggle -->
            <div class="pt-2">
                <label class="flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" 
                        class="w-4.5 h-4.5 text-blue-600 border-slate-200 rounded focus:ring-primary" 
                        {{ old('is_active', $technician->is_active ?? true) ? 'checked' : '' }}>
                    <span class="ml-2.5 text-xs font-semibold text-slate-700">Account Active (Authorized to log in to Mobile FSM App)</span>
                </label>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 pt-5 border-t border-fsmBorder flex justify-end gap-3">
            <a href="{{ route('admin.technicians.index') }}" class="px-5 py-2 border border-slate-200 text-slate-700 font-semibold rounded-xl text-xs hover:bg-slate-50 transition-all">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 bg-primary hover:bg-blue-700 text-white font-semibold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5">
                <ion-icon name="save-outline" class="text-sm"></ion-icon>
                Save Technician Profile
            </button>
        </div>
    </form>
</div>
@endsection
