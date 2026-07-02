// Keine statischen Imports – alles wird per import() geladen, damit
// Fehler während der Modul-Auswertung (z. B. in state/editor oder editor-shell)
// im gleichen try/catch abgefangen werden können.
// cache-bust-marker: 20260423-image-scope-fix-2

function ensureModalHelpers() {
    if (window.modalHelpers) {
        return;
    }

    window.modalHelpers = {
        openModal() {
            if (document.body) {
                document.body.style.overflow = 'hidden';
            }
        },
        closeModal() {
            if (document.body) {
                document.body.style.overflow = '';
            }
        }
    };
}

async function resolveInitialBlocksJson(root) {
    const inlineJson = root?.dataset?.blockJson;
    if (inlineJson && inlineJson.trim()) {
        return inlineJson.trim();
    }
    return null;
}

async function mountEditor(root, getEditorShellTemplate) {
    if (!root) {
        return;
    }

    const initialJson = await resolveInitialBlocksJson(root);
    if (initialJson) {
        root.dataset.blockJson = initialJson;
    }

    root.innerHTML = getEditorShellTemplate();

    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
        window.Alpine.initTree(root);
    }
}

function showStartupError(rootElement, error) {
    console.error('Block-Editor: Fehler beim Starten', error);
    if (!rootElement || !rootElement.appendChild) return;
    rootElement.innerHTML = `
        <div class="p-6 max-w-xl mx-auto mt-8 rounded-lg border border-red-200 bg-red-50 text-red-800" role="alert">
            <h2 class="text-lg font-semibold mb-2">Editor konnte nicht geladen werden</h2>
            <p class="text-sm mb-3">Ein unerwarteter Fehler ist aufgetreten. Bitte lade die Seite neu oder prüfe die Konsole (F12) für Details.</p>
            <pre class="text-xs bg-red-100 p-3 rounded overflow-auto max-h-32">${escapeHtml(String(error?.message || error))}</pre>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function resolveEditorAssetVersion() {
    const root = document.querySelector('[data-editor-instance]');
    const rootVersion = root?.dataset?.editorAssetVersion;
    if (typeof rootVersion === 'string' && rootVersion.trim() !== '') {
        return rootVersion.trim();
    }

    const scriptSrc = document.currentScript?.src;
    if (typeof scriptSrc === 'string' && scriptSrc.trim() !== '') {
        try {
            const parsed = new URL(scriptSrc, window.location.origin);
            const queryVersion = parsed.searchParams.get('v');
            if (typeof queryVersion === 'string' && queryVersion.trim() !== '') {
                return queryVersion.trim();
            }
        } catch (_error) {
            // ignore and fallback
        }
    }

    return String(Date.now());
}

(async () => {
    try {
        const version = resolveEditorAssetVersion();
        const editorStateModule = await import(`../state/editor.js?v=${version}`);
        if (typeof editorStateModule.ensureEditorState === 'function') {
            await editorStateModule.ensureEditorState(version);
        }
        // Cache-bust, damit Browser-Module-Caching nicht alte Shell-Versionen verwendet.
        const editorShellModule = await import(`./editor-shell.js?v=${version}`);
        const getEditorShellTemplate = editorShellModule.getEditorShellTemplate;

        ensureModalHelpers();

        const roots = document.querySelectorAll('[data-editor-instance]');
        if (!roots.length) {
            console.warn('Block-Editor: Kein Editor-Root mit data-editor-instance gefunden.');
        }

        for (const root of roots) {
            // eslint-disable-next-line no-await-in-loop
            await mountEditor(root, getEditorShellTemplate);
        }
    } catch (error) {
        const fallbackRoot = document.querySelector('[data-editor-instance]');
        showStartupError(fallbackRoot, error);
    }
})();

document.addEventListener('alpine:init', () => {
    try {
        ensureModalHelpers();
    } catch (error) {
        console.error('Block-Editor: Fehler in alpine:init', error);
    }
});
