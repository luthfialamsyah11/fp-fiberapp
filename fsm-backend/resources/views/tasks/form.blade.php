@extends('layouts.admin')

@section('title', $task->exists ? 'Edit Task' : 'Create New Task')
@section('header', $task->exists ? 'Edit Task #'.str_pad($task->id, 4, '0', STR_PAD_LEFT) : 'Create New Task')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.tasks.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 flex items-center gap-1.5">
        <ion-icon name="arrow-back-outline"></ion-icon> Back to Task List
    </a>
</div>

<div class="max-w-3xl mx-auto bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
    <div class="mb-6">
        <h2 class="text-base font-bold text-textPrimary">{{ $task->exists ? 'Modify Task Details' : 'Initialize New Field Ticket' }}</h2>
        <p class="text-xs text-textSecondary">Provide ticket parameters, customer metrics, and assign the appropriate wifi technician.</p>
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

    <form action="{{ $task->exists ? route('admin.tasks.update', $task->id) : route('admin.tasks.store') }}" method="POST">
        @csrf
        @if($task->exists)
            @method('PUT')
        @endif

        <div class="space-y-6">
            
            <!-- Work Order Parameters Section -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-fsmBorder pb-1.5">1. Task Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Task Title / Subject <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $task->title) }}" required 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="e.g. WiFi Loss of Signal - Red Attenuation Indicator">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Task Description / Field Instructions</label>
                        <textarea name="description" rows="3" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="Describe troubleshooting instructions or additional requirements for the technician...">{{ old('description', $task->description) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Task Priority <span class="text-red-500">*</span></label>
                        <select name="priority" required 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                            <option value="low" {{ old('priority', $task->priority) === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ old('priority', $task->priority ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority', $task->priority) === 'high' ? 'selected' : '' }}>High</option>
                        </select>
                    </div>

                    @if($task->exists)
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Work Order Status <span class="text-red-500">*</span></label>
                        <select name="status" required 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                            <option value="pending" {{ old('status', $task->status) === 'pending' ? 'selected' : '' }}>Pending (Unassigned/Waiting)</option>
                            <option value="assigned" {{ old('status', $task->status) === 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="accepted" {{ old('status', $task->status) === 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="on-going" {{ old('status', $task->status) === 'on-going' ? 'selected' : '' }}>On-Going</option>
                            <option value="completed" {{ old('status', $task->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="rejected" {{ old('status', $task->status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Scheduled Due Date</label>
                        <input type="datetime-local" name="scheduled_at" 
                            value="{{ old('scheduled_at', $task->scheduled_at ? $task->scheduled_at->format('Y-m-d\TH:i') : '') }}" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                    </div>
                </div>
            </div>

            <!-- Customer & Location Parameters Section -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-fsmBorder pb-1.5">2. Customer & Location Details</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Customer Name <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $task->customer_name) }}" required 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="Enter contact person's name">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Customer Phone Number</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', $task->customer_phone) }}" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="e.g. 0812XXXXXXXX">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Installation / Work Address <span class="text-red-500">*</span></label>
                        <textarea name="address" required rows="2" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="Full address of the service installation location...">{{ old('address', $task->address ?? $task->location) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">GPS Latitude</label>
                        <input type="text" name="latitude" value="{{ old('latitude', $task->latitude) }}" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="e.g. -6.200000">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">GPS Longitude</label>
                        <input type="text" name="longitude" value="{{ old('longitude', $task->longitude) }}" 
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all" 
                            placeholder="e.g. 106.816666">
                    </div>
                </div>
            </div>

            <!-- Technician Assignment Section -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-fsmBorder pb-1.5">3. Assign Field Technician</h3>
                
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Select Dispatch Technician</label>
                    <select name="technician_id" 
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                        <option value="">-- Leave Unassigned (Dispatch Queue / Pending) --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ old('technician_id', $task->technician_id) == $tech->id ? 'selected' : '' }}>
                                {{ $tech->name }} ({{ $tech->email }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-textSecondary">Assigning a technician will automatically set the task state to "Assigned".</p>
                </div>
            </div>

        </div>

        <!-- Action Buttons -->
        <div class="mt-8 pt-5 border-t border-fsmBorder flex justify-end gap-3">
            <a href="{{ route('admin.tasks.index') }}" class="px-5 py-2 border border-slate-200 text-slate-700 font-semibold rounded-xl text-xs hover:bg-slate-50 transition-all">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 bg-primary hover:bg-blue-700 text-white font-semibold rounded-xl text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5">
                <ion-icon name="save-outline" class="text-sm"></ion-icon>
                Save Task Order
            </button>
        </div>
    </form>
</div>
@endsection
