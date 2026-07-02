<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        /** @var \Moox\BlockEditor\Forms\Components\BlockEditor $field */
        $mooxAllowedBlockTypes = $field->getPositiveBlock();
        $mooxExcludedBlockTypes = $field->getNegativeBlock();
        $mooxTemplatesEnabled = $field->getTemplatesEnabled();
        $mooxTemplateSlug = $field->getTemplateSlug();
        $mooxDeveloperJsonEnabled = $field->getDeveloperJsonEnabled();
        $mooxAddComponentsEnabled = $field->getAddComponentsEnabled();
        $mooxJsonImportEnabled = $field->getJsonImportEnabled();
        $mooxMediaLibraryApiUrl = $field->getMediaLibraryApiUrl();
        $mooxMediaLibraryCollection = $field->getMediaLibraryCollection();
        $mooxRecord = $field->getRecord();
        $mooxMediaUsableType = $field->getMediaUsableType() ?? ($mooxRecord ? get_class($mooxRecord) : null);
        $mooxMediaUsableId = $field->getMediaUsableId() ?? ($mooxRecord && $mooxRecord->getKey()
            ? (string) $mooxRecord->getKey()
            : null);
    @endphp
    <livewire:is
        :component="\Moox\BlockEditor\Livewire\BlockEditorField::class"
        wire:model="{{ $getStatePath() }}"
        :allowedBlockTypes="$mooxAllowedBlockTypes"
        :excludedBlockTypes="$mooxExcludedBlockTypes"
        :themeTemplatesEnabled="$mooxTemplatesEnabled"
        :templateSlug="$mooxTemplateSlug"
        :developerJsonEnabled="$mooxDeveloperJsonEnabled"
        :addComponentsEnabled="$mooxAddComponentsEnabled"
        :jsonImportEnabled="$mooxJsonImportEnabled"
        :mediaLibraryApiUrl="$mooxMediaLibraryApiUrl"
        :mediaLibraryCollection="$mooxMediaLibraryCollection"
        :mediaUsableType="$mooxMediaUsableType"
        :mediaUsableId="$mooxMediaUsableId"
        :key="$getId() . '.block-editor'"
    />
</x-dynamic-component>
