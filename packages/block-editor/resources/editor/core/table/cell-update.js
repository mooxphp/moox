export function queueTableCellContentUpdate({
    blockId,
    cellId,
    content,
    queueInlineContentUpdate,
    applyUpdate
}) {
    const key = `table:${blockId}:${cellId}`;
    queueInlineContentUpdate(key, content, (latestContent) => {
        applyUpdate(latestContent);
    });
}
