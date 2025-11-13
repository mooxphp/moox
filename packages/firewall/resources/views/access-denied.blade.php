<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('firewall.message', 'Moox Firewall') }}</title>
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
            color: #dc2626;
            margin-bottom: 0.5rem;
        }

        .message {
            color: #718096;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .error-icon {
            font-size: 3rem;
            color: #dc2626;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="firewall-container">
        <div class="error-icon">🚫</div>
        <h1 class="title">{{ config('firewall.message', 'Moox Firewall') }}</h1>
        <p class="message">{{ config('firewall.denied_message', 'Access denied. Please contact the IT department.') }}</p>
    </div>
</body>
</html>
