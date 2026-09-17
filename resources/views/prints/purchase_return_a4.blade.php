<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Return Memo {{ $purchaseReturn->return_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
            position: relative;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .logo {
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
        }
        
        .return-header {
            text-align: center;
            background-color: #fff7ed;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #ea580c;
            border-radius: 5px;
        }
        
        .return-title {
            font-size: 20px;
            font-weight: bold;
            color: #ea580c;
            margin: 0;
        }

        .status-badge {
            display: inline-block;
            margin-top: 5px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 12px;
            background-color: #ea580c;
            color: #fff;
            text-transform: uppercase;
        }
        
        .meta-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .meta-section > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .meta-section h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
        }
        
        .meta-section p {
            margin: 3px 0;
            font-size: 11px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-section {
            width: 320px;
            margin-left: auto;
            margin-bottom: 20px;
        }
        
        .totals-table td {
            border: none;
            padding: 5px 10px;
            font-size: 11px;
        }
        
        .totals-table .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 12px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        
        .reason-section {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .reason-title {
            font-weight: bold;
            color: #ea580c;
            margin-bottom: 5px;
        }

        .refund-amount {
            color: #ea580c;
            font-weight: bold;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        @if($settings && $settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="Logo" class="logo">
        @endif
        
        <div class="company-name">{{ $settings->company_name ?? 'POS System' }}</div>
        
        @if($settings && $settings->tagline)
            <div class="company-tagline">{{ $settings->tagline }}</div>
        @endif
        
        <div class="company-details">
            @if($settings && $settings->address)
                <div>{{ $settings->address }}</div>
            @endif
            
            @if($settings && ($settings->phone_1 || $settings->phone_2 || $settings->whatsapp_number || $settings->email))
                <div>
                    @if($settings->phone_1)Phone: {{ $settings->phone_1 }}@endif
                    @if($settings->phone_2) | {{ $settings->phone_2 }}@endif
                    @if($settings->whatsapp_number) | WhatsApp: {{ $settings->whatsapp_number }}@endif
                    @if($settings->email) | Email: {{ $settings->email }}@endif
                </div>
            @endif
        </div>
    </div>

    <!-- Return Header -->
    <div class="return-header">
        <h1 class="return-title">PURCHASE RETURN MEMO</h1>
        <div class="status-badge">GOODS RETURNED TO SUPPLIER</div>
    </div>

    <!-- Meta Information -->
    <div class="meta-section">
        <div>
            <h3>Return Details</h3>
            <p><strong>Return No:</strong> {{ $purchaseReturn->return_no }}</p>
            <p><strong>Original Purchase Bill:</strong> {{ $purchaseReturn->purchase->purchase_no ?? 'N/A' }}</p>
            <p><strong>Return Date:</strong> {{ $purchaseReturn->returned_at->format('M d, Y') }}</p>
            <p><strong>Return Time:</strong> {{ $purchaseReturn->returned_at->format('h:i A') }}</p>
            <p><strong>Refund Method:</strong> <span style="text-transform: capitalize; font-weight: bold;">{{ $purchaseReturn->refund_type }}</span></p>
            <p><strong>Processed By:</strong> {{ $purchaseReturn->user->name ?? 'System' }}</p>
        </div>
        
        <div>
            @if($purchaseReturn->supplier)
                <h3>Supplier Details</h3>
                <p><strong>Name:</strong> {{ $purchaseReturn->supplier->name }}</p>
                @if($purchaseReturn->supplier->phone)
                    <p><strong>Phone:</strong> {{ $purchaseReturn->supplier->phone }}</p>
                @endif
                @if($purchaseReturn->supplier->address)
                    <p><strong>Address:</strong> {{ $purchaseReturn->supplier->address }}</p>
                @endif
            @else
                <h3>Supplier Details</h3>
                <p><strong>Name:</strong> Direct Supplier</p>
            @endif
        </div>
    </div>

    <!-- Return Reason -->
    <div class="reason-section">
        <div class="reason-title">Return Reason / Notes:</div>
        <div>{{ $purchaseReturn->reason }}</div>
    </div>

    <!-- Return Items -->
    <table>
        <thead>
            <tr>
                <th style="width: 40%">Product Description</th>
                <th style="width: 20%">Returned Qty / Rolls</th>
                <th style="width: 15%">Rate (PKR)</th>
                <th style="width: 15%">Line Total (PKR)</th>
                <th style="width: 10%">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseReturn->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product->name ?? 'Product' }}</strong>
                        @if($item->product && $item->product->type === 'panaflex_roll' && $item->roll_width_inch && $item->roll_length_meter)
                            <br><small style="color: #666;">
                                Spec: {{ number_format($item->roll_width_inch, 2) }}" × {{ number_format($item->roll_length_meter, 2) }}m
                            </small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item->product && $item->product->type === 'panaflex_roll')
                            {{ number_format($item->rolls_count, 2) }} Rolls
                        @else
                            {{ $item->quantity }} {{ $item->product->unit->symbol ?? 'pcs' }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                    <td class="text-right refund-amount">{{ number_format(abs($item->line_total), 2) }}</td>
                    <td>{{ $item->note ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right refund-amount">PKR {{ number_format(abs($purchaseReturn->subtotal), 2) }}</td>
            </tr>
            @if($purchaseReturn->other_adjustments != 0)
                <tr>
                    <td>Adjustments / Fees:</td>
                    <td class="text-right">PKR {{ number_format($purchaseReturn->other_adjustments, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>TOTAL RETURN AMOUNT:</strong></td>
                <td class="text-right refund-amount"><strong>PKR {{ number_format(abs($purchaseReturn->grand_total), 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>Accounting & Ledger Summary:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>Stock has been deducted and inventory batches updated accordingly.</li>
            <li>Refund Type: <strong>{{ ucfirst($purchaseReturn->refund_type) }}</strong></li>
            @if($purchaseReturn->refund_type === 'credit')
                <li>Supplier payable liability in supplier ledger reduced by PKR {{ number_format(abs($purchaseReturn->grand_total), 2) }}.</li>
            @elseif($purchaseReturn->refund_type === 'cash')
                <li>Cash refund received from supplier: PKR {{ number_format(abs($purchaseReturn->grand_total), 2) }}.</li>
            @elseif($purchaseReturn->refund_type === 'bank')
                <li>Bank transfer refund received from supplier: PKR {{ number_format(abs($purchaseReturn->grand_total), 2) }}.</li>
            @endif
            <li>Return processed on {{ $purchaseReturn->returned_at->format('M d, Y \a\t h:i A') }}</li>
        </ul>
        
        <div style="text-align: center; margin-top: 20px; font-size: 9px;">
            Generated on {{ now()->format('M d, Y \a\t h:i A') }}
        </div>
    </div>
</body>
</html>
