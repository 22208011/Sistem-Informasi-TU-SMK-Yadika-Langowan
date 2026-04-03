<div class="space-y-4">
    {{-- Header Skeleton --}}
    <div class="flex items-center justify-between">
        <div class="space-y-2">
            <div class="h-6 w-48 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
            <div class="h-4 w-32 animate-pulse rounded bg-zinc-200 dark:bg-zinc-700"></div>
        </div>
        <div class="h-10 w-32 animate-pulse rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
    </div>

    {{-- Stats Skeleton --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @for ($i = 0; $i < 4; $i++)
            <div class="h-24 animate-pulse rounded-xl bg-zinc-200 dark:bg-zinc-700"></div>
        @endfor
    </div>

    {{-- Table Skeleton --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="h-12 animate-pulse bg-zinc-100 dark:bg-zinc-800"></div>
        @for ($i = 0; $i < 5; $i++)
            <div class="h-16 animate-pulse border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900"></div>
        @endfor
    </div>
</div>
