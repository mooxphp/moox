<?php

namespace Moox\Slug\Forms\Components;

use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Moox\Slug\Forms\Fields\SlugInput;

class TitleWithSlugInput
{
    public static function make(

        // Model fields
        ?string $fieldTitle = null,
        ?string $fieldSlug = null,
        ?string $fieldPermalink = null,

        // Url
        string|Closure|null $urlPath = '/',
        ?string $urlPathEntityType = null,
        string|Closure|null $urlHost = null,
        bool $urlHostVisible = true,
        bool|Closure $urlVisitLinkVisible = true,
        Closure|string|null $urlVisitLinkLabel = null,
        ?Closure $urlVisitLinkRoute = null,

        // Title
        string|Closure|null $titleLabel = null,
        ?string $titlePlaceholder = null,
        array|Closure|null $titleExtraInputAttributes = null,
        array $titleRules = [
            'required',
        ],
        array $titleRuleUniqueParameters = [],
        bool|Closure $titleIsReadonly = false,
        bool|Closure $titleAutofocus = true,
        ?Closure $titleAfterStateUpdated = null,

        // Slug
        ?string $slugLabel = null,
        array $slugRules = [
            'required',
        ],
        array $slugRuleUniqueParameters = [],
        bool|Closure $slugIsReadonly = false,
        ?Closure $slugAfterStateUpdated = null,
        ?Closure $slugSlugifier = null,
        string|Closure|null $slugRuleRegex = '/^[a-z0-9\-\_]*$/',
        string|Closure|null $slugLabelPostfix = null,
    ): Group {
        $fieldTitle = $fieldTitle ?? config('slug.field_title');
        $fieldSlug = $fieldSlug ?? config('slug.field_slug');
        $urlHost = $urlHost ?? config('slug.url_host');

        /** Input: "Title" */
        $textInput = TextInput::make($fieldTitle)
            ->disabled($titleIsReadonly)
            ->live(true)
            ->autocomplete(false)
            ->rules($titleRules)
            ->extraInputAttributes($titleExtraInputAttributes ?? ['class' => 'text-xl font-semibold'])
            ->beforeStateDehydrated(fn (TextInput $component, $state) => $component->state(trim($state)))
            ->afterStateUpdated(
                function ($state, Set $set, Get $get, string $context, ?Model $record, TextInput $component) use ($slugSlugifier, $fieldSlug, $fieldPermalink, $urlPathEntityType, $titleAfterStateUpdated) {
                    $slugAutoUpdateDisabled = $get($fieldSlug.'_slug_auto_update_disabled');

                    if ($context === 'edit' && filled($record)) {
                        $slugAutoUpdateDisabled = ! empty($get($fieldSlug));
                    }

                    if (! $slugAutoUpdateDisabled && filled($state)) {
                        $slug = self::slugify($slugSlugifier, $state);
                        $set($fieldSlug, $slug);

                        if ($fieldPermalink && filled($slug)) {
                            $entityPath = $urlPathEntityType ? '/'.$urlPathEntityType : '';
                            $permalink = $entityPath.'/'.$slug;
                            $set($fieldPermalink, $permalink);
                        }
                    }

                    if ($titleAfterStateUpdated) {
                        $component->evaluate($titleAfterStateUpdated);
                    }
                }
            );

        if (in_array('required', $titleRules, true)) {
            $textInput->required();
        }

        if ($titlePlaceholder !== '') {
            $textInput->placeholder($titlePlaceholder ?: fn () => Str::of($fieldTitle)->headline());
        }

        if (! $titleLabel) {
            $textInput->hiddenLabel();
        }

        if ($titleLabel) {
            $textInput->label($titleLabel);
        }

        if ($titleRuleUniqueParameters) {
            if (is_array($titleRuleUniqueParameters)) {
                $table = $titleRuleUniqueParameters['table'] ?? null;
                $column = $titleRuleUniqueParameters['column'] ?? null;
                $ignorable = $titleRuleUniqueParameters['ignorable'] ?? null;
                $ignoreRecord = $titleRuleUniqueParameters['ignoreRecord'] ?? false;
                $modifyRuleUsing = $titleRuleUniqueParameters['modifyRuleUsing'] ?? null;

                $textInput->unique(
                    table: $table,
                    column: $column,
                    ignorable: $ignorable,
                    ignoreRecord: $ignoreRecord,
                    modifyRuleUsing: $modifyRuleUsing,
                );
            } else {
                // Fallback for non-array usage
                $textInput->unique($titleRuleUniqueParameters);
            }
        }

        /** Input: "Slug" (+ view) */
        $slugInput = SlugInput::make($fieldSlug)
            ->extraAttributes(['style' => 'margin-top: -15px;'])
            // Custom SlugInput methods
            ->slugInputVisitLinkRoute($urlVisitLinkRoute)
            ->slugInputVisitLinkLabel($urlVisitLinkLabel)
            ->slugInputUrlVisitLinkVisible($urlVisitLinkVisible)
            ->slugInputContext(fn ($context) => $context === 'create' ? 'create' : 'edit')
            ->slugInputRecordSlug(fn (?Model $record) => data_get($record?->attributesToArray(), $fieldSlug))
            ->slugInputModelName(
                fn (?Model $record) => $record
                ? Str::of(class_basename($record))->headline()
                : ''
            )
            ->slugInputLabelPrefix($slugLabel)
            ->slugInputBasePath($urlPath)
            ->slugInputBaseUrl($urlHost)
            ->slugInputShowUrl($urlHostVisible)
            ->slugInputSlugLabelPostfix($slugLabelPostfix)
            ->slugInputUrlPathEntityType($urlPathEntityType)

            // Default TextInput methods
            ->readOnly($slugIsReadonly)
            ->live(true)
            ->autocomplete(false)
            ->hiddenLabel()
            ->regex($slugRuleRegex)
            ->rules($slugRules)
            ->validationMessages([
                'unique' => __('core::core.slug_unique'),
            ])
            ->afterStateUpdated(
                function ($state, Set $set, Get $get, TextInput $component) use ($slugSlugifier, $fieldTitle, $fieldSlug, $fieldPermalink, $urlPathEntityType, $slugAfterStateUpdated) {
                    $text = trim($state) === ''
                        ? $get($fieldTitle)
                        : $get($fieldSlug);

                    $slug = self::slugify($slugSlugifier, $text);
                    $set($fieldSlug, $slug);

                    if ($fieldPermalink && filled($slug)) {
                        $entityPath = $urlPathEntityType ? '/'.$urlPathEntityType : '';
                        $permalink = $entityPath.'/'.$slug;
                        $set($fieldPermalink, $permalink);
                    }

                    $set($fieldSlug.'_slug_auto_update_disabled', true);

                    if ($slugAfterStateUpdated) {
                        $component->evaluate($slugAfterStateUpdated);
                    }
                }
            );

        if ($slugRuleUniqueParameters) {
            if (is_array($slugRuleUniqueParameters)) {
                $table = $slugRuleUniqueParameters['table'] ?? null;
                $column = $slugRuleUniqueParameters['column'] ?? null;
                $ignorable = $slugRuleUniqueParameters['ignorable'] ?? null;
                $ignoreRecord = $slugRuleUniqueParameters['ignoreRecord'] ?? false;
                $modifyRuleUsing = $slugRuleUniqueParameters['modifyRuleUsing'] ?? null;

                $slugInput->unique(
                    table: $table,
                    column: $column,
                    ignorable: $ignorable,
                    ignoreRecord: $ignoreRecord,
                    modifyRuleUsing: $modifyRuleUsing,
                );
            } else {
                // Fallback for non-array usage
                $slugInput->unique($slugRuleUniqueParameters);
            }
        }

        /** Input: "Slug Auto Update Disabled" (Hidden) */
        $hiddenInputSlugAutoUpdateDisabled = Hidden::make($fieldSlug.'_slug_auto_update_disabled')
            ->dehydrated(false);

        /** Input: "Permalink" (Hidden) */
        $hiddenInputPermalink = $fieldPermalink ? Hidden::make($fieldPermalink) : null;

        /** Group */

        return Group::make()
            ->schema([
                $textInput,
                $slugInput,
                $hiddenInputSlugAutoUpdateDisabled,
                $hiddenInputPermalink,
            ]);
    }

    /** Fallback slugifier, over-writable with slugSlugifier parameter. */
    protected static function slugify(?Closure $slugifier, ?string $text): string
    {
        if (is_null($text) || ! trim($text)) {
            return '';
        }

        return is_callable($slugifier)
            ? $slugifier($text)
            : Str::slug($text);
    }
}
