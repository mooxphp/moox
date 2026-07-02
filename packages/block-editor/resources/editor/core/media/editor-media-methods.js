import { BlockManagement } from '../blocks/management.js';
import { getRequiredTrimmedUrl } from '../input/url-input.js';
import { normalizeEmbedUrl } from '../utils/embed-url.js';
import { pickFile } from './file-upload.js';
import {
    getDefaultImageSettingsState,
    getDefaultVideoSettingsState,
    getDefaultEmbedSettingsState,
    getImageSettingsStateFromBlock,
    getVideoSettingsStateFromBlock,
    getEmbedSettingsStateFromBlock
} from './modal-state.js';

function resolveFirstString(values) {
    for (const value of values) {
        if (typeof value !== 'string') {
            continue;
        }
        const normalized = value.trim();
        if (normalized !== '') {
            return normalized;
        }
    }

    return '';
}

function resolveMediaItemUrl(item) {
    return resolveFirstString([
        item?.url,
        item?.original_url,
        item?.originalUrl,
        item?.full_url,
        item?.fullUrl,
    ]);
}

function resolveFallbackMediaItemUrl(item) {
    return resolveFirstString([
        item?.src,
        item?.path,
        item?.attributes?.url,
        item?.attributes?.original_url
    ]);
}

function resolveMediaPreviewUrl(item, fallbackUrl = '') {
    return resolveFirstString([
        item?.preview_url,
        item?.previewUrl,
        item?.thumbnail_url,
        item?.thumbnailUrl,
        item?.thumb_url,
        item?.thumbUrl,
        item?.poster_url,
        item?.posterUrl,
        item?.conversions?.preview,
        item?.conversions?.thumb,
        fallbackUrl
    ]);
}

function resolveMediaItemTitle(item, fallbackUrl = '') {
    return resolveFirstString([
        item?.title,
        item?.name,
        item?.file_name,
        item?.fileName,
        item?.filename,
        item?.alt,
        fallbackUrl
    ]);
}

function resolveMediaItemCollectionId(item) {
    const candidates = [
        item?.collection?.id,
        item?.media_collection_id,
        item?.mediaCollectionId
    ];

    for (const candidate of candidates) {
        const parsed = Number(candidate);
        if (Number.isInteger(parsed) && parsed > 0) {
            return String(parsed);
        }
    }

    return '';
}

function resolvePositiveIntegerString(value) {
    const parsed = Number(value);
    if (Number.isInteger(parsed) && parsed > 0) {
        return String(parsed);
    }

    return '';
}

function normalizeMediaUsableEntry(entry) {
    if (!entry || typeof entry !== 'object') {
        return null;
    }

    const mediaId = resolvePositiveIntegerString(entry.media_id);
    const mediaUsableType = resolveFirstString([entry.media_usable_type]);
    const mediaUsableId = resolvePositiveIntegerString(entry.media_usable_id);

    if (mediaId === '') {
        return null;
    }

    return {
        media_id: mediaId,
        media_usable_type: mediaUsableType,
        media_usable_id: mediaUsableId
    };
}

function parseMediaUploadError(payload, fallbackMessage) {
    const firstValidationError = payload?.errors && typeof payload.errors === 'object'
        ? Object.values(payload.errors).flat().find((message) => typeof message === 'string' && message.trim() !== '')
        : '';

    return resolveFirstString([
        firstValidationError,
        payload?.message,
        fallbackMessage
    ]);
}

function normalizeMediaUploadLocale(locale) {
    const normalized = String(locale ?? '')
        .trim()
        .replace('-', '_');

    if (normalized === '') {
        return '';
    }

    if (/^[a-z]{2}$/i.test(normalized)) {
        const lower = normalized.toLowerCase();
        return `${lower}_${lower.toUpperCase()}`;
    }

    if (/^[a-z]{2}_[a-z]{2}$/i.test(normalized)) {
        const [language, country] = normalized.split('_');
        return `${language.toLowerCase()}_${country.toUpperCase()}`;
    }

    return normalized;
}

function resolveCsrfToken() {
    if (typeof document === 'undefined') {
        return '';
    }

    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (typeof metaToken === 'string' && metaToken.trim() !== '') {
        return metaToken.trim();
    }

    const inputToken = document.querySelector('input[name="_token"]')?.value;
    if (typeof inputToken === 'string' && inputToken.trim() !== '') {
        return inputToken.trim();
    }

    return '';
}

