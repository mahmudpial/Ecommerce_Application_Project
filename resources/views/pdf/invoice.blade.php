<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .invoice-box {
            max-width: 1000px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .company {
            font-size: 26px;
            font-weight: bold;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 20px;
        }

        .info-card {
            flex: 1;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 8px;
            background: #f9fafb;
        }

        .info-card h3 {
            font-size: 14px;
            margin-top: 0;
            margin-bottom: 10px;
            border-left: 3px solid #3b82f6;
            padding-left: 8px;
        }

        .info-row {
            margin-bottom: 5px;
        }

        .label {
            font-weight: bold;
            width: 100px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f1f5f9;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
            border-top: 2px solid #eee;
            padding-top: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }

        .total-row {
            font-size: 16px;
            font-weight: bold;
            border-top: 1px solid #ccc;
            margin-top: 5px;
            padding-top: 8px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="header">
            <div class="company">COMMERCIA</div>
            <div>Tax Invoice</div>
        </div>

        <div class="info-section">
            <div class="info-card">
                <h3>Order Details</h3>
                <div class="info-row"><span class="label">Order #:</span> {{ $order->order_number ?? $order->id }}</div>
                <div class="info-row"><span class="label">Date:</span> {{ $order->created_at->format('d M Y, h:i A') }}
                </div>
                <div class="info-row"><span class="label">Status:</span> {{ ucfirst($order->order_status) }}</div>
                <div class="info-row"><span class="label">Payment:</span>
                    {{ ucfirst($order->payment_status ?? 'Pending') }}</div>
                <div class="info-row"><span class="label">Method:</span> {{ $order->payment_method ?? 'N/A' }}</div>
                <div class="info-row"><span class="label">Transaction ID:</span> {{ $order->transaction_id ?? '-' }}
                </div>
            </div>
            <div class="info-card">
                <h3>Customer Information</h3>
                <div class="info-row"><span class="label">Name:</span>
                    {{ $order->user->name ?? $order->customer_name ?? 'Guest' }}</div>
                <div class="info-row"><span class="label">Email:</span>
                    {{ $order->user->email ?? $order->customer_email ?? '-' }}</div>
                <div class="info-row"><span class="label">Phone:</span>
                    {{ $order->user->phone ?? $order->customer_phone ?? '-' }}</div>
                <div class="info-row"><span class="label">Shipping Address:</span>
                    {{ $order->shipping_address ?? $order->address ?? 'Not provided' }}</div>
                <div class="info-row"><span class="label">Billing Address:</span>
                    {{ $order->billing_address ?? 'Same as shipping' }}</div>
            </div>
        </div>

        <h3>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $item->product->name ?? $item->product_name ?? 'Product' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">BDT {{ number_format($item->price, 2) }}</td>
                        <td class="text-right">BDT {{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row"><span>Subtotal</span><span>BDT {{ number_format($subtotal, 2) }}</span></div>
            @if($shipping_cost > 0)
                <div class="summary-row"><span>Shipping Charge</span><span>BDT {{ number_format($shipping_cost, 2) }}</span>
                </div>
            @endif
            @if($discount > 0)
                <div class="summary-row"><span>Discount</span><span>- BDT {{ number_format($discount, 2) }}</span></div>
            @endif
            @if($tax > 0)
                <div class="summary-row"><span>Tax</span><span>BDT {{ number_format($tax, 2) }}</span></div>
            @endif
            <div class="summary-row total-row"><span>Grand Total</span><span>BDT {{ number_format($total, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            Thank you for shopping with Commercia.<br>
            For support: support@commercia.com | This is a system‑generated invoice.
        </div>
    </div>
</body>

</html>