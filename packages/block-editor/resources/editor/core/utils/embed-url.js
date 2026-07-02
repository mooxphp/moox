const YOUTUBE_ID_PATTERN = /^[a-zA-Z0-9_-]{6,}$/;

function sanitizeId(rawId) {
    const id = String(rawId || '').trim();
    return YOUTUBE_ID_PATTERN.test(id) ? id : '';
}

function toYoutubeEmbedUrl(videoId) {
    return `https://www.youtube-nocookie.com/embed/${videoId}`;
}

function toVimeoEmbedUrl(videoId) {
    return `https://player.vimeo.com/video/${videoId}`;
}

export function normalizeEmbedUrl(rawValue) {
    const input = String(rawValue || '').trim();
    if (!input) {
        return { ok: false, value: '', error: 'Bitte geben Sie eine URL ein.' };
    }

    let parsed;
    try {
        parsed = new URL(input);
    } catch (error) {
        return { ok: false, value: '', error: 'Die URL ist ungültig.' };
    }

    if (parsed.protocol !== 'https:' && parsed.protocol !== 'http:') {
        return { ok: false, value: '', error: 'Nur http/https URLs sind erlaubt.' };
    }

    const host = parsed.hostname.toLowerCase();
    const path = parsed.pathname;

    if (host === 'youtu.be') {
        const id = sanitizeId(path.split('/').filter(Boolean)[0] || '');
        if (!id) {
            return { ok: false, value: '', error: 'Ungültige YouTube-URL.' };
        }
        return { ok: true, value: toYoutubeEmbedUrl(id), provider: 'youtube' };
    }

    if (host === 'youtube.com' || host === 'www.youtube.com' || host === 'm.youtube.com') {
        if (path === '/watch') {
            const id = sanitizeId(parsed.searchParams.get('v'));
            if (!id) {
                return { ok: false, value: '', error: 'Ungültige YouTube-Video-URL.' };
            }
            return { ok: true, value: toYoutubeEmbedUrl(id), provider: 'youtube' };
        }

        const pathParts = path.split('/').filter(Boolean);
        if (pathParts[0] === 'embed') {
            const id = sanitizeId(pathParts[1]);
            if (!id) {
                return { ok: false, value: '', error: 'Ungültige YouTube-Embed-URL.' };
            }
            return { ok: true, value: toYoutubeEmbedUrl(id), provider: 'youtube' };
        }

        if (pathParts[0] === 'shorts') {
            const id = sanitizeId(pathParts[1]);
            if (!id) {
                return { ok: false, value: '', error: 'Ungültige YouTube-Shorts-URL.' };
            }
            return { ok: true, value: toYoutubeEmbedUrl(id), provider: 'youtube' };
        }

        return {
            ok: false,
            value: '',
            error: 'Diese YouTube-URL ist nicht einbettbar. Bitte ein Video (watch/share/embed) verwenden.'
        };
    }

    if (host === 'www.youtube-nocookie.com' || host === 'youtube-nocookie.com') {
        const pathParts = path.split('/').filter(Boolean);
        if (pathParts[0] === 'embed') {
            const id = sanitizeId(pathParts[1]);
            if (!id) {
                return { ok: false, value: '', error: 'Ungültige YouTube-Embed-URL.' };
            }
            return { ok: true, value: toYoutubeEmbedUrl(id), provider: 'youtube' };
        }
    }

    if (host === 'vimeo.com' || host === 'www.vimeo.com') {
        const id = sanitizeId(path.split('/').filter(Boolean)[0] || '');
        if (!id) {
            return { ok: false, value: '', error: 'Ungültige Vimeo-URL.' };
        }
        return { ok: true, value: toVimeoEmbedUrl(id), provider: 'vimeo' };
    }

    if (host === 'player.vimeo.com') {
        const pathParts = path.split('/').filter(Boolean);
        if (pathParts[0] === 'video') {
            const id = sanitizeId(pathParts[1]);
            if (!id) {
                return { ok: false, value: '', error: 'Ungültige Vimeo-Embed-URL.' };
            }
            return { ok: true, value: toVimeoEmbedUrl(id), provider: 'vimeo' };
        }
    }

    // Generisches Embed: Alle http/https URLs erlauben.
    // Hinweis: Manche Seiten blockieren iFrames via X-Frame-Options oder CSP.
    return { ok: true, value: parsed.toString(), provider: 'generic' };
}
