// Format Utilities
/** Erzeugt eine eindeutige ID (Timestamp + optionaler Offset für Mehrfachaufrufe in derselben ms). */
export function generateId(blockIdCounter) {
    const offset = Number(blockIdCounter) || 0;
    return String(Date.now() + offset);
}
