<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Receipt - {{ $receiptNo }}</title>
    <style>
        body {
            font-family: 'notosanstamil', sans-serif;
            font-size: 10.5pt;
            color: #0b1f4b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        .wordmark-logo {
            font-family: 'montserrat', sans-serif;
            font-size: 46pt;
            font-weight: bold;
            color: #0b1f4b;
            line-height: 1;
            margin: 0;
            padding: 0;
            letter-spacing: -0.5pt;
        }
        .wordmark-sub {
            font-family: 'notosanstamil', sans-serif;
            font-size: 10.5pt;
            font-weight: bold;
            color: #0b1f4b;
            letter-spacing: 3pt;
            margin-top: 4px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .logo-img {
            max-height: 48px;
            max-width: 150px;
            margin: 0 auto 6px auto;
        }
        .title-container {
            border-top: 1.5px solid #7dd3fc;
            border-bottom: 1.5px solid #7dd3fc;
            padding: 6px 0;
            margin: 6px 0 10px 0;
            text-align: center;
        }
        .title-text {
            font-family: 'notosanstamil', sans-serif;
            font-size: 20pt;
            font-weight: bold;
            color: #0b1f4b;
            letter-spacing: 2pt;
            text-transform: uppercase;
            margin: 0;
            padding: 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .meta-table td {
            font-size: 10.5pt;
            color: #0b1f4b;
            padding: 2px 0;
            vertical-align: top;
        }
        .meta-label {
            font-weight: 600;
            color: #0b1f4b;
        }
        .meta-val {
            font-weight: bold;
            color: #0b1f4b;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .details-table tr {
            border-bottom: 1px solid #e0f2fe;
        }
        .details-table td {
            padding: 5px 4px;
            vertical-align: top;
            font-size: 10.5pt;
        }
        .label-col {
            width: 38%;
            color: #0b1f4b;
            font-weight: 600;
        }
        .value-col {
            width: 62%;
            color: #0b1f4b;
        }
        .class-item {
            margin-bottom: 2px;
            color: #0b1f4b;
        }
        .class-item:last-child {
            margin-bottom: 0;
        }
        .amount-box {
            width: 100%;
            background-color: #f0f9ff;
            border: 1.5px solid #7dd3fc;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 12px;
            text-align: center;
        }
        .amount-caption {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0369a1;
            letter-spacing: 2pt;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .amount-value {
            font-size: 24pt;
            font-weight: bold;
            color: #0b1f4b;
            line-height: 1.1;
        }
        .footer {
            margin-top: 16px;
            text-align: center;
        }
        .thank-you {
            font-size: 11pt;
            font-style: italic;
            color: #0b1f4b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .contact-line {
            font-size: 8.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
            <div class="wordmark-sub">INSTITUTE • JAFFNA</div>
        @else
            <div class="wordmark-logo">TiT</div>
            <div class="wordmark-sub">INSTITUTE • JAFFNA</div>
        @endif

        <div class="title-container">
            <div class="title-text">PAYMENT RECEIPT</div>
        </div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="text-align: left; width: 60%;">
                <span class="meta-label">Student Name:</span>
                <span class="meta-val">{{ $studentName }}</span>
            </td>
            <td style="text-align: right; width: 40%;">
                <span class="meta-label">Receipt No:</span>
                <span class="meta-val">{{ $receiptNo }}</span>
            </td>
        </tr>
    </table>

    <table class="details-table">
        <tr>
            <td class="label-col">Index Number</td>
            <td class="value-col">
                <strong>{{ $indexNumber }}</strong>
            </td>
        </tr>
        <tr>
            <td class="label-col">Grade</td>
            <td class="value-col">{{ $grade }}</td>
        </tr>
        <tr>
            <td class="label-col">Class / Teacher</td>
            <td class="value-col">
                @if(!empty($classes))
                    @foreach($classes as $c)
                        <div class="class-item">• {{ $c }}</div>
                    @endforeach
                @else
                    <span>General Tuition</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col">Payment Month</td>
            <td class="value-col"><strong>{{ $paymentMonth }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Payment Date</td>
            <td class="value-col">{{ $paymentDate }}</td>
        </tr>
        <tr>
            <td class="label-col">Payment Method</td>
            <td class="value-col">{{ $paymentMethod }}</td>
        </tr>
        @if(!empty($transactionId))
        <tr>
            <td class="label-col">Transaction Ref</td>
            <td class="value-col" style="font-family: monospace; font-size: 9.5pt; color: #475569;">{{ $transactionId }}</td>
        </tr>
        @endif
    </table>

    <div class="amount-box">
        <div class="amount-caption">AMOUNT RECEIVED</div>
        <div class="amount-value">{{ $amountFormatted }}</div>
    </div>

    <div class="footer">
        <div class="thank-you">Thank you for your payment.</div>
        <div class="contact-line">{{ $contactLine ?? 'TiT Institute • Jaffna' }}</div>
    </div>
</body>
</html>
