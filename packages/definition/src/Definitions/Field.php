<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

use Moox\Definition\Definitions\Specifications\BuilderSpecification;
use Moox\Definition\Definitions\Specifications\ChoiceSpecification;
use Moox\Definition\Definitions\Specifications\CodeSpecification;
use Moox\Definition\Definitions\Specifications\ColorSpecification;
use Moox\Definition\Definitions\Specifications\DecimalSpecification;
use Moox\Definition\Definitions\Specifications\IconSpecification;
use Moox\Definition\Definitions\Specifications\JsonSpecification;
use Moox\Definition\Definitions\Specifications\KeyValueSpecification;
use Moox\Definition\Definitions\Specifications\MediaReferenceSpecification;
use Moox\Definition\Definitions\Specifications\MediaSpecification;
use Moox\Definition\Definitions\Specifications\MoneySpecification;
use Moox\Definition\Definitions\Specifications\NoSpecification;
use Moox\Definition\Definitions\Specifications\RepeaterSpecification;
use Moox\Definition\Definitions\Specifications\SliderSpecification;
use Moox\Definition\Definitions\Specifications\Specification;
use Moox\Definition\Definitions\Specifications\TaxonomySpecification;
use Moox\Definition\Definitions\Specifications\TextSpecification;

final class Field
{
    public function __construct(
        private readonly string $name,
        private readonly FieldType $type,
        private readonly Specification $specification,
        private readonly FieldAttributes $attributes,
    ) {
    }

    public static function id(string $name): self
    {
        return self::plain($name, FieldType::Id);
    }

    public static function uuid(string $name): self
    {
        return self::plain($name, FieldType::Uuid);
    }

    public static function ulid(string $name): self
    {
        return self::plain($name, FieldType::Ulid);
    }

    public static function title(string $name): self
    {
        return self::plain($name, FieldType::Title);
    }

    public static function slug(string $name): self
    {
        return self::plain($name, FieldType::Slug);
    }

    public static function text(string $name, int $maxLength): self
    {
        return new self($name, FieldType::Text, new TextSpecification($maxLength), FieldAttributes::make());
    }

    public static function textarea(string $name): self
    {
        return self::plain($name, FieldType::Textarea);
    }

    public static function richText(string $name): self
    {
        return self::plain($name, FieldType::RichText);
    }

    public static function markdown(string $name): self
    {
        return self::plain($name, FieldType::Markdown);
    }

    public static function code(string $name, CodeLanguage $language): self
    {
        return new self($name, FieldType::Code, new CodeSpecification($language), FieldAttributes::make());
    }

    public static function email(string $name): self
    {
        return self::plain($name, FieldType::Email);
    }

    public static function url(string $name): self
    {
        return self::plain($name, FieldType::Url);
    }

    public static function tel(string $name): self
    {
        return self::plain($name, FieldType::Tel);
    }

    public static function password(string $name): self
    {
        return self::plain($name, FieldType::Password);
    }

    public static function select(string $name, ValueType $valueType, ChoiceOptions $options): self
    {
        return self::choice($name, FieldType::Select, $valueType, $options, null);
    }

    public static function multiSelect(string $name, ValueType $valueType, ChoiceOptions $options): self
    {
        return self::choice($name, FieldType::MultiSelect, $valueType, $options, null);
    }

    public static function radio(string $name, ValueType $valueType, ChoiceOptions $options): self
    {
        return self::choice($name, FieldType::Radio, $valueType, $options, null);
    }

    public static function checkbox(string $name): self
    {
        return self::plain($name, FieldType::Checkbox);
    }

    public static function checkboxList(string $name, ValueType $valueType, ChoiceOptions $options): self
    {
        return self::choice($name, FieldType::CheckboxList, $valueType, $options, null);
    }

    public static function toggle(string $name): self
    {
        return self::plain($name, FieldType::Toggle);
    }

    public static function toggleButtons(string $name, ValueType $valueType, ChoiceOptions $options, bool $multiple): self
    {
        return self::choice($name, FieldType::ToggleButtons, $valueType, $options, $multiple);
    }

    public static function tags(string $name): self
    {
        return self::plain($name, FieldType::Tags);
    }

    public static function integer(string $name): self
    {
        return self::plain($name, FieldType::Integer);
    }

    public static function bigInteger(string $name): self
    {
        return self::plain($name, FieldType::BigInteger);
    }

    public static function decimal(string $name, int $precision, int $scale): self
    {
        return new self($name, FieldType::Decimal, new DecimalSpecification($precision, $scale), FieldAttributes::make());
    }

    public static function float(string $name): self
    {
        return self::plain($name, FieldType::Float);
    }

    public static function money(string $name, string $currency, int $precision, int $scale): self
    {
        return new self($name, FieldType::Money, new MoneySpecification($currency, $precision, $scale), FieldAttributes::make());
    }

    public static function percentage(string $name, int $precision, int $scale): self
    {
        return new self($name, FieldType::Percentage, new DecimalSpecification($precision, $scale), FieldAttributes::make());
    }

