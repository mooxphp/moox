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
            color: {{ config('firewall.color', 'black') }};
            margin-bottom: 0.5rem;
        }

        .message {
            color: #718096;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .input-group {
            position: relative;
        }

        .input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #718096;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
            outline: none;
        }

        .input:focus {
            border-color: {{ config('firewall.color', 'black') }};
        }

        .button {
            background: {{ config('firewall.color', 'black') }};
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #feb2b2;
        }
    </style>
</head>
<body>
    <div class="firewall-container">
        <h1 class="title">{{ config('firewall.message', 'Moox Firewall') }}</h1>
        <p class="message">{{ config('firewall.description', 'Please enter your access token to continue.') }}</p>

        @if(isset($firewall_error) && $firewall_error)
        <div class="error">
            {{ $firewall_error }}
        </div>
        @elseif(session('firewall_error'))
        <div class="error">
            {{ session('firewall_error') }}
        </div>
        @endif

        <form method="GET" class="form">
            <div class="input-group">
                <input
                    type="password"
                    name="backdoor_token"
                    class="input"
                    placeholder="Enter your access token"
                    autocomplete="off"
                    autofocus
                >
            </div>

            <button type="submit" class="button">
                Continue
            </button>
        </form>
    </div>
</body>
</html>
