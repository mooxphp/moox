export const editorEntryMethods = {
    async init() {
        if (typeof window !== 'undefined') {
            window.__mooxEditorActiveInstance = this;
        }
        await this.runEditorBootstrap();
        this.setupEditorEventListeners();
    },

    syncLivewireState(force = false) {
        const blocksHash = this.getBlocksHash(this.blocks);

        if (!force && blocksHash === this.livewireSyncHash) {
            return;
        }

        const hiddenInput = this.livewireHiddenInputId
            ? document.getElementById(this.livewireHiddenInputId)
            : null;

        if (!hiddenInput) {
            return;
        }

        const serializedBlocks = JSON.stringify(this.blocks ?? []);
        hiddenInput.value = serializedBlocks;

        const wireRoot = hiddenInput.closest('[wire\\:id]');
        const wireId = wireRoot?.getAttribute('wire:id');
        if (wireId && window.Livewire?.find) {
            const component = window.Livewire.find(wireId);
            if (component) {
                component.set('state', serializedBlocks);
            }
        }

        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        this.livewireSyncHash = blocksHash;
    },
};
