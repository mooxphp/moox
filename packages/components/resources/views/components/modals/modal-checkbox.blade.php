<input type="checkbox" id="{{ $attributes['id'] }}" class="modal-toggle" />
<div class="modal" role="dialog">
    {{ $slot }}
    <label class="modal-backdrop" for="{{ $attributes['id'] }}">Close</label>
</div>
