import { BlockManagement } from '../blocks/management.js';
import * as EditorConfig from '../config/editor-config.js';
import { Storage } from '../io/storage.js';
import { Utils } from '../utils/index.js';

function resolveTemplateSlugFromRoot(rootElement) {
    const parent = rootElement?.parentElement;
    if (!parent) {
        return null;
    }

    const raw = parent.dataset?.templateSlug ?? parent.getAttribute('data-template-slug');
    if (raw === undefined || raw === null) {
        return null;
    }

    const normalized = String(raw).trim();

    return normalized !== '' ? normalized : null;
}

async function loadTemplatesModule(assetVersion) {
    const query = typeof assetVersion === 'string' && assetVersion.trim() !== ''
        ? `?v=${assetVersion.trim()}`
        : '';

    return import(`../../components/templates/index.js${query}`);
}

function hardenSidebarTemplateExpressions(sidebarTemplate) {
    if (typeof sidebarTemplate !== 'string' || sidebarTemplate.trim() === '') {
        return sidebarTemplate;
    }

    return sidebarTemplate
        .replaceAll(
            'x-show="selectedBlockId === null"',
            'x-show="typeof selectedBlockId === \'undefined\' || selectedBlockId === null"'
        )
        .replaceAll(
            'x-for="block in getAllBlocks()"',
            'x-for="block in (typeof getAllBlocks === \'function\' ? getAllBlocks() : [])"'
        )
        .replaceAll(
            'x-show="block.id === selectedBlockId"',
            'x-show="typeof selectedBlockId !== \'undefined\' && block.id === selectedBlockId"'
        );
}

export const editorInitMethods = {
    async runEditorBootstrap() {
        this.childBlockTypes = EditorConfig.resolveChildBlockTypes(this.$root);
        this.themeTemplatesEnabled = EditorConfig.resolveThemeTemplatesEnabled(this.$root);
        this.developerJsonEnabled = EditorConfig.resolveDeveloperJsonEnabled(this.$root);
        this.addComponentsEnabled = EditorConfig.resolveAddComponentsEnabled(this.$root);
        this.jsonImportEnabled = EditorConfig.resolveJsonImportEnabled(this.$root);
        this.editorAssetVersion = typeof EditorConfig.resolveEditorAssetVersion === 'function'
            ? (EditorConfig.resolveEditorAssetVersion(this.$root) ?? '')
            : '';
        this.mediaLibraryApiUrl = typeof EditorConfig.resolveMediaLibraryApiUrl === 'function'
            ? EditorConfig.resolveMediaLibraryApiUrl(this.$root)
            : '/api/media';
        this.mediaLibraryCollection = typeof EditorConfig.resolveMediaLibraryCollection === 'function'
            ? EditorConfig.resolveMediaLibraryCollection(this.$root)
            : null;
        this.mediaUsableType = typeof EditorConfig.resolveMediaUsableType === 'function'
            ? (EditorConfig.resolveMediaUsableType(this.$root) ?? '')
            : '';
        this.mediaUsableId = typeof EditorConfig.resolveMediaUsableId === 'function'
            ? (EditorConfig.resolveMediaUsableId(this.$root) ?? '')
            : '';
        this.mediaUploadLanguage = typeof EditorConfig.resolveMediaUploadLanguage === 'function'
            ? (EditorConfig.resolveMediaUploadLanguage(this.$root) ?? '')
            : '';
        this.mediaUploadMaxFileSizeKb = typeof EditorConfig.resolveMediaUploadMaxFileSizeKb === 'function'
            ? EditorConfig.resolveMediaUploadMaxFileSizeKb(this.$root)
            : null;
        this.livewireHiddenInputId = String(this.$root?.parentElement?.dataset?.hiddenInputId ?? '').trim();
        this.templateSlug = resolveTemplateSlugFromRoot(this.$root);
        if (!this.themeTemplatesEnabled) {
            this.showToolbarTab = 'blocks';
        }

        // Lade Templates beim Initialisieren (Toolbar: Theme-Tab nur wenn erlaubt)
        try {
            const templatesModule = await loadTemplatesModule(this.editorAssetVersion);
            this.templates = templatesModule.getAllTemplates({
                allowThemeTemplates: this.themeTemplatesEnabled,
            });
            this.templates.sidebar = hardenSidebarTemplateExpressions(this.templates.sidebar);
            if (!this.themeTemplatesEnabled) {
                this.templates.blockToolbar = this.getBlocksOnlyToolbarTemplate();
            }
        } catch (error) {
            console.warn('Fehler beim Laden der Templates:', error);
            this.templates = {};
        }
        let importedInitialBlocks = false;
        const initialJson = EditorConfig.normalizeInitialJsonText(this.$root?.parentElement?.dataset?.blockJson ?? null);
        if (initialJson) {
            try {
                Storage.importJSON(
                    initialJson,
                    this.blocks,
                    this.blockIdCounter,
                    this.$nextTick.bind(this),
                    Utils.initAllBlockContents,
                    (newCounter) => {
                        this.blockIdCounter = newCounter;
                    }
                );
                this.clearElementCache();
                this.invalidateRenderCache();
                this.invalidateJSONDisplayCache();
                importedInitialBlocks = this.blocks.length > 0;
            } catch (error) {
                console.warn('Fehler beim Laden der Initial-JSON:', error);
                this.showNotification('Fehler beim Laden der Startdaten.', 'error');
            }
        } else if (this.$root?.parentElement?.dataset?.blockJson) {
            console.warn('Initial-JSON ist ungültig und wurde ignoriert.');
        }
        // Stelle sicher, dass Column-Blöcke die richtige Anzahl von Spalten haben (falls Blöcke bereits vorhanden sind)
        BlockManagement.ensureColumnStructure(this.blocks);

        // Start mit einem leeren Paragraph
        if (this.blocks.length === 0 && this.addComponentsEnabled) {
            this.addBlock('paragraph');
        }
        // Lade Themes beim Initialisieren (für feste templateSlug auch bei templates(false))
        if (this.themeTemplatesEnabled || this.templateSlug) {
            this.loadThemes(Boolean(this.templateSlug))
                .then(() => {
                    this.loadConfiguredTemplateBySlug(importedInitialBlocks);
                })
                .catch(err => console.error('Fehler beim Laden der Themes:', err));
        } else {
            this.themes = [];
        }
        // Initialisiere Block-Inhalte nach dem Rendering
        if (!importedInitialBlocks) {
            this.$nextTick(() => {
                this.initAllBlockContents();
            });
        }

        this.$nextTick(() => {
            this.syncLivewireState(true);
        });

        if (typeof this.initializeDynamicFeedSources === 'function') {
            this.initializeDynamicFeedSources();
        }
    },

    loadConfiguredTemplateBySlug(importedInitialBlocks = false) {
        if (!this.templateSlug || importedInitialBlocks) {
            return;
        }

        const hasNoBlocks = this.blocks.length === 0;
        const hasOnlyDefaultParagraph = this.blocks.length === 1
            && this.blocks[0]?.type === 'paragraph'
            && !String(this.blocks[0]?.content ?? '').trim();

        if (!hasNoBlocks && !hasOnlyDefaultParagraph) {
            return;
        }

        const configuredTheme = this.themes.find((theme) => {
            return typeof theme?.slug === 'string'
                && theme.slug.toLowerCase() === this.templateSlug.toLowerCase();
        });

        if (!configuredTheme?.name) {
            return;
        }

        this.loadTheme(configuredTheme.name, true);
    },
};
