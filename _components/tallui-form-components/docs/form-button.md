# Form Button

## Introduction

The `form-button` component enables you to perform HTTP requests using any HTTP method you wish. By applying an HTML `form` tag behind the scenes it hides all of the bulk work of setting up the entire form.

## Installation

The `form-button` component comes ready out-of-the-box with Blade UI Kit. Simply [install the package](/docs/{{version}}/installation) and you're good to go.

## Basic Usage

The most basic usage of the component is by setting an action, and supplying button text:

```html
<x-form-button :action="route('logout')" class="p-4 bg-blue-500">
    Sign Out
</x-form-button>
```

This will output the following HTML:

```html
<form method="POST" action="http://localhost/logout">
    <input type="hidden" name="_token" value="..." />
    <input type="hidden" name="_method" value="POST" />

    <button type="submit" class="p-4 bg-blue-400">Sign Out</button>
</form>
```

All attributes set on the component are piped through on the `button` element.

## HTTP Methods

You can set a different HTTP method if you like. For example, when deleting resources:

```html
<x-form-button
    :action="route('post', $id)"
    method="DELETE"
    class="p-4 bg-red-500"
>
    Delete Post
</x-form-button>
```

This will output the following HTML:

```html
<form method="POST" action="http://localhost/posts/1">
    <input type="hidden" name="_token" value="..." />
    <input type="hidden" name="_method" value="DELETE" />

    <button type="submit" class="p-4 bg-red-500">Delete Post</button>
</form>
```
