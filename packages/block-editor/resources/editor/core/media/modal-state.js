export function getDefaultImageSettingsState() {
    return {
        imageSettingsBlockId: null,
        imageSettingsUrl: '',
        imageSettingsOriginalUrl: '',
        imageSettingsMediaUsables: [],
        imageSettingsOriginalMediaUsables: [],
        imageSettingsAlt: '',
        imageSettingsTitle: '',
        imageSettingsActiveTab: 'library'
    };
}

export function getDefaultVideoSettingsState() {
    return {
        videoSettingsBlockId: null,
        videoSettingsUrl: '',
        videoSettingsPoster: '',
        videoSettingsTitle: '',
        videoSettingsActiveTab: 'library'
    };
}

export function getDefaultEmbedSettingsState() {
    return {
        embedSettingsBlockId: null,
        embedSettingsUrl: '',
        embedSettingsTitle: ''
    };
}

export function getImageSettingsStateFromBlock(blockId, block) {
    return {
        imageSettingsBlockId: blockId,
        imageSettingsUrl: block.imageUrl || '',
        imageSettingsOriginalUrl: block.imageUrl || '',
        imageSettingsMediaUsables: Array.isArray(block.media_usables) ? [...block.media_usables] : [],
        imageSettingsOriginalMediaUsables: Array.isArray(block.media_usables) ? [...block.media_usables] : [],
        imageSettingsAlt: block.imageAlt || '',
        imageSettingsTitle: block.imageTitle || '',
        imageSettingsActiveTab: 'library'
    };
}

export function getVideoSettingsStateFromBlock(blockId, block) {
    return {
        videoSettingsBlockId: blockId,
        videoSettingsUrl: block.videoUrl || '',
        videoSettingsPoster: block.videoPoster || '',
        videoSettingsTitle: block.videoTitle || '',
        videoSettingsActiveTab: 'library'
    };
}

export function getEmbedSettingsStateFromBlock(blockId, block) {
    return {
        embedSettingsBlockId: blockId,
        embedSettingsUrl: block.embedUrl || '',
        embedSettingsTitle: block.embedTitle || ''
    };
}
