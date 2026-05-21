<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('firewall.message', __('firewall::translations.message')) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: black;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .firewall-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .title {
            font-size: 1.5rem;
            font-weight: 600;
            color: {{ config('firewall.color', 'black') }};
            margin-bottom: 0.5rem;
        }

        .message {
            color: #718096;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .error-icon {
            font-size: 3rem;
            color: {{ config('firewall.color', 'black') }};
            margin-bottom: 1rem;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
    <div class="firewall-container">
        <div class="error-icon" aria-hidden="true">🚫</div>
        <span class="sr-only">{{ __('firewall::translations.denied_message') }}</span>
        <h1 class="title">{{ config('firewall.message', __('firewall::translations.message')) }}</h1>
        <p class="message">{{ __('firewall::translations.denied_message') }}</p>
    </div>
</body>
</html>
