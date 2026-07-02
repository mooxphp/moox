import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { renderJSONBlocks } from '../../resources/editor/core/render/json-renderer.js';
import { ChildManagement } from '../../resources/editor/core/blocks/management.js';
import { Utils } from '../../resources/editor/core/utils/index.js';
import { ToggleListBlock } from '../../resources/editor/components/blocks/layout/toggle-list.js';

function createMixedNestedBlocks() {
    return [
        {
            id: 'root-group',
            type: 'group',
            children: [
                {
                    id: 'col-root-group-0',
                    type: 'column',
                    children: [
                        {
                            id: 'toggle-a',
                            type: 'toggleList',
                            children: [
                                {
                                    id: 'link-a',
                                    type: 'link',
                                    children: [
                                        {
                                            id: 'toggle-b',
                                            type: 'toggleList',
                                            children: [
                                                { id: 'leaf-paragraph', type: 'paragraph', content: 'Leaf' }
                                            ]
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                }
            ]
        },
        {
            id: 'root-tabs',
            type: 'tabs',
            tabsData: {
                activeTabId: 'tab-1',
                items: [
                    {
                        id: 'tab-1',
                        title: 'Tab 1',
                        children: [
                            {
                                id: 'toggle-tab',
                                type: 'toggleList',
                                children: [{ id: 'tab-leaf', type: 'heading2', content: 'Nested heading' }]
                            }
                        ]
                    }
                ]
            }
        },
        {
            id: 'root-accordion',
            type: 'accordion',
            accordionData: {
                behavior: 'single',
                items: [
                    {
                        id: 'acc-1',
                        question: 'Q1',
                        answer: 'A1',
                        expanded: true,
                        children: [
                            {
                                id: 'toggle-acc',
                                type: 'toggleList',
                                children: [{ id: 'acc-leaf', type: 'quote', content: 'Nested quote' }]
                            }
                        ]
                    }
                ]
            }
        },
        {
            id: 'root-table',
            type: 'table',
            tableData: {
                rows: 1,
                cols: 1,
                cells: [
                    [
                        {
                            id: 'cell-1',
                            content: '',
                            blocks: [
                                {
                                    id: 'toggle-cell',
                                    type: 'toggleList',
                                    children: [{ id: 'cell-leaf', type: 'code', content: 'const x = 1;' }]
                                }
                            ],
                            merged: false,
                            colspan: 1,
                            rowspan: 1
                        }
                    ]
                ]
            }
        }
    ];
}

function maxDepth(blocks) {
    const walk = (block, depth) => {
        const nested = [];

        if (Array.isArray(block.children)) {
            nested.push(...block.children);
        }

        if (Array.isArray(block.tabsData?.items)) {
            for (const item of block.tabsData.items) {
                if (Array.isArray(item?.children)) {
                    nested.push(...item.children);
                }
            }
        }

        if (Array.isArray(block.accordionData?.items)) {
            for (const item of block.accordionData.items) {
                if (Array.isArray(item?.children)) {
                    nested.push(...item.children);
                }
            }
        }

        if (Array.isArray(block.tableData?.cells)) {
            for (const row of block.tableData.cells) {
                if (!Array.isArray(row)) {
                    continue;
                }
                for (const cell of row) {
                    if (Array.isArray(cell?.blocks)) {
                        nested.push(...cell.blocks);
                    }
                }
            }
        }

        if (nested.length === 0) {
            return depth;
        }

        return Math.max(...nested.map((child) => walk(child, depth + 1)));
    };

    if (!Array.isArray(blocks) || blocks.length === 0) {
        return 0;
    }

    return Math.max(...blocks.map((block) => walk(block, 1)));
}

describe('editor nesting depth', () => {
    it('toggleList child rendering nutzt stabilen componentBlock scope', () => {
        const html = ToggleListBlock.renderChildHTML({
            id: 'child-toggle',
            type: 'toggleList',
            children: [],
            expanded: true,
            content: '',
        }, {});

        assert.match(html, /x-data="\{ componentBlock: child \}"/);
        assert.match(html, /\(childItem, childIndex\) in \(componentBlock\.children \|\| \[\]\)/);
        assert.ok(!html.includes('(child, childIndex) in (child.children || [])'));
    });

    it('renderJSONBlocks behält tiefe verschachtelung für mehrere Component-Varianten', () => {
        const rendered = renderJSONBlocks(createMixedNestedBlocks(), 0);
        const depth = maxDepth(rendered);

        assert.ok(depth >= 5, `Erwartete Tiefe >= 5, erhalten: ${depth}`);
        assert.ok(Utils.findBlockById(rendered, 'toggle-b').block, 'Tiefer Toggle-Block nicht gefunden');
        assert.ok(Utils.findBlockById(rendered, 'toggle-tab').block, 'Toggle in Tabs nicht gefunden');
        assert.ok(Utils.findBlockById(rendered, 'toggle-acc').block, 'Toggle in Accordion nicht gefunden');
        assert.ok(Utils.findBlockById(rendered, 'toggle-cell').block, 'Toggle in Table-Cell nicht gefunden');
    });

    it('addChild und addChildAfter funktionieren in tiefen Varianten (group, tabs, accordion, table)', () => {
        const blocks = renderJSONBlocks(createMixedNestedBlocks(), 0);
        let blockIdCounter = 3000;

        const parentIds = ['toggle-b', 'toggle-tab', 'toggle-acc', 'toggle-cell'];

        for (const parentId of parentIds) {
            const before = Utils.findBlockById(blocks, parentId).block?.children?.length ?? 0;
            blockIdCounter += 1;
            const child = ChildManagement.addChild(blocks, parentId, blockIdCounter, 'paragraph');
            assert.ok(child, `addChild lieferte null für ${parentId}`);

            const after = Utils.findBlockById(blocks, parentId).block?.children?.length ?? 0;
            assert.equal(after, before + 1, `Kinderzahl wurde für ${parentId} nicht erhöht`);
        }

        const targetParentId = 'toggle-b';
        const beforeInsertLength = Utils.findBlockById(blocks, targetParentId).block?.children?.length ?? 0;
        const insertAfterIndex = beforeInsertLength - 1;

        blockIdCounter += 1;
        const inserted = ChildManagement.addChildAfter(
            blocks,
            targetParentId,
            insertAfterIndex,
            blockIdCounter,
            'quote'
        );

        assert.ok(inserted, 'addChildAfter lieferte null');
        const afterInsert = Utils.findBlockById(blocks, targetParentId).block?.children ?? [];
        assert.equal(afterInsert.length, beforeInsertLength + 1);
        assert.equal(afterInsert.at(-1)?.id, inserted.id);
        assert.equal(afterInsert.at(-1)?.type, 'quote');
    });
});
