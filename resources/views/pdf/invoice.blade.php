<!DOCTYPE html>
<html>

<head>
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .company {
            font-size: 28px;
            font-weight: bold;
        }

        .order-details {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="header">
            <div class="company">Commercia</div>
            <div>Invoice</div>
        </div>

        <div class="order-details">
            <strong>Invoice Number:</strong> INV-{{ $order->id }}<br>
            <strong>Date:</strong> {{ $order->created_at->format('d M Y') }}<br>
            <strong>Order Status:</strong> {{ ucfirst($order->order_status) }}
        </div>

        <div class="customer-info">
            <strong>Customer:</strong> {{ $order->user->name ?? 'Guest' }}<br>
            <strong>Email:</strong> {{ $order->user->email ?? '-' }}<br>
            <strong>Phone:</strong> {{ $order->user->phone ?? '-' }}
        </div>

        <h3>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>৳{{ number_format($item->price, 2) }}</td>
                        <td>৳{{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            Total Amount: ৳{{ number_format($order->total, 2) }}
        </div>

        <div class="footer">
            Thank you for shopping with Commercia!
        </div>
    </div>
</body>

</html>