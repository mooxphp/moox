export function validateImportJsonText(jsonText) {
    const text = typeof jsonText === 'string' ? jsonText.trim() : '';
    if (!text) {
        return {
            valid: false,
            error: null,
            preview: null
        };
    }

    try {
        const parsed = JSON.parse(text);

        if (!Array.isArray(parsed)) {
            return {
                valid: false,
                error: 'JSON muss ein Array von Blöcken sein.',
                preview: null
            };
        }

        if (parsed.length === 0) {
            return {
                valid: false,
                error: 'Das Array ist leer.',
                preview: null
            };
        }

        for (let i = 0; i < parsed.length; i++) {
            const block = parsed[i];
            if (!block?.id || !block?.type) {
                return {
                    valid: false,
                    error: `Block ${i + 1} fehlt 'id' oder 'type' Feld.`,
                    preview: null
                };
            }
        }

        return {
            valid: true,
            error: null,
            preview: {
                blockCount: parsed.length
            }
        };
    } catch (error) {
        return {
            valid: false,
            error: error?.message || 'Ungültiges JSON Format.',
            preview: null
        };
    }
}
