export function createBlockManagement({
    utils,
    blockTypes,
    initializeBlock,
    ensureBlockInitialized,
    cleanupBlock
}) {
    function updateBlockById(blocks, blockId, mutate) {
        const { block } = utils.findBlockById(blocks, blockId);
        if (!block) {
            return null;
        }

        const shouldPersist = mutate(block);
        if (shouldPersist === false) {
            return null;
        }

        block.updatedAt = new Date().toISOString();
        return block;
    }

    function updateTypedBlockById(blocks, blockId, expectedType, mutate) {
        return updateBlockById(blocks, blockId, (block) => {
            if (block.type !== expectedType) {
                return false;
            }

            mutate(block);
            return true;
        });
    }

    function updateTypedBlockField(blocks, blockId, expectedType, field, value) {
        return updateTypedBlockById(blocks, blockId, expectedType, (block) => {
            block[field] = value;
        });
    }

    return {
        createBlock(blockIdCounter, type, content = '') {
            const block = {
                id: utils.generateId(blockIdCounter),
                type: type,
                content: content,
                style: '',
                classes: '',
                htmlId: '',
                createdAt: new Date().toISOString()
            };

            // Dynamische Initialisierung über Block-Komponenten
            initializeBlock(block, blockIdCounter);

            // Initialize children array ONLY for container blocks
            // Spalten werden später von ensureColumnStructure() erstellt
            if (blockTypes.isContainerBlock(type)) {
                if (!block.children) {
                    block.children = [];
                }
            }
            // Non-container blocks should NOT have children array

            return block;
        },

        addBlock(blocks, selectedBlockId, blockIdCounter, type, content = '') {
            const block = this.createBlock(blockIdCounter, type, content);

            if (!selectedBlockId) {
                blocks.push(block);
                return block;
            }

            const { block: selectedBlock, parent } = utils.findBlockById(blocks, selectedBlockId);
            if (!selectedBlock) {
                blocks.push(block);
                return block;
            }

            // Wenn der ausgewählte Block ein Child ist, im selben Parent einfügen
            if (parent && Array.isArray(parent.children)) {
                const childIndex = parent.children.findIndex(child => child.id === selectedBlockId);
                if (childIndex !== -1) {
                    parent.children.splice(childIndex + 1, 0, block);
                    parent.updatedAt = new Date().toISOString();
                    return block;
                }
            }

            // Standard: im Haupt-Block-Array direkt nach dem ausgewählten Block einfügen
            const index = blocks.findIndex(b => b.id === selectedBlockId);
            if (index !== -1) {
                blocks.splice(index + 1, 0, block);
            } else {
                blocks.push(block);
            }

            return block;
        },

        addBlockAfter(blocks, blockId, blockIdCounter, type = 'paragraph') {
            const index = blocks.findIndex(b => b.id === blockId);
            const block = this.createBlock(blockIdCounter, type, '');
            blocks.splice(index + 1, 0, block);
            return block;
        },

        updateBlockContent(blocks, blockId, content) {
            updateBlockById(blocks, blockId, (block) => {
                block.content = content;
            });
        },

        deleteBlock(blocks, blockId) {
            if (!blockId) {
                return { deleted: false, newSelectedBlockId: null };
            }

            // 1) Versuche zuerst, im Wurzel-Array zu löschen (Abwärtskompatibilität)
            const rootIndex = blocks.findIndex((b) => b.id === blockId);
            if (rootIndex !== -1) {
                blocks.splice(rootIndex, 1);

                let newSelectedBlockId = null;
                if (blocks.length > 0) {
                    const nextIndex = rootIndex > 0 ? rootIndex - 1 : 0;
                    newSelectedBlockId = blocks[nextIndex]?.id ?? null;
                }

                return { deleted: true, newSelectedBlockId };
            }

            // 2) Finde Block rekursiv (Child, Spalten etc.)
            const { block, parent } = utils.findBlockById(blocks, blockId);
            if (!block || !parent || !Array.isArray(parent.children)) {
                return { deleted: false, newSelectedBlockId: null };
            }

            const childIndex = parent.children.findIndex((child) => child.id === blockId);
            if (childIndex === -1) {
                return { deleted: false, newSelectedBlockId: null };
            }

            parent.children.splice(childIndex, 1);
            parent.updatedAt = new Date().toISOString();

            let newSelectedBlockId = null;

            const resolveParentSelectionId = () => {
                if (parent?.type || !parent?.id) {
                    return parent?.id ?? null;
                }

                const owner = blocks.find((candidate) =>
                    (
                        candidate?.type === 'tabs' &&
                        Array.isArray(candidate?.tabsData?.items) &&
                        candidate.tabsData.items.some((tab) => tab?.id === parent.id)
                    ) || (
                        candidate?.type === 'accordion' &&
                        Array.isArray(candidate?.accordionData?.items) &&
                        candidate.accordionData.items.some((item) => item?.id === parent.id)
                    )
                );

                return owner?.id ?? parent?.id ?? null;
            };

            if (parent.children.length === 0) {
                // Wenn keine Kinder mehr vorhanden sind, children entfernen und Parent auswählen
                delete parent.children;
                newSelectedBlockId = resolveParentSelectionId();
            } else {
                // Anderen Sibling auswählen (gleiche oder vorherige Position)
                const nextIndex = Math.min(childIndex, parent.children.length - 1);
                newSelectedBlockId = parent.children[nextIndex]?.id ?? resolveParentSelectionId();
            }

            return { deleted: true, newSelectedBlockId };
        },

        changeBlockType(blocks, blockId, newType, blockIdCounter = 0) {
            const { block } = utils.findBlockById(blocks, blockId);
            if (block) {
                const oldContent = block.content;
                const oldType = block.type;

                // Cleanup alte Block-Daten
                if (oldType !== newType) {
                    cleanupBlock(block, oldType);
                }

                block.type = newType;
                block.content = oldContent;
                block.updatedAt = new Date().toISOString();

                // Dynamische Initialisierung für neuen Block-Typ
                initializeBlock(block, blockIdCounter);

                // Clean up column structure if changing away from column-like layouts
                if (blockTypes.isColumnLikeBlock(oldType) && !blockTypes.isColumnLikeBlock(newType)) {
                    delete block.children;
                }

                // Initialize children array for container blocks (wenn noch nicht vorhanden)
                if (blockTypes.isContainerBlock(newType) && !block.children) {
                    block.children = [];
                }

                // Spalten werden später von ensureColumnStructure() erstellt/korrigiert
            }
        },

        updateBlockStyle(blocks, blockId, style) {
            updateBlockById(blocks, blockId, (block) => {
                block.style = style;
            });
        },

        clearBlockStyle(blocks, blockId) {
            updateBlockById(blocks, blockId, (block) => {
                block.style = '';
            });
        },

        updateBlockClasses(blocks, blockId, classes) {
            updateBlockById(blocks, blockId, (block) => {
                block.classes = classes;
            });
        },

        clearBlockClasses(blocks, blockId) {
            updateBlockById(blocks, blockId, (block) => {
                block.classes = '';
            });
        },

        updateBlockHtmlId(blocks, blockId, htmlId) {
            updateBlockById(blocks, blockId, (block) => {
                block.htmlId = htmlId;
            });
        },

        clearBlockHtmlId(blocks, blockId) {
            updateBlockById(blocks, blockId, (block) => {
                block.htmlId = '';
            });
        },

        moveBlock(blocks, index, direction) {
            if (direction === 'up' && index > 0) {
                [blocks[index - 1], blocks[index]] = [blocks[index], blocks[index - 1]];
            } else if (direction === 'down' && index < blocks.length - 1) {
                [blocks[index], blocks[index + 1]] = [blocks[index + 1], blocks[index]];
            }
        },

        updateImageUrl(blocks, blockId, imageUrl) {
            updateTypedBlockField(blocks, blockId, 'image', 'imageUrl', imageUrl);
        },

        updateImageAlt(blocks, blockId, imageAlt) {
            updateTypedBlockField(blocks, blockId, 'image', 'imageAlt', imageAlt);
        },

        updateImageTitle(blocks, blockId, imageTitle) {
            updateTypedBlockField(blocks, blockId, 'image', 'imageTitle', imageTitle);
        },

        updateImageMediaUsables(blocks, blockId, mediaUsables) {
            updateTypedBlockField(
                blocks,
                blockId,
                'image',
                'media_usables',
                Array.isArray(mediaUsables) ? mediaUsables : []
            );
        },

        updateVideoUrl(blocks, blockId, videoUrl) {
            updateTypedBlockField(blocks, blockId, 'video', 'videoUrl', videoUrl);
        },

        updateVideoPoster(blocks, blockId, videoPoster) {
            updateTypedBlockField(blocks, blockId, 'video', 'videoPoster', videoPoster);
        },

        updateVideoTitle(blocks, blockId, videoTitle) {
            updateTypedBlockField(blocks, blockId, 'video', 'videoTitle', videoTitle);
        },

        updateEmbedUrl(blocks, blockId, embedUrl) {
            updateTypedBlockField(blocks, blockId, 'embed', 'embedUrl', embedUrl);
        },

        updateEmbedTitle(blocks, blockId, embedTitle) {
            updateTypedBlockField(blocks, blockId, 'embed', 'embedTitle', embedTitle);
        },

        // Stellt sicher, dass Column-Blöcke die richtige Anzahl von Spalten haben
        ensureColumnStructure(blocks) {
            function fixBlockColumns(block) {
                if (!block) return;

                // Stelle sicher, dass Block initialisiert ist (dynamisch über Block-Komponenten)
                const parsedCounter = Number.parseInt(String(block.id), 10);
                const blockIdCounter = Number.isFinite(parsedCounter) ? parsedCounter : 0;
                ensureBlockInitialized(block, blockIdCounter);

                // Für Container-Blöcke: Stelle sicher, dass die richtige Struktur vorhanden ist
                if (blockTypes.isContainerBlock(block.type)) {
                    const columnCount = blockTypes.getColumnCount(block.type);
                    if (columnCount > 0) {
                        // Column-Blöcke (twoColumn, threeColumn)
                        if (!block.children) {
                            block.children = [];
                        }

                        // Stelle sicher, dass genau die richtige Anzahl von Spalten vorhanden ist
                        while (block.children.length < columnCount) {
                            const columnIndex = block.children.length;
                            block.children.push({
                                id: `col-${block.id}-${columnIndex}`,
                                type: 'column',
                                content: '',
                                style: '',
                                classes: '',
                                children: [],
                                createdAt: new Date().toISOString()
                            });
                        }

                        // Überschüssige Spalten NICHT entfernen (dynamische Spaltenanzahl erlaubt)
                    } else if (block.type === 'table') {
                        // Table-Blöcke: children sollte ein Array sein (für zukünftige Erweiterungen)
                        if (!block.children) {
                            block.children = [];
                        }
                    } else if (block.type === 'checklist' || block.type === 'list') {
                        // Checklist/List-Blöcke: sollten KEINE children haben (verwenden Daten-Items)
                        if (block.children && block.children.length > 0) {
                            delete block.children;
                        }
                    }
                } else {
                    // Entferne children Array von nicht-Container-Blöcken
                    // image, divider, checklist, list und andere void/non-container Blöcke sollten KEINE children haben
                    if (block.children && block.children.length > 0) {
                        // Nur Container-Blöcke sollten children haben
                        delete block.children;
                    }
                }

                // Rekursiv für verschachtelte Blöcke
                if (block.children && Array.isArray(block.children)) {
                    block.children.forEach(child => {
                        // Prüfe ob es eine Spalte ist (dann rekursiv für deren children)
                        if (child.type === 'column') {
                            // Column-Blöcke: rekursiv für deren children
                            if (child.children && Array.isArray(child.children)) {
                                child.children.forEach(grandChild => {
                                    fixBlockColumns(grandChild);
                                });
                            }
                        } else {
                            // Normales Child (kann auch ein Container-Block sein) - rekursiv behandeln
                            fixBlockColumns(child);
                        }
                    });
                }
            }

            blocks.forEach(block => {
                fixBlockColumns(block);
            });
        }
    };
}
