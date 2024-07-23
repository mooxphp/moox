<!DOCTYPE html>
<html>
<head>
    <title>Expiry Weekly</title>
</head>
<body>
    <h1>Expiry Weekly</h1>
    <ul>
        @foreach ($expiries as $expiry)
            <li>{{ $expiry->name }} - Expired on: {{ $expiry->expired_at }}</li>
        @endforeach
    </ul>
</body>
</html>
