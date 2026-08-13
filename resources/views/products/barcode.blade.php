<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Barcode - {{ $product->name }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            
            /* Ensure barcode SVG elements print correctly */
            .barcode svg {
                max-width: 100% !important;
                height: auto !important;
                display: block !important;
                visibility: visible !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            .barcode svg rect {
                fill: #000 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            .barcode svg path {
                fill: #000 !important;
                stroke: #000 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            /* Ensure the page layout is preserved */
            .page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10mm !important;
            }
            
            .label {
                border: 1px solid #000 !important;
                page-break-inside: avoid !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 10mm;
        }
        
        .label-grid {
            display: grid;
            @if($layout === '2x6')
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(6, 1fr);
            @elseif($layout === '2x12')
                grid-template-columns: repeat(2, 1fr);
                grid-template-rows: repeat(12, 1fr);
            @else
                grid-template-columns: repeat(3, 1fr);
                grid-template-rows: repeat(8, 1fr);
            @endif
            gap: 5mm;
            height: 277mm;
        }
        
        .label {
            border: 1px dashed #ccc;
            padding: 2mm;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.2;
            page-break-inside: avoid;
            @if($layout === '2x6')
                font-size: 12px; /* Larger text for 2x6 layout */
            @elseif($layout === '2x12')
                font-size: 9px; /* Smaller text for 2x12 layout */
            @else
                font-size: 10px; /* Standard size for 3x8 layout */
            @endif
        }
        
        .product-name {
            font-weight: bold;
            margin-bottom: 2px;
            @if($layout === '2x6')
                font-size: 13px;
            @elseif($layout === '2x12')
                font-size: 10px;
            @else
                font-size: 11px;
            @endif
        }
        
        .sku {
            color: #666;
            margin-bottom: 3px;
        }
        
        .price {
            color: #333;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .barcode {
            margin: 2px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .barcode svg {
            max-width: 100%;
            height: auto;
            display: block;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        
        .barcode svg rect {
            fill: #000;
        }
        
        .barcode svg path {
            fill: #000;
            stroke: #000;
        }
        
        .barcode-text {
            font-family: monospace;
            margin-top: 1px;
            @if($layout === '2x6')
                font-size: 10px;
            @elseif($layout === '2x12')
                font-size: 8px;
            @else
                font-size: 9px;
            @endif
        }
        
        .print-controls {
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        
        .btn {
            background: #007cba;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #005a87;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <h2>Barcode Labels - {{ $product->name }}</h2>
        <p>Generating {{ $quantity }} labels for printing on A4 sheet 
            @if($layout === '2x6')
                (2 columns × 6 rows - 12 per page)
            @elseif($layout === '2x12')
                (2 columns × 12 rows - 24 per page)
            @else
                (3 columns × 8 rows - 24 per page)
            @endif
        </p>
        <button class="btn" onclick="window.print()">🖨️ Print Labels</button>
        <button class="btn btn-secondary" onclick="window.close()">✕ Close</button>
    </div>

    <div class="page">
        <div class="label-grid">
            @php
                $labelsPerPage = $layout === '2x6' ? 12 : 24;
            @endphp
            @for($i = 0; $i < min($quantity, $labelsPerPage); $i++)
            <div class="label">
                <div class="product-name">{{ Str::limit($product->name, 25) }}</div>
                <div class="sku">SKU: {{ $product->sku }}</div>
                @if($product->sale_rate > 0)
                <div class="price">Rs. {{ number_format($product->sale_rate, 0) }}</div>
                @endif
                <div class="barcode">
                    {!! $barcodeHtml !!}
                </div>
                <div class="barcode-text">{{ $product->barcode ?: $product->sku }}</div>
            </div>
            @endfor
            
            {{-- Fill remaining slots with empty labels if needed --}}
            @for($i = min($quantity, $labelsPerPage); $i < $labelsPerPage; $i++)
            <div class="label" style="border: 1px dashed #eee;">
                {{-- Empty label --}}
            </div>
            @endfor
        </div>
    </div>

    @if($quantity > $labelsPerPage)
    {{-- Additional pages for quantities over first page --}}
    @php
        $remainingQuantity = $quantity - $labelsPerPage;
        $additionalPages = ceil($remainingQuantity / $labelsPerPage);
    @endphp
    
    @for($page = 1; $page <= $additionalPages; $page++)
    <div class="page" style="page-break-before: always;">
        <div class="label-grid">
            @for($i = 0; $i < min($remainingQuantity, $labelsPerPage); $i++)
            <div class="label">
                <div class="product-name">{{ Str::limit($product->name, 25) }}</div>
                <div class="sku">SKU: {{ $product->sku }}</div>
                @if($product->sale_rate > 0)
                <div class="price">Rs. {{ number_format($product->sale_rate, 0) }}</div>
                @endif
                <div class="barcode">
                    {!! $barcodeHtml !!}
                </div>
                <div class="barcode-text">{{ $product->barcode ?: $product->sku }}</div>
            </div>
            @endfor
            
            {{-- Fill remaining slots --}}
            @for($i = min($remainingQuantity, $labelsPerPage); $i < $labelsPerPage; $i++)
            <div class="label" style="border: 1px dashed #eee;"></div>
            @endfor
        </div>
    </div>
    @php $remainingQuantity -= $labelsPerPage; @endphp
    @endfor
    @endif
</body>
</html>