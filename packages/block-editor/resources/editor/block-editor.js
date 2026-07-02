// Block Editor Komponente
import { editorBlockCrudMethods } from './core/blocks/editor-block-crud-methods.js';
import { editorCollectionMethods } from './core/blocks/editor-collection-methods.js';
import { editorDragMethods } from './core/drag/editor-drag-methods.js';
import { editorJsonMethods } from './core/io/editor-json-methods.js';
import { editorJsonDisplayMethods } from './core/io/editor-json-display-methods.js';
import { editorMediaMethods } from './core/media/editor-media-methods.js';
import { editorTableMethods } from './core/table/editor-table-methods.js';
import { editorToolbarMethods } from './core/toolbar/editor-toolbar-methods.js';
import { editorUiMethods } from './core/toolbar/editor-ui-methods.js';
import { editorThemeMethods } from './core/themes/editor-theme-methods.js';
import { editorLinkMethods } from './core/links/editor-link-methods.js';
import { editorRenderMethods } from './core/render/editor-render-methods.js';
import { editorBlockSettingsMethods } from './core/render/editor-block-settings-methods.js';
import { editorSelectionMethods } from './core/selection/editor-selection-methods.js';
import { editorContentMethods } from './core/utils/editor-content-methods.js';
import { editorElementCacheMethods } from './core/utils/editor-element-cache-methods.js';
import { editorLifecycleMethods } from './core/state/editor-lifecycle-methods.js';
import { editorEventWiringMethods } from './core/state/editor-event-wiring-methods.js';
import { editorInitMethods } from './core/state/editor-init-methods.js';
import { editorInlineContentMethods } from './core/state/editor-inline-content-methods.js';
import { editorInteractionMethods } from './core/state/editor-interaction-methods.js';
import { editorFeedbackMethods } from './core/state/editor-feedback-methods.js';
import { editorEntryMethods } from './core/state/editor-entry-methods.js';
import { sanitizeHtmlContent } from './core/utils/dom.js';

// Alpine wertet Ausdrücke mit `with (scope)` aus; fehlt der Name im Scope-Proxy,
// fällt die Auflösung auf das globale Objekt zurück (wie bei CDN-Alpine üblich).
// Damit funktionieren ältere gecachte Block-HTML-Strings und hart gecachte Module,
// die noch `sanitizeHtmlContent(...)` in x-init/x-effect referenzieren.
if (typeof globalThis !== 'undefined') {
    globalThis.sanitizeHtmlContent = sanitizeHtmlContent;
}

function registerMooxEditorAlpineMagics() {
    if (typeof window === 'undefined' || typeof window.Alpine?.magic !== 'function') {
        return;
    }
    if (window.__mooxEditorAlpineMagicsRegistered) {
        return;
    }
    window.__mooxEditorAlpineMagicsRegistered = true;
    window.Alpine.magic('sanitizeHtml', () => (raw) => sanitizeHtmlContent(raw ?? ''));
}

