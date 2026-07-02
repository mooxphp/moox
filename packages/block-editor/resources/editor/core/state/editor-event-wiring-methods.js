export const editorEventWiringMethods = {
    setupEditorEventListeners() {
        // Optimierte Event Listener mit Debouncing für Text-Selektion
        const handleTextSelectionDebounced = () => {
            if (this.textSelectionTimeout) {
                clearTimeout(this.textSelectionTimeout);
            }
            this.textSelectionTimeout = setTimeout(() => {
                this.handleTextSelection();
            }, 10);
        };

        const getEditorContainer = () => {
            if (this.editorContainerElement && document.contains(this.editorContainerElement)) {
                return this.editorContainerElement;
            }
            this.editorContainerElement = document.getElementById('editor-container');
            return this.editorContainerElement;
        };

        const mouseupHandler = () => handleTextSelectionDebounced();
        const keyupHandler = () => handleTextSelectionDebounced();

        document.addEventListener('mouseup', mouseupHandler, { passive: true });
        document.addEventListener('keyup', keyupHandler);

        // Speichere Event Listener für Cleanup
        this.eventListeners.push(
            { element: document, event: 'mouseup', handler: mouseupHandler },
            { element: document, event: 'keyup', handler: keyupHandler }
        );

        // Event Listener für Rechtsklick (Context-Menu)
        const contextmenuHandler = (e) => {
            const selectionDetails = this.getActiveSelectionDetails();
            if (!selectionDetails) {
                return;
            }

            const { range, selectedText } = selectionDetails;
            const editableElement = this.resolveEditableElementFromRange(range);
            if (!editableElement) {
                return;
            }

            const blockElement = editableElement.closest('[data-block-id]');
            if (!blockElement) {
                return;
            }

            const blockId = blockElement.getAttribute('data-block-id');
            const { block } = this.findBlockById(blockId);

            // Zeige Toolbar nur für Text-Blöcke
            if (block && block.type !== 'code' && block.type !== 'table' && block.type !== 'divider') {
                e.preventDefault();
                e.stopPropagation();

                // Positioniere Toolbar an Mausposition (fixed => ohne Scroll-Offsets)
                this.setFloatingToolbarPosition({
                    top: e.clientY,
                    left: e.clientX
                });

                // Speichere Selektion
                this.selectedText = selectedText;
                this.selectedRange = range.cloneRange();
                this.selectedBlockId = blockId;
                this.showFloatingToolbar = true;
            }
        };

        document.addEventListener('contextmenu', contextmenuHandler);
        this.eventListeners.push({ element: document, event: 'contextmenu', handler: contextmenuHandler });

        // Event Listener für Link-Klicks (Bearbeitung)
        const clickHandler = (e) => {
            if (!(e.target instanceof Element)) {
                return;
            }

            const target = e.target;

            // Prüfe ob ein Link angeklickt wurde (mit Ctrl/Cmd für Bearbeitung)
            const linkElement = target.closest('a[href]');
            if (linkElement && linkElement.closest('[data-block-id]')) {
                const blockElement = linkElement.closest('[data-block-id]');
                if (blockElement && blockElement.hasAttribute('contenteditable')) {
                    // Ctrl/Cmd + Klick = Bearbeitung, normaler Klick = Navigation verhindern
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.editLink(linkElement, blockElement);
                    } else {
                        // Normaler Klick: Verhindere Navigation, öffne Bearbeitung
                        e.preventDefault();
                        e.stopPropagation();
                        this.editLink(linkElement, blockElement);
                    }
                }
            }

            // Verstecke Toolbar bei Klick außerhalb
            if (this.showFloatingToolbar && !target.closest('.floating-toolbar')) {
                const clickedInsideToolbar = target.closest('[x-show*="showFloatingToolbar"]');
                const clickedInsideLinkModal = target.closest('[x-show*="showLinkModal"]');
                const clickedInsideEditable = target.closest('[contenteditable="true"]');

                if (!clickedInsideToolbar && !clickedInsideLinkModal && !clickedInsideEditable) {
                    this.showFloatingToolbar = false;
                }
            }

            // Deselect bei Klick außerhalb des Editors
            const editorContainer = getEditorContainer();
            if (editorContainer && !editorContainer.contains(target) && this.selectedBlockId !== null) {
                this.deselectAll();
            }
        };

        document.addEventListener('click', clickHandler);
        this.eventListeners.push({ element: document, event: 'click', handler: clickHandler });
    },
};