    public static function slider(
        string $name,
        ValueType $valueType,
        int|string $min,
        int|string $max,
        int|string $step,
        bool $range,
        ?int $precision = null,
        ?int $scale = null,
    ): self {
        return new self(
            $name,
            FieldType::Slider,
            new SliderSpecification($valueType, $min, $max, $step, $range, $precision, $scale),
            FieldAttributes::make(),
        );
    }

    public static function date(string $name): self
    {
        return self::plain($name, FieldType::Date);
    }

    public static function dateTime(string $name): self
    {
        return self::plain($name, FieldType::DateTime);
    }

    public static function time(string $name): self
    {
        return self::plain($name, FieldType::Time);
    }

    public static function color(string $name, ColorFormat $format): self
    {
        return new self($name, FieldType::Color, new ColorSpecification($format), FieldAttributes::make());
    }

    public static function icon(string $name, string $provider, string $set): self
    {
        return new self($name, FieldType::Icon, new IconSpecification($provider, $set), FieldAttributes::make());
    }

    public static function image(string $name, EntityReference $target): self
    {
        return new self($name, FieldType::Image, new MediaReferenceSpecification($target), FieldAttributes::make());
    }

    public static function file(string $name, EntityReference $target): self
    {
        return new self($name, FieldType::File, new MediaReferenceSpecification($target), FieldAttributes::make());
    }

    public static function media(string $name, EntityReference $target, Cardinality $cardinality): self
    {
        return new self($name, FieldType::Media, new MediaSpecification($target, $cardinality), FieldAttributes::make());
    }

    public static function mediaUrl(string $name): self
    {
        return self::plain($name, FieldType::MediaUrl);
    }

    /**
     * @param  array<mixed>  $schema
     */
    public static function json(string $name, array $schema): self
    {
        return new self($name, FieldType::Json, new JsonSpecification($schema), FieldAttributes::make());
    }

    public static function keyValue(string $name, ValueType $keyType, ValueType $valueType): self
    {
        return new self($name, FieldType::KeyValue, new KeyValueSpecification($keyType, $valueType), FieldAttributes::make());
    }

    /**
     * @param  array<string, Field>  $fields
     */
    public static function repeater(string $name, array $fields): self
    {
        return new self($name, FieldType::Repeater, new RepeaterSpecification($fields), FieldAttributes::make());
    }

    /**
     * @param  list<BlockDefinition>  $blocks
     */
    public static function builder(string $name, array $blocks): self
    {
        return new self($name, FieldType::Builder, new BuilderSpecification($blocks), FieldAttributes::make());
    }

    public static function taxonomy(
        string $name,
        EntityReference $target,
        Cardinality $cardinality,
        Hierarchy $hierarchy,
        bool $customTerms,
        bool $sortable,
    ): self {
        return new self(
            $name,
            FieldType::Taxonomy,
            new TaxonomySpecification($target, $cardinality, $hierarchy, $customTerms, $sortable),
            FieldAttributes::make(),
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): FieldType
    {
        return $this->type;
    }

    public function specification(): Specification
    {
        return $this->specification;
    }

    public function attributes(): FieldAttributes
    {
        return $this->attributes;
    }

    public function label(string $label): self
    {
        return $this->withAttributes($this->attributes->withLabel($label));
    }

    public function description(string $description): self
    {
        return $this->withAttributes($this->attributes->withDescription($description));
    }

    public function section(string $section): self
    {
        return $this->withAttributes($this->attributes->withSection($section));
    }

    public function required(bool $required = true): self
    {
        return $this->withAttributes($this->attributes->withRequired($required));
    }

    public function nullable(bool $nullable = true): self
    {
        return $this->withAttributes($this->attributes->withNullable($nullable));
    }

    public function default(mixed $default): self
    {
        return $this->withAttributes($this->attributes->withDefault(OptionalValue::of($default)));
    }

    public function unique(?UniqueScope $scope = null): self
    {
        return $this->withAttributes($this->attributes->withUnique(true, $scope ?? UniqueScope::global()));
    }

    public function index(bool $index = true): self
    {
        return $this->withAttributes($this->attributes->withIndex($index));
    }

    public function immutable(bool $immutable = true): self
    {
        return $this->withAttributes($this->attributes->withImmutable($immutable));
    }

    public function systemManaged(bool $systemManaged = true): self
    {
        return $this->withAttributes($this->attributes->withSystemManaged($systemManaged));
    }

    public function generated(string $generator): self
    {
        return $this->withAttributes($this->attributes->withGenerator(OptionalValue::of($generator)));
    }

    public function role(string $role): self
    {
        return $this->withAttributes($this->attributes->withRole($role));
    }

    private static function plain(string $name, FieldType $type): self
    {
        return new self($name, $type, new NoSpecification, FieldAttributes::make());
    }

    private static function choice(
        string $name,
        FieldType $type,
        ValueType $valueType,
        ChoiceOptions $options,
        ?bool $multiple,
    ): self {
        return new self($name, $type, new ChoiceSpecification($valueType, $options, $multiple), FieldAttributes::make());
    }

    private function withAttributes(FieldAttributes $attributes): self
    {
        return new self($this->name, $this->type, $this->specification, $attributes);
    }
}
