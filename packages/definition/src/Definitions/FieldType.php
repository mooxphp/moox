<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum FieldType: string
{
    case Id = 'id';
    case Uuid = 'uuid';
    case Ulid = 'ulid';
    case Title = 'title';
    case Slug = 'slug';
    case Text = 'text';
    case Textarea = 'textarea';
    case RichText = 'richText';
    case Markdown = 'markdown';
    case Code = 'code';
    case Email = 'email';
    case Url = 'url';
    case Tel = 'tel';
    case Password = 'password';
    case Select = 'select';
    case MultiSelect = 'multiSelect';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case CheckboxList = 'checkboxList';
    case Toggle = 'toggle';
    case ToggleButtons = 'toggleButtons';
    case Tags = 'tags';
    case Integer = 'integer';
    case BigInteger = 'bigInteger';
    case Decimal = 'decimal';
    case Float = 'float';
    case Money = 'money';
    case Percentage = 'percentage';
    case Slider = 'slider';
    case Date = 'date';
    case DateTime = 'dateTime';
    case Time = 'time';
    case Color = 'color';
    case Icon = 'icon';
    case Image = 'image';
    case File = 'file';
    case Media = 'media';
    case MediaUrl = 'mediaUrl';
    case Json = 'json';
    case KeyValue = 'keyValue';
    case Repeater = 'repeater';
    case Builder = 'builder';
    case Relation = 'relation';
    case Taxonomy = 'taxonomy';
}
