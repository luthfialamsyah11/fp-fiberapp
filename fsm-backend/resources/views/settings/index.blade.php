@extends('layouts.admin')

@section('title', 'Settings')
@section('header', 'Settings')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
    <div class="mb-6 border-b border-fsmBorder pb-4">
        <h2 class="text-base font-bold text-textPrimary">Platform Settings</h2>
        <p class="text-xs text-textSecondary">Configure company metrics, GPS tracking intervals, and automated scheduling rules.</p>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        <div class="space-y-6">
            
            <!-- Company Info Group -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">1. Organization Details</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Company Name</label>
                        <input type="text" name="company_name" value="{{ $settings['company_name'] }}" required
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Support Email</label>
                        <input type="email" name="support_email" value="{{ $settings['support_email'] }}" required
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Support Phone Hotline</label>
                        <input type="text" name="support_phone" value="{{ $settings['support_phone'] }}" required
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                    </div>
                </div>
            </div>

            <!-- GPS & Map Settings Group -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-t border-fsmBorder pt-5">2. Dispatch & Tracking</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">GPS Refresh Interval (Seconds)</label>
                        <select name="gps_refresh_interval" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                            <option value="15" {{ $settings['gps_refresh_interval'] == '15' ? 'selected' : '' }}>15 Seconds (Realtime)</option>
                            <option value="30" {{ $settings['gps_refresh_interval'] == '30' ? 'selected' : '' }}>30 Seconds (Default)</option>
                            <option value="60" {{ $settings['gps_refresh_interval'] == '60' ? 'selected' : '' }}>1 Minute</option>
                            <option value="300" {{ $settings['gps_refresh_interval'] == '300' ? 'selected' : '' }}>5 Minutes</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Default Map Tile Styling</label>
                        <select name="map_style" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                            <option value="openstreetmap" {{ $settings['map_style'] == 'openstreetmap' ? 'selected' : '' }}>OpenStreetMap Standard</option>
                            <option value="mapbox" {{ $settings['map_style'] == 'mapbox' ? 'selected' : '' }}>Mapbox Light (Premium)</option>
                            <option value="carto" {{ $settings['map_style'] == 'carto' ? 'selected' : '' }}>CartoDB Positron</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Intelligent Routing -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-t border-fsmBorder pt-5">3. Automated Operations</h3>
                <div>
                    <label class="flex items-center cursor-pointer select-none">
                        <input type="checkbox" name="auto_assign" value="1" 
                            class="w-4.5 h-4.5 text-blue-600 border-slate-200 rounded focus:ring-primary" 
                            {{ $settings['auto_assign'] == '1' ? 'checked' : '' }}>
                        <span class="ml-2.5 text-xs font-semibold text-slate-700">Enable AI Auto-Dispatch (Assign nearest idle technician)</span>
                    </label>
                </div>
            </div>

        </div>

        <!-- Submit Button -->
        <div class="mt-8 pt-5 border-t border-fsmBorder flex justify-end">
            <button type="submit" class="px-5 py-2 bg-primary hover:bg-blue-700 text-white font-semibold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5">
                <ion-icon name="save-outline" class="text-sm"></ion-icon>
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
