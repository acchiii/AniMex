@extends('layouts.admin')

@section('page-title', 'Anime')

@section('header-actions')
    <a href="{{ route('admin.anime.create') }}" class="px-3 py-2 rounded-lg text-sm bg-purple-600/10 text-purple-400 border border-purple-600/20 hover:bg-purple-600/20 hover:text-purple-300 transition">
        Create Anime
    </a>
@endsection

@section('content')
    <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold">Anime Manager</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage your anime catalog</p>
        </div>

        <form method="GET" action="{{ route('admin.anime.index') }}" class="flex items-center gap-2">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search (coming soon)"
                class="w-64 max-w-full rounded-lg border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/30 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/30"
            />
            <button type="submit" class="px-3 py-2 rounded-lg text-sm bg-gray-800/70 text-gray-200 hover:bg-gray-800 transition border border-gray-700/60 dark:bg-gray-800/60 dark:text-gray-100 dark:border-gray-700/60">
                Search
            </button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/30">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-4 py-3 font-medium">Cover</th>
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Episodes</th>
                    <th class="px-4 py-3 font-medium">Year</th>
                    <th class="px-4 py-3 font-medium">Created</th>
                    <th class="px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200/70 dark:divide-gray-800">
                @forelse($anime as $item)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-950/30 transition">
                        <td class="px-4 py-3">
                            @php
                                $cover = $item->cover_image ? $item->cover_image : null;
                            @endphp
                            @if($cover)
                                <img src="{{ $cover }}" alt="{{ $item->title }}" class="w-12 h-16 object-cover rounded-md border border-gray-200 dark:border-gray-800" />
                            @else
                                <div class="w-12 h-16 rounded-md bg-gray-200 dark:bg-gray-800 border border-gray-200 dark:border-gray-800"></div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $item->title }}</div>
                            @if(!empty($item->title_english))
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->title_english }}</div>
                            @endif
                            @if(!empty($item->slug))
                                <div class="text-xs text-gray-400 dark:text-gray-500">/{{ $item->slug }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $item->type }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $item->status }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $item->episodes_count }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $item->year }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ optional($item->created_at)->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('admin.anime.edit', $item) }}" class="px-2 py-1.5 rounded-lg text-xs bg-purple-600/10 text-purple-400 border border-purple-600/20 hover:bg-purple-600/20 hover:text-purple-300 transition" aria-label="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                                    </svg>
                                </a>
                                <a href="{{ route('admin.anime.episodes', $item) }}" class="px-2 py-1.5 rounded-lg text-xs bg-gray-800/70 text-gray-200 border border-gray-700/60 hover:bg-gray-800 transition dark:bg-gray-800/60 dark:text-gray-100 dark:border-gray-700/60" aria-label="Episodes">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 6h13" />
                                        <path d="M8 12h13" />
                                        <path d="M8 18h13" />
                                        <path d="M3 6h.01" />
                                        <path d="M3 12h.01" />
                                        <path d="M3 18h.01" />
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('admin.anime.destroy', $item) }}" onsubmit="return confirm('Delete {{ addslashes($item->title) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1.5 rounded-lg text-xs bg-red-600/10 text-red-400 border border-red-600/20 hover:bg-red-600/20 hover:text-red-300 transition">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                            No anime found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $anime->withQueryString()->links() }}
    </div>
@endsection

