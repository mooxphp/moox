<div class="progress-bar" style="--progress-color: {{ $getColor() }};">
    <div class="progress-bar-value" style="width: {{ $getProgress() }}%;">
        {{ $getFormattedState() }}
    </div>
</div>