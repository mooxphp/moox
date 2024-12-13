<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalated Entries</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9fafb;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1,
        h3 {
            font-size: 1.25rem;
            color: #333333;
            margin-bottom: 20px;
        }

        p {
            font-size: 1rem;
            color: #555555;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .table th {
            background-color: #f4f4f4;
            font-weight: 600;
            color: #333333;
        }

        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table tr:hover {
            background-color: #f1f1f1;
        }

        .table .title {
            font-weight: bold;
            color: #1a202c;
        }

        .table .escalation {
            color: #e53e3e;
            font-weight: bold;
        }


        .footer {
            margin-top: 20px;
            font-size: 0.875rem;
            color: #777777;
        }

        .footer a {
            color: #3182ce;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .logo {
            display: block;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="logo">
            <img src="{{ $logoUrl }}" alt="Company Logo" class="w-24 h-auto">
        </div>
        <h1>{{ __('core::expiry.escalated_entries_in_expiry_dashboard') }}</h1>
        <h4>{{ __('core::expiry.following_escalated_entries') }}</h4>

        <!-- Escalated Entries -->
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('core::core.title') }}</th>
                    <th>{{ __('core::expiry.notifyUser') }}</th>
                    <th>{{ __('core::core.category') }}</th>
                    <th>{{ __('core::expiry.will_expire_at') }}</th>
                    <th>{{ __('core::expiry.processing_deadline') }}</th>
                    <th>{{ __('core::expiry.escalated_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($escalatedEntries as $entry)
                    <tr>
                        <td class="title">{{ $entry['title'] }}</td>
                        <td>{{ $entry['notified_to'] }}</td>
                        <td>{{ $entry['category'] }}</td>
                        <td>{{ $entry['expired_at'] }}</td>
                        <td>{{ $entry['processing_deadline'] }}</td>
                        <td class="escalation">{{ $entry['escalated_at'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>

        <!-- Footer -->
        <p class="footer">{{ __('core::expiry.review_entreis') }}<a
                href="{{ url($panelPath . '/expiries') }}">{{ __('core::expiry.expiry_dashboard') }}</a>.</p>
    </div>
</body>

</html>
