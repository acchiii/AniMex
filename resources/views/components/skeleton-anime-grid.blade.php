<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4" id="skeleton-grid">
    @for($i = 0; $i < 12; $i++)
        <div class="group">
            <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-200 dark:bg-gray-800 shadow-sm ring-1 ring-gray-200/60 dark:ring-gray-700/40 animate-shimmer" 
                 style="background: linear-gradient(to right, #e5e7eb 8%, #d1d5db 18%, #e5e7eb 33%); background-size: 1000px 100%;">
            </div>
            <div class="mt-3 space-y-2">
                <div class="h-4 bg-gray-200 dark:bg-gray-800 rounded animate-pulse"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded w-2/3 animate-pulse"></div>
            </div>
        </div>
    @endfor
</div>
