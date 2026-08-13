<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
        .print-btn {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-btn:hover {
            background: #4338ca;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }
        
        .header .period {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }
        
        .summary {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .summary h2 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            font-weight: bold;
            color: #555;
        }
        
        .summary-value {
            color: #333;
        }
        
        .table-container {
            margin-top: 20px;
        }
        
        .table-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
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
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .currency {
            text-align: right;
        }
        
        .center {
            text-align: center;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 10px;">
        <button onclick="window.print()" class="print-btn">Print Report</button>
    </div>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="period">{{ $period }} Report - {{ $start_date }} to {{ $end_date }}</div>
        <div class="period">Generated on {{ $generated_at }}</div>
    </div>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total Purchases:</span>
                <span class="summary-value">{{ number_format($summary->total_purchases ?? 0) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Cost:</span>
                <span class="summary-value">Rs {{ number_format($summary->total_cost ?? 0, 0) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Average Purchase:</span>
                <span class="summary-value">Rs {{ number_format($summary->avg_purchase ?? 0, 0) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Unique Suppliers:</span>
                <span class="summary-value">{{ number_format($summary->unique_suppliers ?? 0) }}</span>
            </div>
        </div>
    </div>
    
    <div class="table-container">
        <div class="table-title">Purchase Details</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Purchase No</th>
                    <th>Supplier</th>
                    <th class="currency">Total Cost</th>
                    <th class="center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->date }}</td>
                    <td>{{ $purchase->purchase_no }}</td>
                    <td>{{ $purchase->supplier->name ?? 'Unknown Supplier' }}</td>
                    <td class="currency">Rs {{ number_format($purchase->grand_total, 0) }}</td>
                    <td class="center">{{ ucfirst($purchase->status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <div>{{ config('app.name', 'POS System') }} - Purchase Report</div>
        <div>Page {PAGE_NUM} of {PAGE_COUNT}</div>
    </div>
</body>
</html>