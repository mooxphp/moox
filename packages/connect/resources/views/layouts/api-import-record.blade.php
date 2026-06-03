<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @yield('title', 'Connect')
    </title>
    <style>
        :root {
            color-scheme: dark light;
            --bg: #0f172a;
            --bg-card: #020617;
            --border: #1e293b;
            --fg: #e5e7eb;
            --muted: #9ca3af;
            --accent: #38bdf8;
            --danger: #f87171;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif;
            background: radial-gradient(circle at top left, #1e293b, #020617);
            color: var(--fg);
        }
        .container { max-width: 1200px; margin: 24px auto; padding: 0 16px 40px; }
        .subtitle { font-size: 0.85rem; color: var(--muted); margin-bottom: 16px; }
        .grid { display: grid; gap: 12px; }
        .grid.two { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .card {
            border-radius: 12px;
            border: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(15,23,42,.9), rgba(15,23,42,.98));
            padding: 12px 14px;
        }
        .meta { font-size: .8rem; color: var(--muted); }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        pre {
            margin: 6px 0 0;
            white-space: pre-wrap;
            word-break: break-word;
            border-radius: 8px;
            padding: 10px;
            border: 1px solid var(--border);
            background: var(--bg-card);
            max-height: 340px;
            overflow: auto;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: .8rem;
        }
        .preview-box {
            margin-top: 8px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px;
            background: var(--bg-card);
        }
        .preview-box img {
            max-width: 360px;
            max-height: 260px;
            display: block;
        }
        .preview-box iframe {
            width: 100%;
            height: 440px;
            border: 0;
            display: block;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 2px 9px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .status-ok {
            color: #bbf7d0;
            background: rgba(34, 197, 94, 0.14);
            border-color: rgba(34, 197, 94, 0.6);
        }
        .status-error {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.14);
            border-color: rgba(239, 68, 68, 0.6);
        }
        .status-unknown {
            color: #e2e8f0;
            background: rgba(148, 163, 184, 0.16);
            border-color: rgba(148, 163, 184, 0.45);
        }
    </style>
</head>
<body>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>

