@props(['count' => 1])

@for($i = 0; $i < $count; $i++)
    <div class="group relative animate-shimmer" 
         style="background: linear-gradient(to right, #e5e7eb 8%, #d1d5db 18%, #e5e7eb 33%); background-size: 1000px 100%;">
        <div class="relative aspect-[3/4] rounded-xl overflow-hidden 
                    bg-gray-200 dark:bg-gray-800 
                    shadow-sm dark:shadow-none 
                    ring-1 ring-gray-200/60 dark:ring-gray-700/40">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent 
                        opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>
        <div class="mt-3 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-800 rounded animate-pulse"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-800 rounded w-2/3 animate-pulse"></div>
        </div>
    </div>
@endfor
