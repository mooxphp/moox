# Input

The `input` component offers an easy way to set up any type of input field for your forms. By simply setting its `name` attribute it also automatically defines your `id` and makes sure old values are respected.

## Installation

The `input` component comes ready out-of-the-box with Blade UI Kit. Simply [install the package](/docs/{{version}}/installation) and you're good to go.

## Basic Usage

The most basic usage of the component is to set its `name` attribute:

```html
<x-input name="search" />
```

This will output the following HTML:

```html
<input name="search" type="text" id="search" />
```

By default a `text` type will be set for the input field as well as an `id` that allows it to be easily referenced by a `label` element.

Of course, you can also specifically set a `type` and overwrite the `id` attribute:

```html
<x-input name="confirm_password" id="confirmPassword" type="password" class="p-4" />
```

This will output the following HTML:

```html
<input name="confirm_password" type="password" id="confirmPassword" class="p-4" />
```

### Old Values

The `input` component also supports old values that were set. For example, you might want to apply some validation in the backend, but also make sure the user doesn't lose their input data when you re-render the form with any validation errors. When re-rendering the form, the `input` component will remember the old value:

```html
<input name="search" type="text" id="search" value="Eloquent" />
```
