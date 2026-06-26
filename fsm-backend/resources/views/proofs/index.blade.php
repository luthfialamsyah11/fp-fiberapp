@extends('layouts.admin')

@section('title', 'Proof of Work')
@section('header', 'Proof of Work')

@section('content')
<div class="mb-6">
    <h2 class="text-base font-bold text-textPrimary">Completion Verifications</h2>
    <p class="text-xs text-textSecondary">Browse and verify technician-submitted Before & After photos for billing and service completion validation.</p>
</div>

<!-- Proof Gallery Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
    @forelse($tasksWithProof as $task)
        <div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden flex flex-col justify-between">
            <!-- Card Header -->
            <div class="p-4 border-b border-fsmBorder flex justify-between items-start bg-slate-50/50">
                <div class="overflow-hidden">
                    <span class="text-[9px] font-mono font-bold text-slate-400 block uppercase">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</span>
                    <a href="{{ route('admin.tasks.show', $task->id) }}" class="text-xs font-bold text-textPrimary hover:text-primary transition-all truncate block mt-0.5" title="{{ $task->title }}">
                        {{ $task->title }}
                    </a>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-50 text-green-700 border border-green-200 flex-shrink-0">
                    Verified Completed
                </span>
            </div>

            <!-- Double Image Compartment -->
            <div class="grid grid-cols-2 border-b border-fsmBorder bg-slate-50">
                @php
                    $before = null;
                    $after = null;
                    if ($task->proofs->count() > 1) {
                        $before = $task->proofs->first();
                        $after = $task->proofs->last();
                    } elseif ($task->proofs->count() === 1) {
                        $after = $task->proofs->first();
                    }
                @endphp
                
                <!-- Before Frame -->
                <div class="relative h-32 border-r border-fsmBorder overflow-hidden">
                    @if($before)
                        @if(Str::startsWith($before->image, ['http://', 'https://']))
                            <img src="{{ $before->image }}" class="w-full h-full object-cover" alt="Before">
                        @else
                            <img src="{{ asset('storage/' . $before->image) }}" class="w-full h-full object-cover" alt="Before">
                        @endif
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[10px]">No Photo</div>
                    @endif
                    <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-600/90 text-white uppercase tracking-wider">Before</span>
                </div>

                <!-- After Frame -->
                <div class="relative h-32 overflow-hidden">
                    @if($after)
                        @if(Str::startsWith($after->image, ['http://', 'https://']))
                            <img src="{{ $after->image }}" class="w-full h-full object-cover" alt="After">
                        @else
                            <img src="{{ asset('storage/' . $after->image) }}" class="w-full h-full object-cover" alt="After">
                        @endif
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 text-[10px]">No Photo</div>
                    @endif
                    <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded text-[8px] font-bold bg-green-600/90 text-white uppercase tracking-wider">After</span>
                </div>
            </div>

            <!-- Metadata Details -->
            <div class="p-4 flex-1 flex flex-col justify-between">
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between items-center text-[10px]">
                        <span class="text-textSecondary">Customer:</span>
                        <span class="font-bold text-textPrimary">{{ $task->customer_name }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px]">
                        <span class="text-textSecondary">Dispatched Agent:</span>
                        <span class="font-bold text-textPrimary">{{ $task->technician->name ?? 'System' }}</span>
                    </div>
                    @if($after && $after->description)
                        <div class="text-[10px] text-textSecondary bg-slate-50 border border-slate-100 rounded-lg p-2 leading-relaxed">
                            <span class="font-bold text-textPrimary">Agent Note:</span> {{ $after->description }}
                        </div>
                    @endif
                </div>

                <!-- View Detail Trigger -->
                <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-[9px] text-textSecondary font-medium">Finished {{ $task->updated_at->diffForHumans() }}</span>
                    <a href="{{ route('admin.tasks.show', $task->id) }}" class="text-[10px] font-bold text-primary hover:underline flex items-center gap-0.5">
                        Verify Details <ion-icon name="chevron-forward-outline"></ion-icon>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white border border-fsmBorder rounded-xl p-12 text-center text-textSecondary">
            <div class="max-w-xs mx-auto">
                <ion-icon name="images-outline" class="text-5xl mb-2 text-slate-300"></ion-icon>
                <p class="text-sm font-bold text-textPrimary">No proof of work files</p>
                <p class="text-xs mt-1">Proof uploads will show here as soon as technicians complete their field tasks.</p>
            </div>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($tasksWithProof->hasPages())
    <div class="bg-white border border-fsmBorder rounded-xl p-4 shadow-sm bg-slate-50/30">
        {{ $tasksWithProof->links() }}
    </div>
@endif
@endsection
