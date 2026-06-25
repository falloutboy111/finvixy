@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-1 text-center">
    <flux:heading size="xl" class="font-semibold">{{ $title }}</flux:heading>
    <flux:subheading>{{ $description }}</flux:subheading>
</div>
