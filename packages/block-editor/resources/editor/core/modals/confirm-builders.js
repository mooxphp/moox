export function buildLoadThemeConfirmModal(themeName, callbacks) {
    return {
        title: 'Theme laden',
        message: `Möchten Sie das Theme "${themeName}" laden?`,
        onConfirm: callbacks.onConfirm,
        onCancel: callbacks.onCancel,
        onExtend: callbacks.onExtend,
        showExtend: true
    };
}

export function buildDeleteThemeConfirmModal(themeName, callbacks) {
    return {
        title: 'Theme löschen',
        message: `Möchten Sie das Theme "${themeName}" wirklich löschen?`,
        onConfirm: callbacks.onConfirm,
        onCancel: callbacks.onCancel
    };
}

export function buildImportedThemeConfirmModal(themeName, callbacks) {
    return {
        title: 'Theme laden',
        message: `Theme "${themeName}" erfolgreich importiert. Möchten Sie es jetzt laden?`,
        onCancel: callbacks.onCancel,
        onExtend: callbacks.onExtend,
        showExtend: true
    };
}

export function buildLinkFollowConfirmModal(linkData, callbacks) {
    const { linkUrl, linkText, linkTarget } = linkData;

    return {
        title: '🔗 Link öffnen?',
        message: `Möchten Sie dem Link folgen oder den Block bearbeiten?<br><br><strong>Link-Text:</strong> ${linkText}<br><strong>URL:</strong> ${linkUrl}`,
        onConfirm: callbacks.onConfirm,
        onCancel: callbacks.onCancel,
        onExtend: null,
        showExtend: false,
        showLinkFollow: true,
        linkFollowUrl: linkUrl,
        linkFollowTarget: linkTarget
    };
}
