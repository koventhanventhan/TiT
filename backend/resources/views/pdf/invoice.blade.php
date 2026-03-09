<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $payment->transaction_id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; line-height: 1.6; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.details td { padding-bottom: 20px; }
        .invoice-box table tr.item td { border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-warning { background-color: #fef9c3; color: #854d0e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <span style="color: #6366f1; font-weight: bold;">LENOVA</span>
                            </td>
                            <td>
                                Invoice #: {{ $payment->id }}<br>
                                Created: {{ $payment->created_at->format('M d, Y') }}<br>
                                Paid: {{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : 'N/A' }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <strong>Institute:</strong><br>
                                {{ $payment->user?->institute?->name ?? 'N/A' }}<br>
                                {{ $payment->user?->institute?->email ?? '' }}
                            </td>
                            <td>
                                <strong>Recipient:</strong><br>
                                {{ $payment->user?->full_name }}<br>
                                {{ $payment->user?->email }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Payment Method</td>
                <td>Status</td>
            </tr>

            <tr class="details">
                <td>{{ strtoupper($payment->payment_method) }}</td>
                <td>
                    <span class="badge badge-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                        {{ $payment->status }}
                    </span>
                </td>
            </tr>

            <tr class="heading">
                <td>Item</td>
                <td>Price</td>
            </tr>

            <tr class="item last">
                <td>Tuition Fee - {{ $payment->year_month }}</td>
                <td>Rs. {{ number_format($payment->amount, 2) }}</td>
            </tr>

            <tr class="total">
                <td></td>
                <td>Total: Rs. {{ number_format($payment->amount, 2) }}</td>
            </tr>
        </table>
        <div style="margin-top: 50px; text-align: center; color: #777; font-size: 12px;">
            Thank you for your payment. This is a computer-generated invoice.
        </div>
    </div>
</body>
</html>
