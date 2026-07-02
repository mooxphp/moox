/**
 * Template Index
 * Exportiert alle HTML-Templates für die Verwendung in index.html
 * cache-bust-marker: 20260428-sidebar-scope-fix
 */
import { getNotificationTemplate } from './notification/notification.js';
import { getSidebarTemplate } from './sidebar/sidebar.js';
import { getBlockToolbarTemplate } from './toolbars/block-toolbar.js';
import { getFloatingToolbarTemplate } from './toolbars/floating-toolbar.js';
import { getJsonImportModalTemplate } from './modals/json-import-modal.js';
import { getThemeSaveModalTemplate } from './modals/theme-save-modal.js';
import { getThemeEditModalTemplate } from './modals/theme-edit-modal.js';
import { getThemeImportModalTemplate } from './modals/theme-import-modal.js';
import { getLinkModalTemplate } from './modals/link-modal.js';
import { getConfirmModalTemplate } from './modals/confirm-modal.js';
import { getImageSettingsModalTemplate } from './modals/image-settings-modal.js';
import { getVideoSettingsModalTemplate } from './modals/video-settings-modal.js';
import { getEmbedSettingsModalTemplate } from './modals/embed-settings-modal.js';
import {
    getDeveloperHeaderActionsTemplate,
    getDeveloperJsonDisplayTemplate
} from './developer/developer-tools.js';

/**
 * @param  {{ allowThemeTemplates?: boolean }}  options  `allowThemeTemplates: false` → Toolbar ohne Tab „Theme Vorlagen“
 */
export function getAllTemplates(options = {}) {
    const allowThemeTemplates = options.allowThemeTemplates !== false;

    return {
        notification: getNotificationTemplate(),
        sidebar: getSidebarTemplate(),
        blockToolbar: getBlockToolbarTemplate(allowThemeTemplates),
        floatingToolbar: getFloatingToolbarTemplate(),
        developer: {
            headerActions: getDeveloperHeaderActionsTemplate(),
            jsonDisplay: getDeveloperJsonDisplayTemplate()
        },
        modals: {
            jsonImport: getJsonImportModalTemplate(),
            themeSave: getThemeSaveModalTemplate(),
            themeEdit: getThemeEditModalTemplate(),
            themeImport: getThemeImportModalTemplate(),
            link: getLinkModalTemplate(),
            confirm: getConfirmModalTemplate(),
            imageSettings: getImageSettingsModalTemplate(),
            videoSettings: getVideoSettingsModalTemplate(),
            embedSettings: getEmbedSettingsModalTemplate()
        }
    };
}

