@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="BlogIA" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-2xl bg-gradient-to-br from-lime-300 via-emerald-300 to-sky-300 text-zinc-950 shadow-lg shadow-lime-900/10">
            <x-app-logo-icon class="size-5 fill-current text-zinc-950" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="BlogIA" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-2xl bg-gradient-to-br from-lime-300 via-emerald-300 to-sky-300 text-zinc-950 shadow-lg shadow-lime-900/10">
            <x-app-logo-icon class="size-5 fill-current text-zinc-950" />
        </x-slot>
    </flux:brand>
@endif
