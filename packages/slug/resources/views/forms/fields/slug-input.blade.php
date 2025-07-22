<x-filament-forms::field-wrapper :id="$getId()" :label="$getLabel()" :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText()" :hint="$getHint()" :hint-icon="$getHintIcon()" :required="$isRequired()"
    :state-path="$getStatePath()" class="-mt-3 filament-seo-slug-input-wrapper">
    <div x-data="{
            context: '{{ $getContext() }}', // edit or create
            state: $wire.entangle('{{ $getStatePath() }}'), // current slug value
            statePersisted: '', // slug value received from db
            stateInitial: '', // slug value before modification
            editing: false,
            modified: false,
            initModification: function() {

                this.stateInitial = this.state;

                if(!this.statePersisted) {
                    this.statePersisted = this.state;
                }

                this.editing = true;

                setTimeout(() => $refs.slugInput.focus(), 75);
                {{--$nextTick(() => $refs.slugInput.focus());--}}

            },
            submitModification: function() {

                if(!this.stateInitial) {
                    this.state = '';
                }
                else {
                    this.state = this.stateInitial;
                }

                $wire.set('{{ $getStatePath() }}', this.state)

                this.detectModification();

                this.editing = false;

           },
           cancelModification: function() {

                this.stateInitial = this.state;

                this.detectModification();

                this.editing = false;

           },
           resetModification: function() {

                this.stateInitial = this.statePersisted;

                this.detectModification();

           },
           detectModification: function() {

                this.modified = this.stateInitial !== this.statePersisted;

           },
        }" x-on:submit.document="modified = false">

        <div {{ $attributes->merge($getExtraAttributes())->class(['flex gap-4 items-center justify-between group text-sm filament-forms-text-input-component']) }}>

            @if($getReadOnly())

                <span class="flex">
                    <span class="mr-1">{{ $getLabelPrefix() }}</span>
                    <span class="text-gray-400">{{ $getFullBaseUrl() }}</span>
                    <span class="text-gray-400 font-semibold">{{ $getState() }}</span>
                </span>

            @else

                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                    <template x-if="!editing">
                        <div style="display: flex; align-items: center; flex: 1 1 0; min-width: 0;">
                            <span>{{ $getLabelPrefix() }}</span>
                            <span style="color: #9ca3af; margin-left: 0.25rem;">{{ $getFullBaseUrl() }}</span>
                            <x-filament::link tag="button" x-on:click.prevent="initModification()" x-show="!editing"
                                x-bind:class="context !== 'create' && modified ? 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700 px-1 rounded-md' : ''"
                                icon="heroicon-m-pencil-square" icon-size="sm" icon-position="after"
                                style="margin-left: 0.25rem;">
                                <span class="mr-1">{{ $getState() }}</span>
                            </x-filament::link>
                            @if($getSlugLabelPostfix())
                                <span style="margin-left: 0.125rem; color: #9ca3af;">{{ $getSlugLabelPostfix() }}</span>
                            @endif
                            <span x-show="context !== 'create' && modified" style="margin-left: 0.25rem;">
                                [{{ __('slug::fields.permalink_status_changed') }}]
                            </span>
                        </div>
                    </template>
                    <template x-if="!editing">
                        @if($getSlugInputUrlVisitLinkVisible())
                            <x-filament::link :href="$getRecordUrl()" target="_blank" size="sm" weight="semibold"
                                icon="heroicon-m-arrow-top-right-on-square" icon-position="after">
                                {{ $getVisitLinkLabel() }}
                            </x-filament::link>
                        @endif
                    </template>

                    <template x-if="editing">
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <span style="margin-right: 0.5rem; white-space: nowrap;">{{ $getLabelPrefix() }}
                                <span style="color: #9ca3af;">{{ $getFullBaseUrl() }}</span></span>
                            <x-filament::input.wrapper style="margin-bottom: 0; flex: 1 1 0; min-width: 0;">
                                <x-filament::input type="text" x-ref="slugInput" x-model="stateInitial"
                                    x-bind:disabled="!editing" x-on:keydown.enter="submitModification()"
                                    x-on:keydown.escape="cancelModification()" :autocomplete="$getAutocomplete()"
                                    :id="$getId()" :placeholder="$getPlaceholder()" :required="$isRequired()" />
                            </x-filament::input.wrapper>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <x-filament::button type="button" x-on:click.prevent="submitModification()"
                                    style="margin-left: 0.5rem;">
                                    {{ __('slug::fields.permalink_action_ok') }}
                                </x-filament::button>
                                <x-filament::link x-show="context === 'edit' && modified"
                                    x-on:click.prevent="resetModification()" tag="button" icon="heroicon-o-arrow-path"
                                    color="gray" size="sm">
                                </x-filament::link>
                                <x-filament::link x-on:click.prevent="cancelModification()" tag="button"
                                    icon="heroicon-o-x-mark" color="gray" size="sm">
                                </x-filament::link>
                            </div>
                        </div>
                    </template>
                </div>

            @endif

        </div>

    </div>

</x-filament-forms::field-wrapper>