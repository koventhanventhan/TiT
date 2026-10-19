<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Receipt - {{ $receiptNo }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 18mm 20mm 18mm 20mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11.5px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .logo-img {
            max-height: 52px;
            max-width: 140px;
            object-fit: contain;
            margin: 0 auto 4px auto;
        }
        .subtitle {
            font-size: 10px;
            font-weight: 700;
            color: #0b1f4b;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            margin: 2px 0 6px 0;
        }
        .divider-line {
            height: 1.5px;
            background-color: #7dd3fc;
            margin: 6px 0;
            border: none;
        }
        .title {
            font-size: 19px;
            font-weight: 800;
            color: #0b1f4b;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 6px 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .meta-table td {
            font-size: 11px;
            color: #475569;
            padding: 2px 0;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .details-table tr {
            border-bottom: 1px solid #e0f2fe;
        }
        .details-table td {
            padding: 8px 6px;
            vertical-align: top;
            font-size: 11.5px;
        }
        .label-col {
            width: 32%;
            color: #475569;
            font-weight: 600;
        }
        .value-col {
            width: 68%;
            color: #0f172a;
        }
        .index-badge {
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: 700;
            color: #0284c7;
            background: #f0f9ff;
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid #bae6fd;
            display: inline-block;
        }
        .class-item {
            margin-bottom: 3px;
            color: #1e293b;
        }
        .class-item:last-child {
            margin-bottom: 0;
        }
        .amount-box {
            background-color: #f0f9ff;
            border: 1.5px solid #7dd3fc;
            border-radius: 8px;
            padding: 12px 18px;
            margin-top: 22px;
            width: 100%;
        }
        .amount-table {
            width: 100%;
            border-collapse: collapse;
        }
        .amount-table td {
            vertical-align: middle;
            padding: 0;
        }
        .amount-label {
            font-size: 11px;
            font-weight: 800;
            color: #0369a1;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .amount-val {
            text-align: right;
            font-size: 17px;
            font-weight: 900;
            color: #0c4a6e;
            letter-spacing: 0.5px;
        }
        .footer {
            margin-top: 35px;
            text-align: center;
            color: #64748b;
            font-size: 10.5px;
        }
        .thank-you {
            font-size: 12px;
            font-style: italic;
            color: #334155;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .disclaimer {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
        @endif
        <div class="subtitle">INSTITUTE &bull; JAFFNA</div>
        <hr class="divider-line">
        <div class="title">PAYMENT RECEIPT</div>
        <hr class="divider-line">
    </div>

    <table class="meta-table">
        <tr>
            <td style="text-align: left;">
                Receipt No: <span class="meta-val">{{ $receiptNo }}</span>
            </td>
            <td style="text-align: right;">
                Date: <span class="meta-val">{{ $paymentDate }}</span>
            </td>
        </tr>
    </table>

    <table class="details-table">
        <tr>
            <td class="label-col">Student Name</td>
            <td class="value-col"><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Index Number</td>
            <td class="value-col">
                @if($indexNumber && $indexNumber !== 'Pending')
                    <span class="index-badge">{{ $indexNumber }}</span>
                @else
                    <span style="color: #64748b; font-style: italic;">Pending</span>
                @endif
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
                        <div class="class-item">&bull; {{ $c }}</div>
                    @endforeach
                @else
                    <span style="color: #64748b;">General Tuition</span>
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
            <td class="value-col" style="font-family: monospace; font-size: 10.5px; color: #475569;">{{ $transactionId }}</td>
        </tr>
        @endif
    </table>

    <div class="amount-box">
        <table class="amount-table">
            <tr>
                <td class="amount-label">AMOUNT RECEIVED</td>
                <td class="amount-val">{{ $amountFormatted }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div class="thank-you">Thank you for your payment.</div>
        <div style="font-size: 9.5px; color: #64748b;">TiT Institute &bull; Quality Education for Future Leaders</div>
        <div class="disclaimer">This is a computer-generated receipt and requires no physical signature.</div>
    </div>
</body>
</html>
