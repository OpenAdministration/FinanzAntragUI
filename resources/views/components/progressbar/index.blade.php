@props([
    'steps' => null,
    'active_step' => null,
])
<div class="w-full row">
    <nav aria-label="Progress" class="">
        <ol role="list" class="flex items-center grow">
            {{ $slot }}
        </ol>
    </nav>
</div>
