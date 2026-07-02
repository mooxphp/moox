export function getDefaultLinkModalState(type = null) {
    return {
        type: type,
        blockId: null,
        url: '',
        target: '_blank',
        text: '',
        linkText: '',
        element: null,
        range: null,
        markerIds: null
    };
}

export function getDefaultConfirmModalState() {
    return {
        title: '',
        message: '',
        onConfirm: null,
        onCancel: null,
        onExtend: null,
        showExtend: false,
        showLinkFollow: false,
        linkFollowUrl: null,
        linkFollowTarget: '_self'
    };
}
