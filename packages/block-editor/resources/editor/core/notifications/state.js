const VALID_NOTIFICATION_TYPES = ['success', 'error', 'info', 'warning'];

export function showNotificationState({
    notification,
    message,
    type = 'success',
    duration = 3000,
    notificationTimeout,
    setNotificationTimeout,
    logger = console
}) {
    let normalizedType = type;
    if (!VALID_NOTIFICATION_TYPES.includes(normalizedType)) {
        logger.warn(`Ungültiger Notification-Typ: ${normalizedType}. Verwende 'success' als Standard.`);
        normalizedType = 'success';
    }

    if (notificationTimeout) {
        clearTimeout(notificationTimeout);
    }

    notification.message = message;
    notification.type = normalizedType;
    notification.duration = duration;
    notification.show = true;

    if (duration > 0) {
        const timeoutId = setTimeout(() => {
            notification.show = false;
            setNotificationTimeout(null);
        }, duration);
        setNotificationTimeout(timeoutId);
        return;
    }

    setNotificationTimeout(null);
}

export function hideNotificationState({
    notification,
    notificationTimeout,
    setNotificationTimeout
}) {
    if (notificationTimeout) {
        clearTimeout(notificationTimeout);
        setNotificationTimeout(null);
    }

    notification.show = false;
}
