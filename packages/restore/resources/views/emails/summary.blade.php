<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Welcome to Our Platform</title>
    <style>
        /* Inline styles for simplicity, consider using CSS classes for larger templates */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f1f1f1;
        }

        .logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo img {
            max-width: 200px;
        }

        .message {
            padding: 20px;
            background-color: #ffffff;
        }

        .message p {
            margin-bottom: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="message">
            <h2 style="color: #007bff; margin-bottom: 20px;">Here is the summary of your backups:</h2>
            <ul>
                <li>Successful Backups: {{ $mailData['summary']->successfulBackups }}</li>
                <li>Failed Backups: {{ $mailData['summary']->failedBackups }}</li>
                <li>Healthy Destinations: {{ $mailData['summary']->healthyDestinations }}</li>
                <li>Unhealthy Destinations: {{ $mailData['summary']->unhealthyDestinations }}</li>
                <li>Healthy Sources: {{ $mailData['summary']->healthySources }}</li>
                <li>Unhealthy Sources: {{ $mailData['summary']->unhealthySources }}</li>
                <li>Destination Used Space:
                    {{ number_format($mailData['summary']->destinationUsedSpaceInKb / 1024 / 1024, 2) }} GB</li>
                <li>Destination Free Space:
                    {{ number_format($mailData['summary']->destinationFreeSpaceInKb / 1024 / 1024, 2) }} GB</li>
                <li>Time Spent Running Backups: {{ $mailData['summary']->timeSpentRunningBackupsInSeconds }} seconds
                </li>
                <li>Errors in Log: {{ $mailData['summary']->errorsInLog }}</li>
            </ul>


            <h2 style="color: #007bff; margin-bottom: 20px;">Restore Details</h2>
            <table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd;">
                <thead style="background-color: #f9f9f9;">
                    <tr>
                        <th colspan="3" style="border: 1px solid #ddd; padding: 10px;">Backup</th>
                        <th colspan="3" style="border: 1px solid #ddd; padding: 10px;">Restore</th>
                    </tr>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 10px;">Status</th>
                        <th style="border: 1px solid #ddd; padding: 10px;">Completed</th>
                        <th style="border: 1px solid #ddd; padding: 10px;">Source</th>
                        <th style="border: 1px solid #ddd; padding: 10px;">Destination</th>
                        <th style="border: 1px solid #ddd; padding: 10px;">Status</th>
                        <th style="border: 1px solid #ddd; padding: 10px;">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mailData['restore'] as $restore)
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                @if ($restore['backup']['status'] == 'completed')
                                    <span style="color: green;">✓</span>
                                @elseif($restore['backup']['status'] == 'failed')
                                    <span style="color: red;">!</span>
                                @endif
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">{{ $restore['backup']['completed_at'] }}
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                <a
                                    href="https://{{ $restore['restoreDestination']['source']['host'] }}">{{ $restore['restoreDestination']['source']['host'] }}</a>
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                <a
                                    href="https://{{ $restore['restoreDestination']['host'] }}">{{ $restore['restoreDestination']['host'] }}</a>
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                @if ($restore['status'] == 'completed')
                                    <span style="color: green;">✓</span>
                                @elseif($restore['status'] == 'failed')
                                    <span style="color: red;">!</span>
                                @endif
                            </td>
                            <td style="border: 1px solid #ddd; padding: 10px;">{{ $restore['created_at'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</body>

</html>
