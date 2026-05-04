<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .content {
            line-height: 1.6;
        }

        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Good Receipt Note Dispute</h2>
            <p><strong>GR Number:</strong> {{ $grNumber }}</p>
        </div>

        <div class="content">
            <h3>Dispute Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background-color: #f5f5f5;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><strong>GR Note ID:</strong></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">{{ $grNoteId }}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;"><strong>Submitted By:</strong></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">{{ $userName }} ({{ $userEmail }})</td>
                </tr>
                <tr style="background-color: #f5f5f5;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><strong>Date/Time:</strong></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">{{ $timestamp }}</td>
                </tr>
            </table>

            <h3 style="margin-top: 20px;">Description</h3>
            <p style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #007bff;">
                {!! nl2br(e($description)) !!}
            </p>

            <p>Please review the dispute and take appropriate action.</p>
        </div>

        <div class="footer">
            <p>This is an automated email notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>

</html>
