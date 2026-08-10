<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="relative overflow-hidden px-4 py-6 sm:px-6 lg:px-8">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(132,204,22,0.22),transparent_30rem),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.16),transparent_26rem)] dark:bg-[radial-gradient(circle_at_top_left,rgba(132,204,22,0.12),transparent_30rem),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.12),transparent_26rem)]"></div>

        <div class="mx-auto w-full max-w-7xl">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
