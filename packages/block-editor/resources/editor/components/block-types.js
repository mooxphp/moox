// Block Type Definitions and Helpers - als Objekt organisiert
export const BLOCK_TYPES = {
    paragraph: {
        label: '📝 Paragraph',
        category: 'text',
        tag: 'div',
        placeholder: 'Schreibe einen Absatz...',
        classes: 'block-placeholder min-h-[1.5rem]',
        canHaveChildren: false
    },
    heading1: {
        label: 'H1 Überschrift',
        category: 'text',
        tag: 'h1',
        placeholder: 'Überschrift 1...',
        classes: 'block-placeholder text-3xl font-bold mb-2 min-h-[2rem]',
        canHaveChildren: false
    },
    heading2: {
        label: 'H2 Überschrift',
        category: 'text',
        tag: 'h2',
        placeholder: 'Überschrift 2...',
        classes: 'block-placeholder text-2xl font-bold mb-2 min-h-[1.75rem]',
        canHaveChildren: false
    },
    heading3: {
        label: 'H3 Überschrift',
        category: 'text',
        tag: 'h3',
        placeholder: 'Überschrift 3...',
        classes: 'block-placeholder text-xl font-bold mb-2 min-h-[1.5rem]',
        canHaveChildren: false
    },
    heading4: {
        label: 'H4 Überschrift',
        category: 'text',
        tag: 'h4',
        placeholder: 'Überschrift 4...',
        classes: 'block-placeholder text-lg font-bold mb-2 min-h-[1.5rem]',
        canHaveChildren: false
    },
    heading5: {
        label: 'H5 Überschrift',
        category: 'text',
        tag: 'h5',
        placeholder: 'Überschrift 5...',
        classes: 'block-placeholder text-base font-bold mb-2 min-h-[1.25rem]',
        canHaveChildren: false
    },
    heading6: {
        label: 'H6 Überschrift',
        category: 'text',
        tag: 'h6',
        placeholder: 'Überschrift 6...',
        classes: 'block-placeholder text-sm font-bold mb-2 min-h-[1.25rem]',
        canHaveChildren: false
    },
    code: {
        label: '💻 Code Block',
        category: 'text',
        tag: 'pre',
        placeholder: 'Schreibe Code...',
        classes: 'block-placeholder bg-gray-900 text-green-400 p-4 rounded font-mono text-sm min-h-[4rem] whitespace-pre-wrap',
        isTextContent: true,
        canHaveChildren: false
    },
    quote: {
        label: '💬 Zitat',
        category: 'text',
        tag: 'blockquote',
        placeholder: 'Zitat...',
        classes: 'block-placeholder border-l-4 border-blue-500 pl-4 italic text-gray-700 min-h-[1.5rem]',
        canHaveChildren: false
    },
    callout: {
        label: '🚨 Callout',
        category: 'layout',
        tag: 'div',
        placeholder: '',
        classes: 'grid grid-cols-1 gap-4 p-4 border-2 rounded-lg min-h-[140px]',
        isContainer: true,
        columnCount: 1,
        canHaveChildren: true
    },
    divider: {
        label: '➖ Trennlinie',
        category: 'layout',
        tag: 'hr',
        placeholder: '',
        classes: 'border-t-2 border-gray-300 my-4',
        isVoid: true,
        canHaveChildren: false
    },
    twoColumn: {
        label: '📊 Zwei Spalten',
        category: 'layout',
        tag: 'div',
        placeholder: '',
        classes: 'grid grid-cols-2 gap-4 p-4 border-2 border-dashed border-gray-300 rounded-lg min-h-[200px]',
        isContainer: true,
        columnCount: 2,
        canHaveChildren: true
    },
    threeColumn: {
        label: '📊 Drei Spalten',
        category: 'layout',
        tag: 'div',
        placeholder: '',
        classes: 'grid grid-cols-3 gap-4 p-4 border-2 border-dashed border-gray-300 rounded-lg min-h-[200px]',
        isContainer: true,
        columnCount: 3,
        canHaveChildren: true
    },
    group: {
        label: '🧩 Gruppe',
        category: 'layout',
        tag: 'div',
        placeholder: '',
        classes: 'grid grid-cols-1 gap-4 p-4 border-2 border-dashed border-gray-300 rounded-lg min-h-[140px]',
        isContainer: true,
        columnCount: 1,
        canHaveChildren: true
    },
    table: {
        label: '📋 Tabelle',
        category: 'interactive',
        tag: 'table',
        placeholder: '',
        classes: 'w-full border-collapse border border-gray-300',
        isContainer: true,
        canHaveChildren: true
    },
    image: {
        label: '🖼️ Bild',
        category: 'media',
        tag: 'img',
        placeholder: '',
        classes: 'w-full h-auto rounded',
        isVoid: true,
        hasImageData: true,
        canHaveChildren: false
    },
    video: {
        label: '🎬 Video',
        category: 'media',
        tag: 'video',
        placeholder: '',
        classes: 'w-full rounded',
        isVoid: true,
        hasVideoData: true,
        canHaveChildren: false
    },
    embed: {
        label: '🌐 Embed',
        category: 'media',
        tag: 'iframe',
        placeholder: '',
        classes: 'w-full rounded-lg border border-gray-200',
        isVoid: true,
        hasEmbedData: true,
        canHaveChildren: false
    },
    checklist: {
        label: '☑️ Checkliste',
        category: 'interactive',
        tag: 'div',
        placeholder: '',
        classes: 'space-y-2 p-4 border border-gray-300 rounded-lg',
        isContainer: false,
        canHaveChildren: false,
        hasChecklistData: true
    },
    list: {
        label: '• Liste',
        category: 'interactive',
        tag: 'div',
        placeholder: '',
        classes: 'space-y-2 p-4 border border-gray-300 rounded-lg',
        isContainer: false,
        canHaveChildren: false,
        hasListData: true
    },
    link: {
        label: '🔗 Link',
        category: 'interactive',
        tag: 'a',
        placeholder: 'Link-Text eingeben...',
        classes: 'block-placeholder min-h-[1.5rem] text-blue-600 hover:text-blue-800 hover:underline',
        isContainer: true,
        canHaveChildren: true,
        hasLinkData: true
    },
    toggleList: {
        label: '📑 Toggle-Liste',
        category: 'interactive',
        tag: 'div',
        placeholder: 'Überschrift...',
        classes: 'border border-gray-300 rounded-lg',
        isContainer: true,
        canHaveChildren: true
    },
    tabs: {
        label: '🗂️ Tabs-Block',
        category: 'interactive',
        tag: 'div',
        placeholder: '',
        classes: 'border border-gray-300 rounded-lg',
        isContainer: false,
        canHaveChildren: false,
        hasTabsData: true
    },
    accordion: {
        label: '❓ FAQ / Accordion',
        category: 'interactive',
        tag: 'div',
        placeholder: '',
        classes: 'border border-gray-300 rounded-lg',
        isContainer: false,
        canHaveChildren: false,
        hasAccordionData: true
    }
};

export const BlockTypes = {
    getBlockTypeConfig(type) {
        return BLOCK_TYPES[type] || BLOCK_TYPES.paragraph;
    },

    // Gibt die Anzahl der Spalten für einen Block-Typ zurück
    getColumnCount(blockType) {
        const config = this.getBlockTypeConfig(blockType);
        return config.columnCount || 0;
    },

    // Prüft ob ein Block-Typ ein Container ist
    isContainerBlock(blockType) {
        const config = this.getBlockTypeConfig(blockType);
        return config.isContainer === true;
    },

    // Prüft ob ein Block-Typ spaltenähnliches Container-Verhalten hat
    isColumnLikeBlock(blockType) {
        const config = this.getBlockTypeConfig(blockType);
        return (config.columnCount || 0) > 0;
    },

    // Prüft ob ein Block-Typ Kinder haben darf
    canBlockHaveChildren(blockType) {
        // Column-Blöcke können immer Kinder haben (spezialfall)
        if (blockType === 'column') {
            return true;
        }
        
        const config = this.getBlockTypeConfig(blockType);
        return config.canHaveChildren === true;
    }
};
