// Block-Shortcuts fuer schnelle Block-Typen
export const BLOCK_SHORTCUTS = [
    {
        id: 'heading-1',
        label: 'Ueberschrift 1',
        action: { type: 'heading1' },
        trigger: {
            keys: [''],
            codes: ['Space'],
            text: '#',
        }
    },
    {
        id: 'heading-2',
        label: 'Ueberschrift 2',
        action: { type: 'heading2' },
        trigger: {
            keys: [' '],
            codes: ['Space'],
            text: '##'
        }
    },
    {
        id: 'heading-3',
        label: 'Ueberschrift 3',
        action: { type: 'heading3' },
        trigger: {
            keys: [' '],
            codes: ['Space'],
            text: '###'
        }
    },
    {
        id: 'heading-4',
        label: 'Ueberschrift 4',
        action: { type: 'heading4' },
        trigger: {
            keys: [' '],
            codes: ['Space'],
            text: '####'
        }
    },
    {
        id: 'heading-5',
        label: 'Ueberschrift 5',
        action: { type: 'heading5' },
        trigger: {
            keys: [' '],
            codes: ['Space'],
            text: '#####'
        }
    },
    {
        id: 'heading-6',
        label: 'Ueberschrift 6',
        action: { type: 'heading6' },
        trigger: {
            keys: [' '],
            codes: ['Space'],
            text: '######'
        }
    },
    {
        id: 'list-unordered',
        label: 'Liste (unnummeriert)',
        action: { type: 'list', listStyle: 'unordered' },
        trigger: {
            keys: ['-'],
            codes: ['Minus', 'NumpadSubtract'],
            text: ''
        }
    },
    {
        id: 'list-ordered',
        label: 'Liste (nummeriert)',
        action: { type: 'list', listStyle: 'ordered' },
        trigger: {
            keys: ['.'],
            codes: ['Period', 'NumpadDecimal'],
            text: '1'
        }
    },
    {
        id: 'quote',
        label: 'Zitat',
        action: { type: 'quote' },
        trigger: {
            keys: ['>'],
            codes: ['Period', 'NumpadDecimal'],
            text: ''
        }
    },
    {
        id: 'table',
        label: 'Tabelle',
        action: { type: 'table' },
        trigger: {
            keys: ['|'],
            codes: [],
            text: ''
        }
    }
];

function normalizeShortcut(shortcut) {
    if (!shortcut || typeof shortcut !== 'object') return null;
    const normalized = { ...shortcut };
    if (!normalized.action || typeof normalized.action !== 'object') {
        normalized.action = { type: 'paragraph' };
    }
    if (normalized.trigger && typeof normalized.trigger === 'object') {
        normalized.trigger = {
            keys: Array.isArray(normalized.trigger.keys) ? normalized.trigger.keys : [],
            codes: Array.isArray(normalized.trigger.codes) ? normalized.trigger.codes : [],
            text: normalized.trigger.text ?? ''
        };
    }
    return normalized;
}

function matchesTrigger(trigger, { key, code, text }) {
    if (!trigger) return false;
    const trimmedText = (text || '').trim();
    if (trigger.text !== undefined && trigger.text !== null && trimmedText !== trigger.text) {
        return false;
    }
    if (Array.isArray(trigger.keys) && trigger.keys.length > 0) {
        if (trigger.keys.includes(key)) {
            return true;
        }
    }
    if (Array.isArray(trigger.codes) && trigger.codes.length > 0) {
        if (trigger.codes.includes(code)) {
            return true;
        }
    }
    return false;
}

export function registerBlockShortcut(shortcut, { prepend = false } = {}) {
    const normalized = normalizeShortcut(shortcut);
    if (!normalized) return false;
    if (prepend) {
        BLOCK_SHORTCUTS.unshift(normalized);
    } else {
        BLOCK_SHORTCUTS.push(normalized);
    }
    return true;
}

export function registerBlockShortcuts(shortcuts, options) {
    if (!Array.isArray(shortcuts)) return 0;
    let count = 0;
    for (const shortcut of shortcuts) {
        if (registerBlockShortcut(shortcut, options)) {
            count += 1;
        }
    }
    return count;
}

export function getBlockShortcuts() {
    return BLOCK_SHORTCUTS.slice();
}

export function getBlockShortcutOverview() {
    return BLOCK_SHORTCUTS.map((shortcut) => ({
        id: shortcut.id,
        label: shortcut.label,
        type: shortcut.action?.type,
        listStyle: shortcut.action?.listStyle,
        trigger: shortcut.trigger,
        hasMatch: typeof shortcut.match === 'function'
    }));
}

export function matchBlockShortcut({ key, code, text, event }) {
    for (const shortcut of BLOCK_SHORTCUTS) {
        try {
            if (typeof shortcut.match === 'function') {
                if (shortcut.match({ key, code, text, event })) {
                    return shortcut;
                }
            } else if (matchesTrigger(shortcut.trigger, { key, code, text })) {
                return shortcut;
            }
        } catch (error) {
            // Ignoriere fehlerhafte Shortcut-Definitionen
        }
    }
    return null;
}
