/**
 * Block Components Index
 * Exportiert alle Block-Komponenten zentral
 * 
 * WICHTIG: Alle Block-Typen aus BLOCK_TYPES müssen hier vorhanden sein!
 */
import { ParagraphBlock } from './text/paragraph.js';
import { ImageBlock } from './media/image.js';
import { Heading1Block, Heading2Block, Heading3Block, Heading4Block, Heading5Block, Heading6Block } from './text/heading.js';
import { CodeBlock } from './text/code.js';
import { QuoteBlock } from './text/quote.js';
import { CalloutBlock } from './text/callout.js';
import { DividerBlock } from './layout/divider.js';
import { TwoColumnBlock } from './layout/two-column.js';
import { ThreeColumnBlock } from './layout/three-column.js';
import { GroupBlock } from './layout/group.js';
import { TableBlock } from './data/table.js';
import { TabsBlock } from './data/tabs.js';
import { AccordionBlock } from './data/accordion.js';
import { ChecklistBlock } from './text/checklist.js';
import { ListBlock } from './text/list.js';
import { LinkBlock } from './layout/link.js';
import { ToggleListBlock } from './layout/toggle-list.js';
import { VideoBlock } from './media/video.js';
import { EmbedBlock } from './media/embed.js';
import { BLOCK_TYPES } from '../block-types.js';

// Exportiere alle Block-Komponenten als Objekt
// Diese müssen mit BLOCK_TYPES in block-types.js übereinstimmen!
export const BlockComponents = {
    paragraph: ParagraphBlock,
    heading1: Heading1Block,
    heading2: Heading2Block,
    heading3: Heading3Block,
    heading4: Heading4Block,
    heading5: Heading5Block,
    heading6: Heading6Block,
    code: CodeBlock,
    quote: QuoteBlock,
    callout: CalloutBlock,
    divider: DividerBlock,
    twoColumn: TwoColumnBlock,
    threeColumn: ThreeColumnBlock,
    group: GroupBlock,
    table: TableBlock,
    tabs: TabsBlock,
    accordion: AccordionBlock,
    image: ImageBlock,
    video: VideoBlock,
    embed: EmbedBlock,
    checklist: ChecklistBlock,
    list: ListBlock,
    link: LinkBlock,
    toggleList: ToggleListBlock
};

const INTERNAL_ONLY_TYPES = new Set(['column']);

export function validateBlockComponentRegistry() {
    const blockTypeKeys = Object.keys(BLOCK_TYPES).filter((type) => !INTERNAL_ONLY_TYPES.has(type));
    const componentKeys = Object.keys(BlockComponents);

    const missingTypes = blockTypeKeys.filter((type) => !BlockComponents[type]);
    const extraTypes = componentKeys.filter((type) => !BLOCK_TYPES[type] && !INTERNAL_ONLY_TYPES.has(type));

    return {
        isValid: missingTypes.length === 0 && extraTypes.length === 0,
        missingTypes,
        extraTypes
    };
}

const registryValidation = validateBlockComponentRegistry();
if (!registryValidation.isValid) {
    console.warn('Block registry mismatch detected:', registryValidation);
}

/**
 * Gibt die passende Block-Komponente für einen Block-Typ zurück
 */
export function getBlockComponent(blockType) {
    if (!BlockComponents[blockType]) {
        // Interne Typen wie "column" sind rein strukturell und haben keine eigene Komponente
        if (INTERNAL_ONLY_TYPES.has(blockType)) {
            return BlockComponents.paragraph;
        }
        console.warn(`Unknown block type "${blockType}", falling back to paragraph.`);
    }
    return BlockComponents[blockType] || BlockComponents.paragraph;
}

/**
 * Ruft die Initialisierungsfunktion für einen Block-Typ auf
 */
export function initializeBlock(block, blockIdCounter) {
    const component = getBlockComponent(block.type);
    return component.initialize(block, blockIdCounter);
}

/**
 * Stellt sicher, dass ein Block initialisiert ist (für geladene Blöcke)
 */
export function ensureBlockInitialized(block, blockIdCounter) {
    const component = getBlockComponent(block.type);
    return component.ensureInitialized(block, blockIdCounter);
}

/**
 * Führt Cleanup für einen Block durch (beim Typ-Wechsel)
 */
export function cleanupBlock(block, oldType) {
    if (oldType && BlockComponents[oldType]) {
        const component = BlockComponents[oldType];
        component.cleanup(block);
    }
    return block;
}

