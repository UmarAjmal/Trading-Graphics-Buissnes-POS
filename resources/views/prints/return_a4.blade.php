<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Memo {{ $saleReturn->return_no }}</title>
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
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border: 2px solid #dc2626;
            border-radius: 5px;
        }
        
        .return-title {
            font-size: 20px;
            font-weight: bold;
            color: #dc2626;
            margin: 0;
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
            width: 300px;
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
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .reason-title {
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 5px;
        }

        .refund-amount {
            color: #dc2626;
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
            
            @if($settings && ($settings->ntn || $settings->sales_tax_no))
                <div>
                    @if($settings->ntn)NTN: {{ $settings->ntn }}@endif
                    @if($settings->sales_tax_no) | STN: {{ $settings->sales_tax_no }}@endif
                </div>
            @endif
        </div>
    </div>

    <!-- Return Header -->
    <div class="return-header">
        <h1 class="return-title">RETURN MEMO</h1>
    </div>

    <!-- Meta Information -->
    <div class="meta-section">
        <div>
            <h3>Return Details</h3>
            <p><strong>Return No:</strong> {{ $saleReturn->return_no }}</p>
            <p><strong>Original Invoice:</strong> {{ $saleReturn->sale->invoice_no }}</p>
            <p><strong>Return Date:</strong> {{ $saleReturn->returned_at->format('M d, Y') }}</p>
            <p><strong>Return Time:</strong> {{ $saleReturn->returned_at->format('h:i A') }}</p>
            <p><strong>Processed By:</strong> {{ $saleReturn->user->name }}</p>
            <p><strong>Original Cashier:</strong> {{ $saleReturn->sale->user->name ?? 'Unknown' }}</p>
        </div>
        
        <div>
            @if($saleReturn->sale->customer)
                <h3>Customer Details</h3>
                <p><strong>Name:</strong> {{ $saleReturn->sale->customer->name }}</p>
                @if($saleReturn->sale->customer->phone)
                    <p><strong>Phone:</strong> {{ $saleReturn->sale->customer->phone }}</p>
                @endif
                @if($saleReturn->sale->customer->address)
                    <p><strong>Address:</strong> {{ $saleReturn->sale->customer->address }}</p>
                @endif
            @else
                <h3>Customer Details</h3>
                <p><strong>Name:</strong> Walk-in Customer</p>
            @endif
        </div>
    </div>

    <!-- Return Reason -->
    <div class="reason-section">
        <div class="reason-title">Return Reason:</div>
        <div>{{ $saleReturn->reason }}</div>
    </div>

    <!-- Return Items -->
    <table>
        <thead>
            <tr>
                <th style="width: 40%">Product</th>
                <th style="width: 15%">Returned Qty/Units</th>
                <th style="width: 15%">Rate (PKR)</th>
                <th style="width: 15%">Line Total (PKR)</th>
                <th style="width: 15%">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($saleReturn->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->saleItem->product->name }}</strong>
                        @if($item->saleItem->description)
                            <br><small>{{ $item->saleItem->description }}</small>
                        @endif
                        @if($item->saleItem->product->type === 'panaflex_roll' && $item->length_input && $item->width_input)
                            <br><small style="color: #666;">
                                Original: {{ number_format($item->length_input, 2) }}{{ $item->length_unit }} 
                                × {{ number_format($item->width_input, 2) }}{{ $item->width_unit }}
                            </small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item->saleItem->product->type === 'panaflex_roll')
                            {{ number_format($item->units_sqft, 2) }} sq.ft
                        @else
                            {{ $item->quantity }} {{ $item->saleItem->product->unit->symbol ?? 'pcs' }}
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
                <td class="text-right refund-amount">PKR {{ number_format(abs($saleReturn->subtotal), 2) }}</td>
            </tr>
            @if($saleReturn->discount_total != 0)
                <tr>
                    <td>Discount Adjustment:</td>
                    <td class="text-right">PKR {{ number_format(abs($saleReturn->discount_total), 2) }}</td>
                </tr>
            @endif
            @if($saleReturn->tax_total != 0)
                <tr>
                    <td>Tax Adjustment:</td>
                    <td class="text-right">PKR {{ number_format(abs($saleReturn->tax_total), 2) }}</td>
                </tr>
            @endif
            @if($saleReturn->other_adjustments != 0)
                <tr>
                    <td>
                        @if($saleReturn->other_adjustments < 0)
                            Restocking Fee:
                        @else
                            Goodwill Adjustment:
                        @endif
                    </td>
                    <td class="text-right">PKR {{ number_format(abs($saleReturn->other_adjustments), 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td><strong>REFUND TOTAL:</strong></td>
                <td class="text-right refund-amount"><strong>PKR {{ number_format(abs($saleReturn->grand_total), 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>Important Notes:</strong></p>
        <ul style="margin: 5px 0; padding-left: 20px;">
            <li>This return memo serves as proof of returned merchandise</li>
            <li>Refund amount: PKR {{ number_format(abs($saleReturn->grand_total), 2) }}</li>
            @if($saleReturn->sale->payment_type === 'credit')
                <li>Original sale was on credit - outstanding balance has been adjusted</li>
            @endif
            <li>Return processed on {{ $saleReturn->returned_at->format('M d, Y \a\t h:i A') }}</li>
        </ul>
        
        @if($settings && $settings->print_footer_message)
            <div style="text-align: center; margin-top: 15px; font-style: italic;">
                {{ $settings->print_footer_message }}
            </div>
        @endif
        
        <div style="text-align: center; margin-top: 15px; font-size: 9px;">
            Generated on {{ now()->format('M d, Y \a\t h:i A') }}
        </div>
    </div>
</body>
</html>