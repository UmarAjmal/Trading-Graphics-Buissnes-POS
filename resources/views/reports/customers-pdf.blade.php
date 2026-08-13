<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Report - {{ $customer->name }}</title>
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
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative; /* Needed for absolute positioning of logo */
            min-height: 60px; /* Ensure height for logo */
        }
        
        .header img {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .header h1 {
            color: #333;
            margin: 10px 0 0 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        
        .header .company {
            color: #333;
            margin: 5px 0;
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header .address, .header .contact {
            color: #555;
            font-size: 11px;
            margin: 2px 0;
        }
        
        .header .period {
            color: #666;
            margin: 5px 0;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .customer-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .customer-info h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
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
            background-color: #f0f7ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
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
        
        .summary-value.receivable {
            color: #dc2626;
        }
        
        .summary-value.payable {
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
        
        .balance-receivable {
            color: #dc2626;
            font-weight: bold;
        }
        
        .balance-payable {
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
            background-color: #3b82f6;
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
            background-color: #2563eb;
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
        @php
            $logoSrc = null;
            if ($company->logo_path) {
                // Try public path first (served via web)
                $path = public_path('storage/' . $company->logo_path);
                
                // Fallback to storage path (internal) if symlink issues
                if (!file_exists($path)) {
                    $path = storage_path('app/public/' . $company->logo_path);
                }

                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    $logoSrc = $base64;
                }
            }
        @endphp
        
        @if($logoSrc)
            <img src="{{ $logoSrc }}" alt="Company Logo" style="position: absolute; left: 0; top: 0; max-height: 50px; width: auto;">
        @endif
        
        <div class="company">{{ $company->company_name ?? config('app.name', 'Narmer POS') }}</div>
        
        @if(isset($company))
            @if($company->address)
                <div class="address">{{ $company->address }}</div>
            @endif
            @if($company->phone_1 || $company->email)
                <div class="contact">
                    @if($company->phone_1) Phone: {{ $company->phone_1 }} @endif
                    @if($company->phone_1 && $company->email) | @endif
                    @if($company->email) Email: {{ $company->email }} @endif
                </div>
            @endif
        @endif
    </div>
    
    <div class="customer-info">
        <h2 style="border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px;">Customer Ledger</h2>
        <div class="info-grid" style="display: table; width: 100%;">
            <div style="display: table-row;">
                <div style="display: table-cell; padding: 5px;">
                    <strong>Customer:</strong> {{ $customer->name }}
                </div>
                <div style="display: table-cell; padding: 5px; text-align: right;">
                    <strong>Period:</strong> {{ $date_from }} to {{ $date_to }}
                </div>
            </div>
            <div style="display: table-row;">
                <div style="display: table-cell; padding: 5px;">
                    <strong>Phone:</strong> {{ $customer->phone ?? 'N/A' }}
                </div>
                <div style="display: table-cell; padding: 5px; text-align: right;">
                    <strong>Generated:</strong> {{ now()->format('d M Y h:i A') }}
                </div>
            </div>
            @if($customer->address)
            <div style="display: table-row;">
                <div style="display: table-cell; padding: 5px; width: 100%;" colspan="2">
                    <strong>Address:</strong> {{ $customer->address }}
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Sr</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Voucher No</th>
                    <th class="currency">Debit</th>
                    <th class="currency">Credit</th>
                    <th class="currency">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $index => $entry)
                <tr class="{{ $entry['type'] === 'opening_balance' ? 'opening-balance' : '' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $entry['formatted_date'] }}</td>
                    <td>{{ $entry['description'] }}</td>
                    <td>{{ $entry['voucher_no'] ?? '-' }}</td>
                    <td class="currency">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                    <td class="currency">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                    <td class="currency {{ $entry['balance'] >= 0 ? 'balance-receivable' : 'balance-payable' }}">
                        {{ number_format(abs($entry['balance']), 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="4" style="text-align: right; padding-right: 10px;">TOTALS</td>
                    <td class="currency">{{ number_format($totals['debit'] ?? 0, 2) }}</td>
                    <td class="currency">{{ number_format($totals['credit'] ?? 0, 2) }}</td>
                    <td></td>
                </tr>
                <tr style="background-color: #e6e6e6; font-weight: bold; border-top: 2px solid #333;">
                    <td colspan="6" style="text-align: right; padding-right: 10px;">CLOSING BALANCE</td>
                    <td class="currency {{ ($totals['closing_balance'] ?? 0) >= 0 ? 'balance-receivable' : 'balance-payable' }}">
                        {{ number_format(abs($totals['closing_balance'] ?? 0), 2) }}
                        <br>
                        <small style="font-weight: normal; font-size: 9px; color: #555;">{{ ($totals['closing_balance'] ?? 0) >= 0 ? '(Receivable)' : '(Advance)' }}</small>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- <div class="footer">
        <div>{{ config('app.name', 'POS System') }} - Customer Report</div>
        <div>Page {PAGE_NUM} of {PAGE_COUNT}</div>
    </div> -->
</body>
</html>
