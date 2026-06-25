@props([
    'status',
])

@if ($status)
    <flux:callout variant="success" icon="check-circle" :text="$status" />
@endif
