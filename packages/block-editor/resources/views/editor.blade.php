<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width,initial-scale=1.0" />
        <link rel="icon" href="{{ url('moox/block-editor/images/favicon.png') }}">
        <title>Moox Block Editor</title>
        <script type="module" crossorigin src="{{ url('moox/block-editor/assets/index.js?v=0.0.1') }}"></script>
        <link rel="stylesheet" crossorigin href="{{ url('moox/block-editor/assets/index.css?v=0.0.1') }}">
    </head>
    <body class="min-h-screen">
        <div
            id="block-editor"
            data-mode="{{ $mode ?? 'web' }}"
            data-initial-content='@json($initialContent ?? [])'
        ></div>
    </body>
</html>