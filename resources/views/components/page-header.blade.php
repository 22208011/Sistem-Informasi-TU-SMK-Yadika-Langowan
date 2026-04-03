<div {{ $attributes->merge(['class' => 'relative mb-8 animate-fade-in-up']) }}>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="space-y-2">
            <div class="flex items-center gap-3">
                @if(isset($icon))
                    <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-linear-to-br from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-500/20 animate-scale-in">
                        {{ $icon }}
                    </div>
                @endif
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-zinc-800 dark:text-zinc-100 tracking-tight">
                        {{ $title }}
                    </h1>
                    @if(isset($subtitle))
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            </div>

            @if(isset($breadcrumbs))
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $breadcrumbs }}
                </div>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex flex-wrap items-center gap-3 animate-fade-in-right delay-200">
                {{ $actions }}
            </div>
        @endif
    </div>

    <!-- Bottom Border -->
    <div class="mt-5 h-px bg-zinc-200/60 dark:bg-zinc-800/60"></div>
</div>
