<?php

return [
    'navigation_group' => 'Fields',

    'field_group' => [
        'single' => 'Field group',
        'plural' => 'Field groups',
        'general' => 'General',
        'assignment' => 'Assignment',
        'fields' => 'Fields',
        'name' => 'Name',
        'name_helper' => 'Shown as the section heading in forms.',
        'slug' => 'Technical key',
        'slug_helper' => 'Unique identifier for this group. Generated from the name automatically.',
        'active' => 'Active',
        'active_helper' => 'Only active groups appear in forms.',
        'sort' => 'Sort order',
        'sort_helper' => 'Lower values appear higher in the form.',
        'target_entities' => 'Show on',
        'target_entities_helper' => 'This field group appears in the forms of the selected resources.',
        'target_entities_placeholder' => 'Select a resource…',
        'no_entities_registered' => 'No resources use HasCustomFields yet. Add the trait to a Filament resource.',
        'field_item' => 'Field',
        'fields_count' => 'Fields',
        'assigned_to' => 'Assigned to',
    ],

    'field' => [
        'label' => 'Label',
        'label_helper' => 'Visible name in the form.',
        'name' => 'Field key',
        'name_helper' => 'Technical name for storage and queries. Lowercase letters, numbers, and hyphens only.',
        'type' => 'Field type',
        'required' => 'Required',
        'settings' => 'Settings',
        'options' => 'Options',
        'option_label' => 'Display text',
        'option_value' => 'Value',
    ],

    'field_types' => [
        'text' => 'Text (short)',
        'textarea' => 'Text (multiline)',
        'number' => 'Number',
        'range' => 'Range',
        'email' => 'Email',
        'url' => 'URL',
        'password' => 'Password',
        'select' => 'Select (dropdown)',
        'multiselect' => 'Multi select',
        'checkbox_list' => 'Checkbox list',
        'radio' => 'Radio buttons',
        'button_group' => 'Button group',
        'toggle' => 'Toggle',
        'date' => 'Date',
        'datetime' => 'Date & time',
        'time' => 'Time',
        'color' => 'Color',
        'link' => 'Link',
        'rich_text' => 'Rich text',
        'message' => 'Message',
        'oembed' => 'oEmbed',
    ],

    'message' => [
        'body' => 'Message',
    ],

    'oembed' => [
        'helper' => 'Paste a video or embed URL (YouTube, Vimeo, etc.).',
    ],

    'link' => [
        'url' => 'URL',
        'label' => 'Label',
        'opens_in_new_tab' => 'Open in new tab',
    ],

    'capabilities' => [
        'max_length' => 'Maximum length',
        'placeholder' => 'Placeholder',
        'prefix' => 'Prefix',
        'suffix' => 'Suffix',
        'default_value' => 'Default value',
        'helper_text' => 'Helper text',
        'min_value' => 'Minimum value',
        'max_value' => 'Maximum value',
        'step' => 'Step',
        'rows' => 'Rows',
        'display_format' => 'Display format',
    ],

    'repeater' => [
        'collapse_all' => 'Collapse all',
        'expand_all' => 'Expand all',
    ],

    'validation' => [
        'invalid_option' => 'The selected value is not a valid option.',
        'duplicate_field_name' => 'The field key ":name" is already used in the group ":group".',
        'duplicate_field_name_internal' => 'The field key ":name" is assigned more than once in this group.',
    ],
];
