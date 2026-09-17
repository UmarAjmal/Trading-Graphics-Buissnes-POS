<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Return Memo {{ $purchaseReturn->return_no }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 10px;
            font-size: 11px;
            line-height: 1.3;
            max-width: 300px;
            color: #000;
            position: relative;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #333;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .logo {
            max-height: 40px;
            margin-bottom: 5px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .company-tagline {
            font-size: 10px;
            margin-bottom: 5px;
        }
        
        .company-details {
            font-size: 9px;
            line-height: 1.2;
        }
        
        .return-header {
            text-align: center;
            margin-bottom: 10px;
            padding: 5px;
            border: 1px solid #333;
            background-color: #f5f5f5;
        }
        
        .return-title {
            font-size: 13px;
            font-weight: bold;
            margin: 0;
        }

        .return-badge {
            font-size: 9px;
            font-weight: bold;
            margin-top: 2px;
        }
        
        .meta-section {
            margin-bottom: 10px;
            font-size: 10px;
        }
        
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .meta-label {
            font-weight: bold;
        }
        
        .separator {
            border-top: 1px dashed #333;
            margin: 8px 0;
        }
        
        .items-header {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }
        
        .item {
            margin-bottom: 8px;
            font-size: 9px;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
        }
        
        .reason-section {
            margin: 10px 0;
            padding: 5px;
            border: 1px solid #333;
            font-size: 9px;
        }
        
        .reason-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .totals-section {
            margin-top: 10px;
            font-size: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .grand-total {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            font-weight: bold;
            padding: 3px 0;
            margin-top: 5px;
        }
        
        .footer {
            margin-top: 15px;
            font-size: 8px;
            text-align: center;
            border-top: 1px dashed #333;
            padding-top: 10px;
        }
        
        .refund-amount {
            font-weight: bold;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 5px;
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
            
            @if($settings && ($settings->phone_1 || $settings->phone_2 || $settings->whatsapp_number))
                <div>
                    @if($settings->phone_1)Ph: {{ $settings->phone_1 }}@endif
                    @if($settings->phone_2) | {{ $settings->phone_2 }}@endif
                    @if($settings->whatsapp_number) | WA: {{ $settings->whatsapp_number }}@endif
                </div>
            @endif
        </div>
    </div>

    <!-- Return Header -->
    <div class="return-header">
        <div class="return-title">PURCHASE RETURN MEMO</div>
        <div class="return-badge">** SUPPLIER RETURN **</div>
    </div>

    <!-- Meta Information -->
    <div class="meta-section">
        <div class="meta-row">
            <span class="meta-label">Return No:</span>
            <span>{{ $purchaseReturn->return_no }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Purchase Bill:</span>
            <span>{{ $purchaseReturn->purchase->purchase_no ?? 'N/A' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Date:</span>
            <span>{{ $purchaseReturn->returned_at->format('M d, Y h:i A') }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Supplier:</span>
            <span>{{ $purchaseReturn->supplier->name ?? 'Direct Supplier' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Method:</span>
            <span style="text-transform: uppercase; font-weight: bold;">{{ $purchaseReturn->refund_type }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">Processed By:</span>
            <span>{{ $purchaseReturn->user->name ?? 'Admin' }}</span>
        </div>
    </div>

    <div class="separator"></div>

    <!-- Return Reason -->
    <div class="reason-section">
        <div class="reason-title">Reason / Note:</div>
        <div>{{ $purchaseReturn->reason }}</div>
    </div>

    <!-- Items -->
    <div class="items-header">RETURNED ITEMS</div>
    
    @foreach($purchaseReturn->items as $item)
        <div class="item">
            <div class="item-name">{{ $item->product->name ?? 'Product' }}</div>
            
            <div class="item-details">
                <span>Qty / Rolls:</span>
                <span>
                    @if($item->product && $item->product->type === 'panaflex_roll')
                        {{ number_format($item->rolls_count, 2) }} Rolls
                    @else
                        {{ $item->quantity }} pcs
                    @endif
                </span>
            </div>
            
            <div class="item-details">
                <span>Rate:</span>
                <span>PKR {{ number_format($item->rate, 2) }}</span>
            </div>
            
            <div class="item-details">
                <span>Return Total:</span>
                <span class="refund-amount">PKR {{ number_format(abs($item->line_total), 2) }}</span>
            </div>
            
            @if($item->note)
                <div style="font-size: 8px; color: #666; margin-top: 2px;">
                    Note: {{ $item->note }}
                </div>
            @endif
        </div>
    @endforeach

    <div class="separator"></div>

    <!-- Totals -->
    <div class="totals-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span class="refund-amount">PKR {{ number_format(abs($purchaseReturn->subtotal), 2) }}</span>
        </div>
        
        @if($purchaseReturn->other_adjustments != 0)
            <div class="total-row">
                <span>Adjustment:</span>
                <span>PKR {{ number_format($purchaseReturn->other_adjustments, 2) }}</span>
            </div>
        @endif
        
        <div class="total-row grand-total">
            <span>TOTAL REFUND:</span>
            <span class="refund-amount">PKR {{ number_format(abs($purchaseReturn->grand_total), 2) }}</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Stock deducted & supplier ledger updated</div>
        <div>Method: {{ strtoupper($purchaseReturn->refund_type) }}</div>
        <div>{{ $purchaseReturn->returned_at->format('M d, Y \a\t h:i A') }}</div>
        
        <div style="margin-top: 8px;">
            Generated: {{ now()->format('M d, Y h:i A') }}
        </div>
    </div>
</body>
</html>
