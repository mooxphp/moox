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
                        <span style="display: flex; align-items: center;">
                            <span>{{ $getLabelPrefix() }}</span>
                            <span x-text="!editing ? '{{ $getFullBaseUrl() }}' : '{{ $getBasePath() }}'" style="color: #9ca3af; margin-left: 0.25rem;"></span>
                            <x-filament::link tag="button" x-on:click.prevent="initModification()" x-show="!editing"
                                x-bind:class="context !== 'create' && modified ? 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700 px-1 rounded-md' : ''"
                                icon="heroicon-m-pencil-square" icon-size="sm" icon-position="after" style="margin-left: 0.25rem;">
                                <span class="mr-1">{{ $getState() }}</span>
                            </x-filament::link>
                            @if($getSlugLabelPostfix())
                                <span x-show="!editing" style="margin-left: 0.125rem; color: #9ca3af;">{{ $getSlugLabelPostfix() }}</span>
                            @endif
                            <span x-show="!editing && context !== 'create' && modified" style="margin-left: 0.25rem;">
                                [{{ __('slug::fields.permalink_status_changed') }}]
                            </span>
                        </span>
                        @if($getSlugInputUrlVisitLinkVisible())
                            <template x-if="!editing">
                                <x-filament::link :href="$getRecordUrl()" target="_blank" size="sm" weight="semibold"
                                    icon="heroicon-m-arrow-top-right-on-square" icon-position="after">
                                    {{ $getVisitLinkLabel() }}
                                </x-filament::link>
                            </template>
                        @endif
                    </div>

                    <div class="flex-1 mx-2" x-show="editing" style="display: none;">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" x-ref="slugInput" x-model="stateInitial" x-bind:disabled="!editing"
                                x-on:keydown.enter="submitModification()" x-on:keydown.escape="cancelModification()"
                                :autocomplete="$getAutocomplete()" :id="$getId()" :placeholder="$getPlaceholder()"
                                :required="$isRequired()" :attributes="$getExtraInputAttributeBag()->class([
                    'fi-input block w-full border-none bg-transparent py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-xs sm:leading-6 ps-3 pe-3',
                    'border-danger-600 ring-danger-600' => $errors->has($getStatePath())
                ])" />
                        </x-filament::input.wrapper>

                    </div>

                    <div x-show="editing" class="flex space-x-2 gap-2" style="display: none;">

                        <x-filament::button type="button" x-on:click.prevent="submitModification()">
                            {{ __('slug::fields.permalink_action_ok') }}
                        </x-filament::button>

                        <x-filament::link x-show="context === 'edit' && modified" x-on:click.prevent="resetModification()"
                            tag="button" icon="heroicon-o-arrow-path" color="gray" size="sm"
                            title="{{ __('slug::fields.permalink_action_reset') }}">
                            <span class="sr-only">{{ __('slug::fields.permalink_action_reset') }}</span>
                        </x-filament::link>

                        <x-filament::link x-on:click.prevent="cancelModification()" tag="button" icon="heroicon-o-x-mark"
                            color="gray" size="sm" title="{{ __('slug::fields.permalink_action_cancel') }}">
                            <span class="sr-only">{{ __('slug::fields.permalink_action_cancel') }}</span>
                        </x-filament::link>

                    </div>

            @endif

        </div>

    </div>

</x-filament-forms::field-wrapper>