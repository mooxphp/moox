# Label

The `label` component is a small and practical convenience component to use in your forms. When you set the `for` attribute, it'll generate a `label` tag for a subsequent input field with the same `id` attribute and automatically generate the label title.

## Installation

The `label` component comes ready out-of-the-box with Blade UI Kit. Simply [install the package](/docs/{{version}}/installation) and you're good to go.

## Basic Usage

The most basic usage of the `label` component is as a self-closing component:

```html
<x-label for="first_name" />
```

This will output the following HTML:

```html
<label for="first_name">
    First name
</label>
```

As you can see it'll generate the title within the `<label>` tag. It's important to note that only keys with `_` are supported and no camelcased or other variants.
