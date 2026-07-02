// Entry point for the editor state
export async function ensureEditorState(version) {
    const query = version ? `?v=${version}` : '';
    await import(`../../block-editor.js${query}`);
}
