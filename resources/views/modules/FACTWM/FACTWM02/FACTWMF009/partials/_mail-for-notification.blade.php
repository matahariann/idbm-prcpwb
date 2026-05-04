<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Invoice PT Hitachi Astemo Bekasi Manufacturing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background-color: #ffffff;
            padding: 30px 40px;
            border-bottom: 3px solid #e0e0e0;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 32px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 0;
        }

        .divider {
            border: 0;
            border-top: 2px dashed #666666;
            margin: 0 40px;
        }

        .content {
            padding: 30px 40px;
        }

        .content p {
            font-size: 14px;
            color: #333333;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .invoice-details {
            margin-top: 10px;
        }

        .detail-row {
            display: flex;
            font-size: 14px;
            color: #000000;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .detail-label {
            font-weight: normal;
            min-width: 200px;
        }

        .detail-colon {
            margin: 0 5px;
        }

        .detail-value {
            font-weight: bold;
        }

        .status-received {
            color: #2e7d32;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <h1>PT Hitachi Astemo Bekasi Manufacturing</h1>
            <h2>E-Invoice</h2>
        </div>

        <hr class="divider">

        <div class="content">
            <p>Billing Statement berhasil diterima PT Hitachi Astemo Bekasi Manufacturing</p>

            <div class="invoice-details">
                <div class="detail-row">
                    <span class="detail-label">Nomor Billing Statement</span>
                    <span class="detail-colon">:</span>
                    <span class="detail-value">{{ $nomorBilling }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Tanggal</span>
                    <span class="detail-colon">:</span>
                    <span class="detail-value">{{ $tanggal }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Jam</span>
                    <span class="detail-colon">:</span>
                    <span class="detail-value">{{ $jam }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-colon">:</span>
                    <span class="detail-value status-received">{{ $status }}</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>