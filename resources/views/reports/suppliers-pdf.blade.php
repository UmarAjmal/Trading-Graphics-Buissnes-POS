<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Report - {{ $supplier->name }}</title>
    <style>
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
        
        .header .company {
            color: #666;
            margin: 5px 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .header .period {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }
        
        .supplier-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .supplier-info h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .info-item {
            padding: 5px 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .summary {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f59e0b;
        }
        
        .summary h2 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .summary-item {
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
        }
        
        .summary-label {
            font-weight: bold;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .summary-value.payable {
            color: #dc2626;
        }
        
        .summary-value.prepayment {
            color: #16a34a;
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
            font-size: 11px;
            text-transform: uppercase;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr.opening-balance {
            background-color: #fff3cd;
            font-weight: bold;
        }
        
        .currency {
            text-align: right;
        }
        
        .center {
            text-align: center;
        }
        
        .balance-payable {
            color: #dc2626;
            font-weight: bold;
        }
        
        .balance-prepayment {
            color: #16a34a;
            font-weight: bold;
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
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #f59e0b;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .print-button:hover {
            background-color: #d97706;
        }
        
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
    <script>
        // Auto-open print dialog when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        function printPDF() {
            window.print();
        }
    </script>
</head>
<body>
    <!-- Download/Print Button -->
    <button onclick="printPDF()" class="print-button">📥 Download as PDF</button>
    
    <div class="header">
        <div class="company">{{ config('app.name', 'POS System') }}</div>
        <h1>Supplier Report</h1>
        <div class="period">{{ $date_from }} to {{ $date_to }}</div>
        <div class="period">Generated on {{ $generated_at }}</div>
    </div>
    
    <div class="supplier-info">
        <h2>Supplier Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $supplier->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $supplier->phone ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $supplier->email ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Address:</span>
                <span class="info-value">{{ $supplier->address ?? 'N/A' }}</span>
            </div>
        </div>
    </div>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Purchases</div>
                <div class="summary-value">Rs {{ number_format($summary['total_purchases'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Payments</div>
                <div class="summary-value">Rs {{ number_format($summary['total_payments'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Current Balance</div>
                <div class="summary-value {{ $summary['balance'] >= 0 ? 'payable' : 'prepayment' }}">
                    Rs {{ number_format(abs($summary['balance'] ?? 0), 2) }}
                    <br>
                    <small style="font-size: 12px;">{{ $summary['balance'] >= 0 ? '(Payable)' : '(Prepayment)' }}</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="table-container">
        <div class="table-title">Supplier Ledger</div>
        <table>
            <thead>
                <tr>
                    <th>Sr</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="currency">Debit</th>
                    <th class="currency">Credit</th>
                    <th class="currency">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ledger as $index => $entry)
                <tr class="{{ $entry['description'] === 'Opening Balance (B/F)' ? 'opening-balance' : '' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                    <td>{{ $entry['description'] }}</td>
                    <td class="currency">{{ $entry['purchase_amount'] > 0 ? number_format($entry['purchase_amount'], 2) : '-' }}</td>
                    <td class="currency">
                        @php
                            $credit = ($entry['payment'] ?? 0) + ($entry['prepayment'] ?? 0);
                        @endphp
                        {{ $credit > 0 ? number_format($credit, 2) : '-' }}
                    </td>
                    <td class="currency {{ $entry['balance'] >= 0 ? 'balance-payable' : 'balance-prepayment' }}">
                        {{ number_format(abs($entry['balance']), 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <div>{{ config('app.name', 'POS System') }} - Supplier Report</div>
        <div>Page {PAGE_NUM} of {PAGE_COUNT}</div>
    </div>
</body>
</html>
