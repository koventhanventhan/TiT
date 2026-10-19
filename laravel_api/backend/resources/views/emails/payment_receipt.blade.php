<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - TiT Education</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e0f2fe;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h1 {
            color: #0b1f4b;
            font-size: 22px;
            margin: 0 0 6px 0;
            letter-spacing: 0.5px;
        }
        .receipt-pill {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            font-size: 13px;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }
        .content {
            font-size: 14.5px;
            line-height: 1.6;
            color: #334155;
        }
        .ta-section {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px dashed #cbd5e1;
            color: #475569;
            font-size: 14px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 20px 0;
        }
        .btn {
            background-color: #0284c7;
            color: #ffffff !important;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
        }
        .footer {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TiT Education</h1>
            <div class="receipt-pill">Receipt: {{ $receiptNo }}</div>
        </div>

        <div class="content">
            <p>Dear <strong>{{ $student->full_name ?? $student->name }}</strong>,</p>
            <p>Your payment for <strong>{{ $monthName }}</strong> has been approved. Your official payment receipt is attached to this email as a PDF.</p>
            <p>You can also download or view your receipt online at any time:</p>

            @if(!empty($downloadUrl))
            <div class="btn-wrapper">
                <a href="{{ $downloadUrl }}" class="btn" target="_blank">Download Payment Receipt (PDF)</a>
            </div>
            @endif

            <div class="ta-section">
                <p>அன்பான <strong>{{ $student->full_name ?? $student->name }}</strong>,</p>
                <p>{{ $monthName }} மாதத்திற்கான உங்கள் கல்விக்கட்டணம் வெற்றிகரமாக ஏற்றுக்கொள்ளப்பட்டது. உங்கள் உத்தியோகபூர்வ பற்றுச்சீட்டு (Receipt) இத்துடன் இணைக்கப்பட்டுள்ளது.</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} TiT Education &bull; Jaffna, Sri Lanka. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
