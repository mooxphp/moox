/**
 * Code Block Component
 * Enthält alle Informationen für Code-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';

export const CodeBlock = {
    type: 'code',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.code,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'code',
        content: '',
        codeLanguage: 'plaintext',
        codeShowLineNumbers: false,
        codeShowCopyButton: true,
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    renderHTML(block, context = {}) {
        return this.renderCodeHTML('block', block);
    },
    
    renderChildHTML(child, context = {}) {
        return this.renderCodeHTML('child', child);
    },
    
    renderCodeHTML(scope, data) {
        const isChild = scope === 'child';
        const placeholder = this.options.placeholder || 'Code...';
        const addAfterCall = isChild
            ? "addChildAfter((column && column.id) || (block && block.id), childIndex, 'code')"
            : "addBlockAfter(block.id, 'code')";
        
        return `
            <div x-show="${scope}.type === 'code'">
                <div class="rounded-lg overflow-hidden border border-gray-800 bg-gray-900 text-green-200 font-mono text-sm">
                    <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-gray-800 bg-gray-950/70">
                        <span class="text-xs uppercase tracking-wide text-gray-300" x-text="${scope}.codeLanguage || 'plaintext'"></span>
                        <button
                            x-show="${scope}.codeShowCopyButton !== false"
                            type="button"
                            class="px-2 py-1 rounded text-xs bg-gray-800 text-gray-200 hover:bg-gray-700 transition-colors"
                            @click="copyCodeToClipboard(${scope}.id, $event)"
                        >Copy</button>
                    </div>
                    <div class="flex max-h-96 overflow-y-auto">
                        <div
                            x-show="${scope}.codeShowLineNumbers === true"
                            class="select-none px-3 py-4 border-r border-gray-800 text-gray-500 text-right"
                        >
                            <template x-for="line in Math.max(1, String(${scope}.content || '').split('\\n').length)" :key="line">
                                <div class="leading-6" x-text="line"></div>
                            </template>
                        </div>
                        <pre 
                            :data-block-id="${scope}.id"
                            :id="${scope}.htmlId || null"
                            :style="${scope}.style || ''"
                            :class="['block-placeholder flex-1 min-w-0 p-4 overflow-x-auto whitespace-pre-wrap break-words', ${scope}.classes || '']"
                        ><code 
                            :class="(${scope}.codeLanguage && ${scope}.codeLanguage !== 'plaintext') ? ('language-' + ${scope}.codeLanguage) : ''"
                            class="block whitespace-pre-wrap break-words leading-6"
                            contenteditable="true"
                            data-placeholder="${placeholder}"
                            x-init="$nextTick(() => initBlockContent($el, ${scope}, true))"
                            @input="updateBlockContent(${scope}.id, getCodeEditableText($event.target))"
                            @blur="commitBlockContent(${scope}.id, getCodeEditableText($event.target))"
                            @keydown.enter.exact.prevent="insertCodeNewLine(${scope}.id, $event)"
                            @keydown.shift.enter.prevent="${addAfterCall}"
                            @keydown.backspace="handleBackspace(${scope}.id, $event)"
                            @focus="initBlockContent($event.target, ${scope}, true)"
                        ></code></pre>
                    </div>
                </div>
            </div>
        `;
    },
    
    initialize(block, blockIdCounter) {
        if (typeof block.codeLanguage !== 'string' || block.codeLanguage.trim() === '') {
            block.codeLanguage = 'plaintext';
        }
        if (typeof block.codeShowLineNumbers !== 'boolean') {
            block.codeShowLineNumbers = false;
        }
        if (typeof block.codeShowCopyButton !== 'boolean') {
            block.codeShowCopyButton = true;
        }
        return block;
    },
    
    ensureInitialized(block, blockIdCounter) {
        if (typeof block.codeLanguage !== 'string' || block.codeLanguage.trim() === '') {
            block.codeLanguage = 'plaintext';
        }
        if (typeof block.codeShowLineNumbers !== 'boolean') {
            block.codeShowLineNumbers = false;
        }
        if (typeof block.codeShowCopyButton !== 'boolean') {
            block.codeShowCopyButton = true;
        }
        return block;
    },
    
    cleanup(block) {
        delete block.codeLanguage;
        delete block.codeShowLineNumbers;
        delete block.codeShowCopyButton;
        return block;
    },
    
    getSettingsHTML(block, context = {}) {
        const language = block.codeLanguage || 'plaintext';
        const showLineNumbers = block.codeShowLineNumbers === true;
        const showCopyButton = block.codeShowCopyButton !== false;
        
        return `
            <div class="pt-4 border-t border-gray-200 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Code-Sprache:</label>
                    <select
                        x-model="block.codeLanguage"
                        @change="block.updatedAt = new Date().toISOString()"
                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="plaintext" ${language === 'plaintext' ? 'selected' : ''}>Plain Text</option>
                        <option value="bash" ${language === 'bash' ? 'selected' : ''}>Bash</option>
                        <option value="javascript" ${language === 'javascript' ? 'selected' : ''}>JavaScript</option>
                        <option value="typescript" ${language === 'typescript' ? 'selected' : ''}>TypeScript</option>
                        <option value="php" ${language === 'php' ? 'selected' : ''}>PHP</option>
                        <option value="python" ${language === 'python' ? 'selected' : ''}>Python</option>
                        <option value="json" ${language === 'json' ? 'selected' : ''}>JSON</option>
                        <option value="html" ${language === 'html' ? 'selected' : ''}>HTML</option>
                        <option value="css" ${language === 'css' ? 'selected' : ''}>CSS</option>
                        <option value="sql" ${language === 'sql' ? 'selected' : ''}>SQL</option>
                        <option value="yaml" ${language === 'yaml' ? 'selected' : ''}>YAML</option>
                        <option value="markdown" ${language === 'markdown' ? 'selected' : ''}>Markdown</option>
                    </select>
                </div>
                <label class="flex items-center justify-between text-sm text-gray-700">
                    <span>Zeilennummern anzeigen</span>
                    <input
                        type="checkbox"
                        x-model="block.codeShowLineNumbers"
                        @change="block.updatedAt = new Date().toISOString()"
                        ${showLineNumbers ? 'checked' : ''}
                    />
                </label>
                <label class="flex items-center justify-between text-sm text-gray-700">
                    <span>Copy-Button anzeigen</span>
                    <input
                        type="checkbox"
                        x-model="block.codeShowCopyButton"
                        @change="block.updatedAt = new Date().toISOString()"
                        ${showCopyButton ? 'checked' : ''}
                    />
                </label>
            </div>
        `;
    },
    
    // Fokus-Verhalten: Standard-Implementierung für editierbare Blöcke
    focusable: true,
    focus(element, block) {
        if (!element) return false;
        // Für Code-Blöcke: Suche nach dem <code> Element innerhalb von <pre>
        const codeElement = element.querySelector('code') || element;
        if (codeElement.hasAttribute('contenteditable') && codeElement.getAttribute('contenteditable') === 'true') {
            // Cursor-Position nicht erzwingen, damit sie nicht jedes Mal ans Ende springt.
            codeElement.focus();
            return true;
        }
        return false;
    }
};
