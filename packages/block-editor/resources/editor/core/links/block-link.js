export function applyLinkBlockSettings(block, { url, target, linkText }) {
    if (!block || block.type !== 'link') {
        return null;
    }

    block.linkUrl = url;
    block.linkTarget = target || '_blank';

    const trimmedLinkText = typeof linkText === 'string' ? linkText.trim() : '';
    if (trimmedLinkText !== '') {
        block.linkText = trimmedLinkText;
        block.content = trimmedLinkText;
    } else {
        block.linkText = '';
        if (!block.content || block.content.trim() === '') {
            block.content = url;
        }
    }

    return {
        displayText: block.linkText || block.content || url
    };
}