function blockEditor() {
    return {
        blocks: [],
        selectedBlockId: null,
        draggingBlockId: null,
        showToolbar: false,
        showToolbarTab: 'blocks',
        toolbarSearchQuery: '',
        showJsonStructure: false,
        developerJsonEnabled: false,
        blockIdCounter: 0,
        dragStartIndex: null,
        dragOverIndex: null,
        showSidebar: false,
        blockSettingsCache: {}, // Cache für Block-Einstellungen HTML
        blockSettingsVersion: 0, // Version für Reaktivität
        showImportModal: false,
        importJSONText: '',
        importJSONValid: false,
        importJSONError: null,
        importJSONPreview: null,
        showSaveThemeModal: false,
        showEditThemeModal: false,
        showImportThemeModal: false,
        showImageSettingsModal: false,
        imageSettingsBlockId: null,
        imageSettingsUrl: '',
        imageSettingsOriginalUrl: '',
        imageSettingsMediaUsables: [],
        imageSettingsOriginalMediaUsables: [],
        imageSettingsAlt: '',
        imageSettingsTitle: '',
        imageSettingsActiveTab: 'library', // 'upload', 'library' oder 'url'
        showVideoSettingsModal: false,
        videoSettingsBlockId: null,
        videoSettingsUrl: '',
        videoSettingsPoster: '',
        videoSettingsTitle: '',
        videoSettingsActiveTab: 'library', // 'upload', 'library' oder 'url'
        showEmbedSettingsModal: false,
        embedSettingsBlockId: null,
        embedSettingsUrl: '',
        embedSettingsTitle: '',
        mediaClickArmedBlockId: null,
        showFloatingToolbar: false,
        floatingToolbarPosition: { top: 0, left: 0 },
        selectedText: '',
        selectedRange: null,
        // Einheitliches Link-Management-System
        showLinkModal: false,
        linkModal: {
            type: null, // 'block', 'selection', 'edit' - Art des Links
            blockId: null, // Block-ID (für Link-Blocks)
            url: '', // Link-URL
            target: '_blank', // Link-Target ('_self', '_blank')
            text: '', // Link-Text
            element: null, // DOM-Element (für Edit-Modus)
            range: null, // Selection-Range (für Selection-Modus)
            markerIds: null // Marker-IDs für Selektion (Fallback)
        },
        newThemeName: '',
        editThemeName: '',
        editThemeOriginalName: '',
        saveThemeError: null,
        editThemeError: null,
        themes: [],
        /** Theme-/Vorlagen-UI (Toolbar-Tab, Theme speichern); aus data-moox-theme-templates */
        themeTemplatesEnabled: true,
        addComponentsEnabled: true,
        jsonImportEnabled: false,
        mediaLibraryApiUrl: '/api/media',
        mediaLibraryCollection: null,
        mediaUsableType: '',
        mediaUsableId: '',
        mediaLibraryItems: [],
        mediaLibraryLoading: false,
        mediaLibraryError: '',
        mediaLibrarySearch: '',
        mediaLibraryType: 'image',
        mediaLibraryTarget: null,
        mediaLibraryDebounceTimeout: null,
        mediaLibraryCache: new Map(),
        mediaLibraryPendingRequests: new Map(),
        mediaLibraryPage: 1,
        mediaLibraryPerPage: 25,
        mediaLibraryTotalPages: 1,
        mediaLibraryTotalItems: 0,
        mediaLibrarySelectedUrl: '',
        mediaLibraryRecentlyUploadedUrl: '',
        mediaUploadLoading: false,
        mediaUploadError: '',
        mediaUploadLanguage: '',
        mediaUploadProgressPercent: 0,
        mediaUploadFileName: '',
        mediaUploadFileSizeLabel: '',
        templateSlug: null,
        childBlockTypes: {},
        notification: {
            show: false,
            message: '',
            type: 'success', // success, error, info, warning
            duration: 3000 // Automatisches Ausblenden in Millisekunden (0 = kein automatisches Ausblenden)
        },
        notificationTimeout: null, // Timeout-Referenz für automatisches Ausblenden
        showConfirmModal: false,
        confirmModal: {
            title: '',
            message: '',
            onConfirm: null,
            onCancel: null,
            onExtend: null, // Neue Option für "Erweitern"
            showExtend: false, // Zeigt ob "Erweitern" Button angezeigt werden soll
            showLinkFollow: false, // Zeigt ob "Link folgen" und "Bearbeiten" Buttons angezeigt werden sollen
            linkFollowUrl: null, // URL für Link-Follow
            linkFollowTarget: '_self' // Target für Link-Follow
        },
        // Performance-Optimierungen
        textSelectionTimeout: null, // Debounce-Timeout für Text-Selektion
        elementCache: new Map(), // Cache für DOM-Elemente
        eventListeners: [], // Array für Event Listener Cleanup
        // Templates für HTML-Komponenten
        templates: null, // Wird in init() geladen
        // Performance: Debouncing für Updates
        updateBlockContentTimeouts: new Map(), // Debounce-Timeouts pro Block
        inlineContentBuffer: new Map(), // Zwischenspeicher für contenteditable Inhalte
        inlineContentDebounceMs: 400, // Debounce-Zeit für Content-Updates
        validateJSONTimeout: null, // Debounce-Timeout für JSON-Validierung
        needsLivewireSync: true, // Dirty-Flag für inkrementellen Livewire-Sync
        // Performance: Caching für Rendering
        renderBlockCache: new Map(), // Cache für gerenderte Blöcke (pro Block-ID)
        renderChildCache: new Map(), // Cache für gerenderte Child-Blöcke (pro Block-ID)
        // Performance: JSON Display Cache
        jsonDisplayCache: '', // Gecachter JSON-String für Debug-Display
        jsonDisplayTimeout: null, // Debounce-Timeout für JSON-Display
        jsonDisplayHash: '', // Hash der Blöcke für Change-Detection
        livewireSyncHash: '',
        livewireHiddenInputId: '',
        // Performance: Block-Lookup Cache
        blockLookupCache: new Map(), // Cache für findBlockById Ergebnisse
        blockLookupCacheVersion: 0, // Version für Cache-Invalidierung

        // Mixins: bei gleichnamigen Keys gewinnt der spätere Spread.
        // Reihenfolge: Entry (init, Livewire-Dirty) → Domänen (Selection, CRUD, Tabelle, …)
        // → IO/Theme/Render/Link → Feedback → Toolbar zuletzt (soll nichts Wichtiges überschreiben).
        ...editorEntryMethods,

        ...editorSelectionMethods,
        ...editorContentMethods,
        ...editorElementCacheMethods,
        ...editorInitMethods,
        ...editorEventWiringMethods,
        ...editorLifecycleMethods,
        ...editorUiMethods,
        ...editorInlineContentMethods,
        ...editorInteractionMethods,
        ...editorBlockCrudMethods,
        ...editorDragMethods,
        ...editorTableMethods,
        ...editorCollectionMethods,

        ...editorJsonMethods,
        ...editorJsonDisplayMethods,

        ...editorThemeMethods,
        ...editorMediaMethods,
        ...editorLinkMethods,
        ...editorRenderMethods,
        ...editorBlockSettingsMethods,

        ...editorFeedbackMethods,

        ...editorToolbarMethods
    }
}

// Registriere die Komponente global für Alpine.js
window.blockEditor = blockEditor;

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', registerMooxEditorAlpineMagics);
}
registerMooxEditorAlpineMagics();