function formatFileSize(bytes) {
    const size = Number(bytes);
    if (!Number.isFinite(size) || size <= 0) {
        return '';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = size;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    return `${value.toFixed(value >= 100 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function uploadMediaWithProgress({
    url,
    formData,
    csrfToken,
    onProgress
}) {
    return new Promise((resolve, reject) => {
        const request = new XMLHttpRequest();
        request.open('POST', url, true);
        request.withCredentials = true;
        request.responseType = 'json';
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (csrfToken !== '') {
            request.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }

        request.upload.onprogress = (event) => {
            if (!event.lengthComputable || typeof onProgress !== 'function') {
                return;
            }

            const percent = Math.min(100, Math.max(0, Math.round((event.loaded / event.total) * 100)));
            onProgress(percent);
        };

        request.onerror = () => {
            reject(new Error('Netzwerkfehler'));
        };

        request.onload = () => {
            const payload = request.response && typeof request.response === 'object'
                ? request.response
                : (() => {
                    try {
                        return JSON.parse(request.responseText || '{}');
                    } catch (_error) {
                        return {};
                    }
                })();

            resolve({
                ok: request.status >= 200 && request.status < 300,
                status: request.status,
                payload
            });
        };

        request.send(formData);
    });
}

export function buildMediaLibraryUrl(baseUrl, search, collection, type, page = 1, perPage = 25) {
    if (typeof baseUrl !== 'string' || baseUrl.trim() === '') {
        return null;
    }

    const normalizedBaseUrl = baseUrl.trim();
    const origin = typeof window !== 'undefined' && window.location ? window.location.origin : 'http://localhost';
    const targetUrl = new URL(normalizedBaseUrl, origin);

    if (typeof search === 'string') {
        targetUrl.searchParams.set('search', search);
    } else {
        targetUrl.searchParams.set('search', '');
    }

    if (typeof collection === 'string' && collection.trim() !== '') {
        const parsedCollection = Number(collection.trim());
        if (Number.isInteger(parsedCollection) && parsedCollection > 0) {
            targetUrl.searchParams.set('collection', String(parsedCollection));
        }
    }

    if (typeof type === 'string' && type.trim() !== '') {
        targetUrl.searchParams.set('type', type.trim().toLowerCase());
    }

    const normalizedPage = Number.isInteger(Number(page)) && Number(page) > 0 ? String(Number(page)) : '1';
    targetUrl.searchParams.set('page', normalizedPage);

    const normalizedPerPage = Number.isInteger(Number(perPage)) && Number(perPage) > 0
        ? String(Number(perPage))
        : '25';
    targetUrl.searchParams.set('per_page', normalizedPerPage);

    return targetUrl.toString();
}

export function buildMediaUploadUrl(baseUrl) {
    if (typeof baseUrl !== 'string' || baseUrl.trim() === '') {
        return null;
    }

    const normalizedBaseUrl = baseUrl.trim();
    const origin = typeof window !== 'undefined' && window.location ? window.location.origin : 'http://localhost';
    const targetUrl = new URL(normalizedBaseUrl, origin);

    return targetUrl.toString();
}

export function buildMediaLibraryCacheKey(baseUrl, search, collection, type, page = 1, perPage = 25) {
    const requestUrl = buildMediaLibraryUrl(baseUrl, search, collection, type, page, perPage);
    return requestUrl ?? '';
}

export function normalizeMediaLibraryItems(payload, fallbackType = 'image') {
    let rawItems = [];
    if (Array.isArray(payload)) {
        rawItems = payload;
    } else if (Array.isArray(payload?.data)) {
        rawItems = payload.data;
    } else if (payload?.data && typeof payload.data === 'object') {
        rawItems = [payload.data];
    } else if (Array.isArray(payload?.items)) {
        rawItems = payload.items;
    } else if (Array.isArray(payload?.results)) {
        rawItems = payload.results;
    }

    return rawItems
        .map((item, index) => {
            if (typeof item === 'string') {
                const directUrl = item.trim();
                if (directUrl === '') {
                    return null;
                }

                return {
                    id: `media-${index}`,
                    url: directUrl,
                    previewUrl: directUrl,
                    title: directUrl,
                    type: String(fallbackType).toLowerCase()
                };
            }

            const primaryMediaUrl = resolveMediaItemUrl(item);
            const fallbackMediaUrl = resolveFallbackMediaItemUrl(item);
            const mediaUrl = primaryMediaUrl || fallbackMediaUrl;

            if (!mediaUrl) {
                return null;
            }

            const mediaType = resolveFirstString([item?.type, item?.mime_type, fallbackType]) || fallbackType;
            const mediaId = resolvePositiveIntegerString(item?.id) || resolvePositiveIntegerString(item?.media_id);

            const previewUrl = mediaType === 'image'
                ? mediaUrl
                : resolveMediaPreviewUrl(item, mediaUrl);

            return {
                id: resolveFirstString([item?.id ? String(item.id) : '', item?.uuid, `media-${index}`]),
                url: mediaUrl,
                originalUrl: primaryMediaUrl || mediaUrl,
                previewUrl,
                title: resolveMediaItemTitle(item, mediaUrl),
                type: String(mediaType).toLowerCase(),
                collectionId: resolveMediaItemCollectionId(item),
                mediaId,
                media_id: mediaId
            };
        })
        .filter(Boolean);
}

export const editorMediaMethods = {
    buildImageMediaUsables(mediaId) {
        const normalizedMediaId = resolvePositiveIntegerString(mediaId);
        const normalizedUsableType = resolveFirstString([this.mediaUsableType]);
        const normalizedUsableId = resolvePositiveIntegerString(this.mediaUsableId);

        if (normalizedMediaId === '') {
            return [];
        }

        return [{
            media_id: normalizedMediaId,
            media_usable_type: normalizedUsableType,
            media_usable_id: normalizedUsableId
        }];
    },

    getMediaLibraryCacheEntry(cacheKey) {
        if (!cacheKey || !(this.mediaLibraryCache instanceof Map)) {
            return null;
        }

        return this.mediaLibraryCache.get(cacheKey) ?? null;
    },

    setMediaLibraryCacheEntry(cacheKey, entry) {
        if (!cacheKey || !(this.mediaLibraryCache instanceof Map) || !entry) {
            return;
        }

        this.mediaLibraryCache.set(cacheKey, entry);
    },

    applyMediaLibraryCacheEntry(entry) {
        if (!entry || typeof entry !== 'object') {
            return false;
        }

        this.mediaLibraryItems = Array.isArray(entry.items) ? entry.items : [];
        this.mediaLibraryPage = Number.isInteger(entry.page) && entry.page > 0 ? entry.page : 1;
        this.mediaLibraryTotalPages = Number.isInteger(entry.totalPages) && entry.totalPages > 0 ? entry.totalPages : 1;
        this.mediaLibraryTotalItems = Number.isInteger(entry.totalItems) && entry.totalItems >= 0 ? entry.totalItems : 0;
        this.mediaLibraryPerPage = Number.isInteger(entry.perPage) && entry.perPage > 0 ? entry.perPage : 25;
        this.mediaLibraryError = this.mediaLibraryItems.length === 0 ? 'Keine Medien gefunden.' : '';
        this.mediaLibraryLoading = false;

        return true;
    },

    clearMediaLibraryCache() {
        if (this.mediaLibraryCache instanceof Map) {
            this.mediaLibraryCache.clear();
        }
        if (this.mediaLibraryPendingRequests instanceof Map) {
            this.mediaLibraryPendingRequests.clear();
        }
    },

    modalSupportsMediaLibrary(modalKey) {
        const modalTemplate = this.templates?.modals?.[modalKey];

        if (typeof modalTemplate !== 'string' || modalTemplate.trim() === '') {
            return false;
        }

        return modalTemplate.includes("ActiveTab === 'library'");
    },

    shouldOpenMediaModal(blockId) {
        if (this.mediaClickArmedBlockId !== blockId) {
            this.mediaClickArmedBlockId = blockId;
            this.selectBlock(blockId);
            return false;
        }

        this.mediaClickArmedBlockId = null;
        return true;
    },

    handleTwoStepMediaBlockClick(blockId, openModal) {
        openModal(blockId);
    },

    handleImageBlockClick(blockId) {
        this.handleTwoStepMediaBlockClick(blockId, (id) => this.openImageSettingsModal(id));
    },

    handleVideoBlockClick(blockId) {
        this.handleTwoStepMediaBlockClick(blockId, (id) => this.openVideoSettingsModal(id));
    },

    handleEmbedBlockClick(blockId) {
        this.handleTwoStepMediaBlockClick(blockId, (id) => this.openEmbedSettingsModal(id));
    },

    updateImageUrl(blockId, imageUrl) {
        BlockManagement.updateImageUrl(this.blocks, blockId, imageUrl);
    },

    updateImageAlt(blockId, imageAlt) {
        BlockManagement.updateImageAlt(this.blocks, blockId, imageAlt);
    },

    updateImageTitle(blockId, imageTitle) {
        BlockManagement.updateImageTitle(this.blocks, blockId, imageTitle);
    },

    updateVideoUrl(blockId, videoUrl) {
        BlockManagement.updateVideoUrl(this.blocks, blockId, videoUrl);
    },

    updateVideoPoster(blockId, videoPoster) {
        BlockManagement.updateVideoPoster(this.blocks, blockId, videoPoster);
    },

    updateVideoTitle(blockId, videoTitle) {
        BlockManagement.updateVideoTitle(this.blocks, blockId, videoTitle);
    },

    updateEmbedUrl(blockId, embedUrl) {
        BlockManagement.updateEmbedUrl(this.blocks, blockId, embedUrl);
    },

    updateEmbedTitle(blockId, embedTitle) {
        BlockManagement.updateEmbedTitle(this.blocks, blockId, embedTitle);
    },

    resetMediaLibraryState() {
        if (this.mediaLibraryDebounceTimeout) {
            clearTimeout(this.mediaLibraryDebounceTimeout);
        }
        this.mediaLibraryItems = [];
        this.mediaLibraryLoading = false;
        this.mediaLibraryError = '';
        this.mediaLibrarySearch = '';
        this.mediaLibraryDebounceTimeout = null;
        this.mediaLibraryPage = 1;
        this.mediaLibraryTotalPages = 1;
        this.mediaLibraryTotalItems = 0;
        this.mediaLibrarySelectedUrl = '';
        this.mediaLibraryRecentlyUploadedUrl = '';
        this.mediaUploadLoading = false;
        this.mediaUploadError = '';
        this.mediaUploadProgressPercent = 0;
        this.mediaUploadFileName = '';
        this.mediaUploadFileSizeLabel = '';
    },

    setImageSettingsTab(tab) {
        this.imageSettingsActiveTab = tab;

        if (tab === 'library') {
            this.prepareMediaLibraryForModal('image');
        }
    },

    setVideoSettingsTab(tab) {
        this.videoSettingsActiveTab = tab;

        if (tab === 'library') {
            this.prepareMediaLibraryForModal('video');
        }
    },

    prepareMediaLibraryForModal(target) {
        this.mediaLibraryTarget = target;
        this.mediaLibraryType = target === 'video' ? 'video' : 'image';
        this.mediaLibraryItems = [];
        this.mediaLibraryError = '';
        this.mediaLibraryPage = 1;
        this.mediaLibraryTotalPages = 1;
        this.mediaLibraryTotalItems = 0;
        this.mediaLibrarySelectedUrl = target === 'video'
            ? (this.videoSettingsUrl || '')
            : (this.imageSettingsUrl || '');
        this.loadMediaLibrary();
    },

    isMediaLibraryItemSelected(item) {
        const itemUrl = resolveFirstString([item?.originalUrl, item?.url]);

        return itemUrl !== '' && itemUrl === this.mediaLibrarySelectedUrl;
    },

    isRecentlyUploadedMediaLibraryItem(item) {
        const itemUrl = resolveFirstString([item?.originalUrl, item?.url]);
        return itemUrl !== '' && itemUrl === this.mediaLibraryRecentlyUploadedUrl;
    },

    queueMediaLibrarySearch() {
        if (this.mediaLibraryDebounceTimeout) {
            clearTimeout(this.mediaLibraryDebounceTimeout);
        }

        this.mediaLibraryPage = 1;
        this.mediaLibraryDebounceTimeout = setTimeout(() => {
            this.loadMediaLibrary();
        }, 300);
    },

    goToMediaLibraryPage(page) {
        const parsedPage = Number(page);
        if (!Number.isInteger(parsedPage) || parsedPage < 1 || parsedPage === this.mediaLibraryPage) {
            return;
        }

        this.mediaLibraryPage = parsedPage;
        this.loadMediaLibrary();
    },

    goToPreviousMediaLibraryPage() {
        if (this.mediaLibraryPage <= 1) {
            return;
        }

        this.goToMediaLibraryPage(this.mediaLibraryPage - 1);
    },

    goToNextMediaLibraryPage() {
        if (this.mediaLibraryPage >= this.mediaLibraryTotalPages) {
            return;
        }

        this.goToMediaLibraryPage(this.mediaLibraryPage + 1);
    },

    resolveMediaLibraryPagination(payload) {
        const meta = payload?.meta ?? {};
        const currentPage = Number(meta.current_page ?? payload?.current_page ?? this.mediaLibraryPage ?? 1);
        const lastPage = Number(meta.last_page ?? payload?.last_page ?? 1);
        const total = Number(meta.total ?? payload?.total ?? 0);
        const perPage = Number(meta.per_page ?? payload?.per_page ?? this.mediaLibraryPerPage ?? 25);

        this.mediaLibraryPage = Number.isInteger(currentPage) && currentPage > 0 ? currentPage : 1;
        this.mediaLibraryTotalPages = Number.isInteger(lastPage) && lastPage > 0 ? lastPage : 1;
        this.mediaLibraryTotalItems = Number.isInteger(total) && total >= 0 ? total : 0;
        this.mediaLibraryPerPage = Number.isInteger(perPage) && perPage > 0 ? perPage : 25;
    },

    async loadMediaLibrary(forceRefresh = false) {
        const apiUrl = this.mediaLibraryApiUrl;
        const requestUrl = buildMediaLibraryUrl(
            apiUrl,
            this.mediaLibrarySearch ?? '',
            null,
            this.mediaLibraryType ?? 'image',
            this.mediaLibraryPage ?? 1,
            this.mediaLibraryPerPage ?? 25
        );
        const cacheKey = buildMediaLibraryCacheKey(
            apiUrl,
            this.mediaLibrarySearch ?? '',
            null,
            this.mediaLibraryType ?? 'image',
            this.mediaLibraryPage ?? 1,
            this.mediaLibraryPerPage ?? 25
        );

        if (!requestUrl) {
            this.mediaLibraryError = 'Kein Mediathek-Endpoint konfiguriert.';
            this.mediaLibraryItems = [];
            return;
        }

        if (!forceRefresh) {
            const cachedEntry = this.getMediaLibraryCacheEntry(cacheKey);
            if (this.applyMediaLibraryCacheEntry(cachedEntry)) {
                return;
            }
        }

        if (!forceRefresh && this.mediaLibraryPendingRequests instanceof Map && this.mediaLibraryPendingRequests.has(cacheKey)) {
            this.mediaLibraryLoading = true;
            try {
                await this.mediaLibraryPendingRequests.get(cacheKey);
                const sharedCachedEntry = this.getMediaLibraryCacheEntry(cacheKey);
                this.applyMediaLibraryCacheEntry(sharedCachedEntry);
            } catch (error) {
                this.mediaLibraryItems = [];
                this.mediaLibraryTotalPages = 1;
                this.mediaLibraryTotalItems = 0;
                this.mediaLibraryError = `Mediathek konnte nicht geladen werden (${error?.message || 'unbekannter Fehler'}).`;
            } finally {
                this.mediaLibraryLoading = false;
            }

            return;
        }

        this.mediaLibraryLoading = true;
        this.mediaLibraryError = '';

        const requestPromise = (async () => {
            const response = await fetch(requestUrl, {
                headers: {
                    Accept: 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            const normalizedItems = normalizeMediaLibraryItems(payload, this.mediaLibraryType);
            const meta = payload?.meta ?? {};
            const currentPage = Number(meta.current_page ?? payload?.current_page ?? this.mediaLibraryPage ?? 1);
            const lastPage = Number(meta.last_page ?? payload?.last_page ?? 1);
            const total = Number(meta.total ?? payload?.total ?? 0);
            const perPage = Number(meta.per_page ?? payload?.per_page ?? this.mediaLibraryPerPage ?? 25);

            this.setMediaLibraryCacheEntry(cacheKey, {
                items: normalizedItems,
                page: Number.isInteger(currentPage) && currentPage > 0 ? currentPage : 1,
                totalPages: Number.isInteger(lastPage) && lastPage > 0 ? lastPage : 1,
                totalItems: Number.isInteger(total) && total >= 0 ? total : 0,
                perPage: Number.isInteger(perPage) && perPage > 0 ? perPage : 25
            });
        })();

        if (this.mediaLibraryPendingRequests instanceof Map) {
            this.mediaLibraryPendingRequests.set(cacheKey, requestPromise);
        }

        try {
            await requestPromise;
            const refreshedEntry = this.getMediaLibraryCacheEntry(cacheKey);
            this.applyMediaLibraryCacheEntry(refreshedEntry);
        } catch (error) {
            this.mediaLibraryItems = [];
            this.mediaLibraryTotalPages = 1;
            this.mediaLibraryTotalItems = 0;
            this.mediaLibraryError = `Mediathek konnte nicht geladen werden (${error?.message || 'unbekannter Fehler'}).`;
        } finally {
            if (this.mediaLibraryPendingRequests instanceof Map) {
                this.mediaLibraryPendingRequests.delete(cacheKey);
            }
            this.mediaLibraryLoading = false;
        }
    },

    async reloadMediaLibraryAfterUpload(target) {
        this.mediaLibraryTarget = target;
        this.mediaLibraryType = target === 'video' ? 'video' : 'image';
        this.mediaLibraryPage = 1;
        this.mediaLibraryItems = [];
        this.mediaLibraryError = '';
        this.mediaLibraryLoading = true;
        this.clearMediaLibraryCache();
        await this.loadMediaLibrary(true);
    },

    selectMediaLibraryItem(item) {
        const selectedAssetUrl = resolveFirstString([item?.originalUrl, item?.url]);

        if (!selectedAssetUrl) {
            return;
        }

        this.mediaLibrarySelectedUrl = selectedAssetUrl;

        if (this.mediaLibraryTarget === 'video') {
            this.videoSettingsUrl = selectedAssetUrl;
            if (!this.videoSettingsTitle) {
                this.videoSettingsTitle = item.title || '';
            }
        } else {
            this.imageSettingsUrl = selectedAssetUrl;
            const selectedMediaId = resolvePositiveIntegerString(item?.mediaId)
                || resolvePositiveIntegerString(item?.media_id)
                || resolvePositiveIntegerString(item?.id);
            this.imageSettingsMediaUsables = this.buildImageMediaUsables(selectedMediaId);
            if (!this.imageSettingsAlt) {
                this.imageSettingsAlt = item.title || '';
            }
            if (!this.imageSettingsTitle) {
                this.imageSettingsTitle = item.title || '';
            }
        }

    },

    resolveMediaUploadLanguage() {
        const configured = normalizeMediaUploadLocale(this.mediaUploadLanguage);
        if (configured !== '') {
            return configured;
        }

        const documentLang = normalizeMediaUploadLocale(
            typeof document !== 'undefined' ? document?.documentElement?.lang : ''
        );

        return documentLang || 'de_DE';
    },

    clearMediaUploadState() {
        this.mediaUploadLoading = false;
        this.mediaUploadError = '';
        this.mediaUploadProgressPercent = 0;
        this.mediaUploadFileName = '';
        this.mediaUploadFileSizeLabel = '';
    },

    openImageSettingsModal(blockId) {
        const { block } = this.findBlockById(blockId);
        if (block && block.type === 'image') {
            if (!this.shouldOpenMediaModal(blockId)) {
                return;
            }
            this.resetMediaLibraryState();
            Object.assign(this, getImageSettingsStateFromBlock(blockId, block));
            if (!this.modalSupportsMediaLibrary('imageSettings')) {
                this.imageSettingsActiveTab = 'upload';
            } else if (this.imageSettingsActiveTab === 'library') {
                this.prepareMediaLibraryForModal('image');
            }
            this.showImageSettingsModal = true;
            if (window.modalHelpers) window.modalHelpers.openModal();
        }
    },

    closeImageSettingsModal() {
        this.showImageSettingsModal = false;
        Object.assign(this, getDefaultImageSettingsState());
        this.resetMediaLibraryState();
    },

    saveImageSettings() {
        if (!this.imageSettingsBlockId) return;

        const urlInput = getRequiredTrimmedUrl(this.imageSettingsUrl);
        if (!urlInput.ok) {
            this.showNotification('Bitte geben Sie eine Bild-URL ein', 'warning');
            return;
        }

        BlockManagement.updateImageUrl(this.blocks, this.imageSettingsBlockId, urlInput.value);
        BlockManagement.updateImageAlt(this.blocks, this.imageSettingsBlockId, this.imageSettingsAlt);
        BlockManagement.updateImageTitle(this.blocks, this.imageSettingsBlockId, this.imageSettingsTitle);
        const imageMediaUsables = urlInput.value === this.mediaLibrarySelectedUrl
            ? this.imageSettingsMediaUsables
            : (urlInput.value === this.imageSettingsOriginalUrl
                ? this.imageSettingsOriginalMediaUsables
                : []);
        const normalizedImageMediaUsables = Array.isArray(imageMediaUsables)
            ? imageMediaUsables.map((entry) => normalizeMediaUsableEntry(entry)).filter(Boolean)
            : [];
        BlockManagement.updateImageMediaUsables(this.blocks, this.imageSettingsBlockId, normalizedImageMediaUsables);

        this.showNotification('Bild gespeichert!', 'success');
        this.closeImageSettingsModal();
    },

    selectImageFile(blockId) {
        pickFile({
            accept: 'image/*',
            onSelect: (file) => {
                this.uploadSelectedMediaFile(file, blockId, 'image');
            }
        });
    },

    openVideoSettingsModal(blockId) {
        const { block } = this.findBlockById(blockId);
        if (block && block.type === 'video') {
            if (!this.shouldOpenMediaModal(blockId)) {
                return;
            }
            this.resetMediaLibraryState();
            Object.assign(this, getVideoSettingsStateFromBlock(blockId, block));
            if (!this.modalSupportsMediaLibrary('videoSettings')) {
                this.videoSettingsActiveTab = 'upload';
            } else if (this.videoSettingsActiveTab === 'library') {
                this.prepareMediaLibraryForModal('video');
            }
            this.showVideoSettingsModal = true;
            if (window.modalHelpers) window.modalHelpers.openModal();
        }
    },

    closeVideoSettingsModal() {
        this.showVideoSettingsModal = false;
        Object.assign(this, getDefaultVideoSettingsState());
        this.resetMediaLibraryState();
    },

    saveVideoSettings() {
        if (!this.videoSettingsBlockId) return;

        const urlInput = getRequiredTrimmedUrl(this.videoSettingsUrl);
        if (!urlInput.ok) {
            this.showNotification('Bitte geben Sie eine Video-URL ein', 'warning');
            return;
        }

        BlockManagement.updateVideoUrl(this.blocks, this.videoSettingsBlockId, urlInput.value);
        BlockManagement.updateVideoPoster(this.blocks, this.videoSettingsBlockId, this.videoSettingsPoster);
        BlockManagement.updateVideoTitle(this.blocks, this.videoSettingsBlockId, this.videoSettingsTitle);

        this.showNotification('Video gespeichert!', 'success');
        this.closeVideoSettingsModal();
    },

    selectVideoFile(blockId) {
        pickFile({
            accept: 'video/*',
            onSelect: (file) => {
                this.uploadSelectedMediaFile(file, blockId, 'video');
            }
        });
    },

    async uploadSelectedMediaFile(file, blockId, mediaType) {
        if (!(file instanceof File)) {
            this.mediaUploadError = 'Keine Datei ausgewählt.';
            this.showNotification(this.mediaUploadError, 'warning');
            return;
        }

        const uploadUrl = buildMediaUploadUrl(this.mediaLibraryApiUrl);
        if (!uploadUrl) {
            this.mediaUploadError = 'Kein Mediathek-Upload-Endpoint konfiguriert.';
            this.showNotification(this.mediaUploadError, 'error');
            return;
        }

        const collectionId = resolveFirstString([this.mediaLibraryCollection]);
        let effectiveCollectionId = collectionId;

        if (effectiveCollectionId === '' && mediaType === 'image') {
            effectiveCollectionId = '1';
        }

        const metadataTitle = mediaType === 'video'
            ? resolveFirstString([this.videoSettingsTitle, file.name])
            : resolveFirstString([this.imageSettingsTitle, file.name]);
        const metadataAlt = mediaType === 'video'
            ? resolveFirstString([this.videoSettingsTitle, file.name])
            : resolveFirstString([this.imageSettingsAlt, file.name]);

        const formData = new FormData();
        formData.append('file', file);
        if (effectiveCollectionId !== '') {
            formData.append('media_collection_id', effectiveCollectionId);
        }
        formData.append('lang', this.resolveMediaUploadLanguage());
        formData.append('name', file.name);
        formData.append('title', metadataTitle);
        formData.append('alt', metadataAlt);
        const csrfToken = resolveCsrfToken();
        if (csrfToken !== '') {
            formData.append('_token', csrfToken);
        }

        this.mediaUploadLoading = true;
        this.mediaUploadError = '';
        this.mediaUploadFileName = file.name || '';
        this.mediaUploadFileSizeLabel = formatFileSize(file.size);
        this.mediaUploadProgressPercent = 0;
        this.mediaLibraryRecentlyUploadedUrl = '';

        try {
            const response = await uploadMediaWithProgress({
                url: uploadUrl,
                formData,
                csrfToken,
                onProgress: (percent) => {
                    this.mediaUploadProgressPercent = percent;
                }
            });

            const payload = response.payload ?? {};

            if (!response.ok) {
                this.mediaUploadError = parseMediaUploadError(
                    payload,
                    `Upload fehlgeschlagen (HTTP ${response.status}).`
                );
                this.showNotification(this.mediaUploadError, response.status === 409 ? 'warning' : 'error');
                return;
            }

            const uploadedItems = normalizeMediaLibraryItems(payload, mediaType);
            const uploadedItem = uploadedItems[0];
            if (!uploadedItem) {
                this.mediaUploadError = 'Upload erfolgreich, aber Rückgabe enthielt kein verwendbares Medium.';
                this.showNotification(this.mediaUploadError, 'warning');
                return;
            }

            this.selectMediaLibraryItem(uploadedItem);
            this.mediaUploadError = '';
            this.mediaUploadProgressPercent = 100;
            this.mediaLibraryRecentlyUploadedUrl = resolveFirstString([uploadedItem?.originalUrl, uploadedItem?.url]);

            if (mediaType === 'image') {
                if (this.showImageSettingsModal && this.imageSettingsBlockId === blockId) {
                    this.imageSettingsUrl = uploadedItem.url;
                } else if (blockId) {
                    BlockManagement.updateImageUrl(this.blocks, blockId, uploadedItem.url);
                }
                this.imageSettingsActiveTab = 'library';
                await this.reloadMediaLibraryAfterUpload('image');
            } else {
                if (this.showVideoSettingsModal && this.videoSettingsBlockId === blockId) {
                    this.videoSettingsUrl = uploadedItem.url;
                    if (!this.videoSettingsPoster) {
                        this.videoSettingsPoster = uploadedItem.previewUrl || '';
                    }
                } else if (blockId) {
                    BlockManagement.updateVideoUrl(this.blocks, blockId, uploadedItem.url);
                }
                this.videoSettingsActiveTab = 'library';
                await this.reloadMediaLibraryAfterUpload('video');
            }

            this.showNotification('Datei in die Mediathek hochgeladen.', 'success');
        } catch (error) {
            this.mediaUploadError = `Upload fehlgeschlagen (${error?.message || 'unbekannter Fehler'}).`;
            this.showNotification(this.mediaUploadError, 'error');
        } finally {
            this.mediaUploadLoading = false;
        }
    },

    openEmbedSettingsModal(blockId) {
        const { block } = this.findBlockById(blockId);
        if (block && block.type === 'embed') {
            if (!this.shouldOpenMediaModal(blockId)) {
                return;
            }
            Object.assign(this, getEmbedSettingsStateFromBlock(blockId, block));
            this.showEmbedSettingsModal = true;
            if (window.modalHelpers) window.modalHelpers.openModal();
        }
    },

    closeEmbedSettingsModal() {
        this.showEmbedSettingsModal = false;
        Object.assign(this, getDefaultEmbedSettingsState());
    },

    saveEmbedSettings() {
        if (!this.embedSettingsBlockId) return;

        const urlInput = getRequiredTrimmedUrl(this.embedSettingsUrl);
        if (!urlInput.ok) {
            this.showNotification('Bitte geben Sie eine Embed-URL ein', 'warning');
            return;
        }

        const normalized = normalizeEmbedUrl(urlInput.value);
        if (!normalized.ok) {
            this.showNotification(normalized.error || 'Ungültige Embed-URL', 'warning');
            return;
        }

        BlockManagement.updateEmbedUrl(this.blocks, this.embedSettingsBlockId, normalized.value);
        BlockManagement.updateEmbedTitle(this.blocks, this.embedSettingsBlockId, this.embedSettingsTitle);

        this.showNotification('Embed gespeichert!', 'success');
        this.closeEmbedSettingsModal();
    },

    getEmbedPreviewUrl() {
        const normalized = normalizeEmbedUrl(this.embedSettingsUrl);
        return normalized.ok ? normalized.value : '';
    },
};
