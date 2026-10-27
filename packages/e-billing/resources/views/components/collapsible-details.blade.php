{{--
    Native `<details>` is optimistic; sync open/closed into Livewire so remorphs
    (e.g. wire:poll.5s) keep the reviewer's choice for this page visit.
    Soft force-open / default_open are applied in ViewInvoice::syncAndGetCollapsibleOpen().
--}}
@props([
    'wireKey',
    'open' => false,
])

<details
    @if($open) open="open" @endif
    x-data
    x-on:toggle="$wire.setCollapsibleOpen(@js($wireKey), $el.open)"
    {{ $attributes }}
>
    {{ $slot }}
</details>
