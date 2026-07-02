const THEMES_STORAGE_KEY = 'blockEditorThemes';

export function loadThemesFromLocalStorage() {
    const themesJson = localStorage.getItem(THEMES_STORAGE_KEY);
    let themes = [];

    if (themesJson) {
        try {
            themes = JSON.parse(themesJson);
        } catch (error) {
            console.error('Fehler beim Laden der Themes aus LocalStorage:', error);
        }
    }

    return themes;
}

export function saveThemesToLocalStorage(themes) {
    localStorage.setItem(THEMES_STORAGE_KEY, JSON.stringify(themes));
}
