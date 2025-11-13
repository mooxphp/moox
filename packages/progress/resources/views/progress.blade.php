<div class="progress-bar" style="--progress-color: {{ $getColor() }};">
    <div class="progress-bar-fill" style="width: {{ $getProgress() }}%;"></div>
    <span class="progress-bar-text">{{ $getFormattedState() }}</span>
</div>