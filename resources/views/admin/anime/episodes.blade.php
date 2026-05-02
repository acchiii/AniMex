@extends('layouts.admin')

@section('title', 'Episodes')
@section('page-title', 'Episodes: ' . ($anime->title_english ?: $anime->title))

@section('header-actions')
<a href="{{ route('admin.anime.index') }}" class="text-gray-400 hover:text-gray-200 text-sm transition">&larr; Back to Anime</a>
@endsection

@section('content')

{{-- Add Episode --}}
<div class="bg-gray-900 border border-gray-800 rounded-xl p-6 mb-6">
    <h3 class="font-medium mb-4">Add Episode</h3>
    <form action="{{ route('admin.anime.episodes.store', $anime) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Number *</label>
                <input type="number" name="number" value="{{ old('number') }}" min="1" required class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div class="col-span-2">
                <label class="block text-sm text-gray-400 mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Duration (sec)</label>
                <input type="number" name="duration" value="{{ old('duration') }}" min="0" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
        </div>
        <div class="flex flex-wrap gap-4 mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_filler" value="1" {{ old('is_filler') ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Filler</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_recap" value="1" {{ old('is_recap') ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Recap</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_subbed" value="1" {{ old('is_subbed', true) ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Subbed</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_dubbed" value="1" {{ old('is_dubbed') ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Dubbed</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_premium_only" value="1" {{ old('is_premium_only') ? 'checked' : '' }} class="rounded bg-gray-800 border-gray-700 text-purple-600 focus:ring-purple-600">
                <span class="text-sm text-gray-400">Premium</span>
            </label>
        </div>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition">Add Episode</button>
    </form>
</div>

{{-- Episodes List --}}
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-800">
        <h3 class="font-medium">{{ $episodes->count() }} Episode{{ $episodes->count() !== 1 ? 's' : '' }}</h3>
    </div>
    <div class="divide-y divide-gray-800">
        @forelse($episodes as $ep)
            <div class="px-5 py-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-purple-600/10 rounded-lg flex items-center justify-center text-sm font-medium text-purple-400 flex-shrink-0">{{ $ep->number }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium">{{ $ep->title ?: 'Episode ' . $ep->number }}</p>
                        <p class="text-xs text-gray-500">
                            @if($ep->duration){{ floor($ep->duration / 60) }}:{{ str_pad($ep->duration % 60, 2, '0', STR_PAD_LEFT) }}@endif
                            @if($ep->is_filler)<span class="ml-2 text-yellow-400">Filler</span>@endif
                            @if($ep->is_recap)<span class="ml-2 text-gray-400">Recap</span>@endif
                        </p>
                    </div>
                    <div class="flex items-center gap-1 text-xs">
                        @if($ep->sources->isNotEmpty())
                            <span class="text-gray-500">{{ $ep->sources->count() }} source{{ $ep->sources->count() > 1 ? 's' : '' }}</span>
                        @endif
                        @if($ep->is_subbed)<span class="text-blue-400">SUB</span>@endif
                        @if($ep->is_dubbed)<span class="text-green-400 ml-1">DUB</span>@endif
                    </div>
                </div>
            </div>
        @empty
            <p class="px-5 py-12 text-center text-gray-500 text-sm">No episodes yet. Add one above.</p>
        @endforelse
    </div>
</div>
@endsection
