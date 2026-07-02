export function computeFloatingToolbarPosition({
    top = 0,
    left = 0,
    selectionRect = null,
    toolbarElement = null
}) {
    const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    const horizontalPadding = 16;
    const verticalGap = 12;
    const estimatedToolbarWidth = Math.max(240, toolbarElement?.offsetWidth || 360);
    const estimatedToolbarHeight = Math.max(40, toolbarElement?.offsetHeight || 44);

    const clampedLeft = Math.min(
        Math.max(left, horizontalPadding + (estimatedToolbarWidth / 2)),
        Math.max(horizontalPadding + (estimatedToolbarWidth / 2), viewportWidth - horizontalPadding - (estimatedToolbarWidth / 2))
    );

    let topPosition = top - verticalGap - estimatedToolbarHeight;
    if (selectionRect && (topPosition < 8)) {
        topPosition = selectionRect.bottom + verticalGap;
    }

    const maxTop = Math.max(8, viewportHeight - estimatedToolbarHeight - 8);
    const clampedTop = Math.min(Math.max(topPosition, 8), maxTop);

    return {
        top: clampedTop,
        left: clampedLeft
    };
}
