<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receivables Report</title>
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
            position: relative;
            min-height: 60px;
        }
        
        .header img {
            max-height: 50px;
            margin-bottom: 10px;
        }

        .header h1 {
            color: #333;
            margin: 5px 0 0 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        
        .header .company {
            color: #333;
            margin: 5px 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .meta-info {
            text-align: right;
            margin-bottom: 10px;
            font-size: 10px;
            color: #555;
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
        
        .currency {
            text-align: right;
        }
        
        .receivable {
            color: #dc2626;
        }
        
        .payable {
            color: #16a34a;
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
    <div class="header">
        @php
            $logoSrc = null;
            if ($company->logo_path) {
                $path = public_path('storage/' . $company->logo_path);
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
        
        <div class="company">{{ $company->company_name ?? config('app.name') }}</div>
        <h1>Receivables Report</h1>
        <div class="meta-info">
            Balance As Of: {{ $generated_at }} | Filter: {{ ucfirst($type) }}
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">Sr</th>
                <th>Customer Name</th>
                <th>Phone / Contact</th>
                <th class="currency">Balance (Rs)</th>
                <th class="currency" style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalReceivable = 0;
                $totalAdvance = 0;
            @endphp
            
            @foreach($customers as $index => $customer)
                @php
                    if ($customer['balance'] > 0) $totalReceivable += $customer['balance'];
                    else $totalAdvance += $customer['balance'];
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $customer['name'] }}</td>
                    <td>{{ $customer['phone'] ?? '-' }}</td>
                    <td class="currency {{ $customer['balance'] >= 0 ? 'receivable' : 'payable' }}" style="font-weight: bold;">
                        {{ number_format(abs($customer['balance']), 2) }}
                    </td>
                    <td class="currency" style="font-size: 10px;">
                        {{ $customer['balance'] >= 0 ? 'RECEIVABLE' : 'ADVANCE' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #eee; font-weight: bold;">
                <td colspan="3" style="text-align: right;">TOTAL RECEIVABLES</td>
                <td class="currency receivable">{{ number_format($totalReceivable, 2) }}</td>
                <td></td>
            </tr>
            <tr style="background-color: #eee; font-weight: bold;">
                <td colspan="3" style="text-align: right;">TOTAL ADVANCES</td>
                <td class="currency payable">{{ number_format(abs($totalAdvance), 2) }}</td>
                <td></td>
            </tr>
            <tr style="background-color: #333; color: white; font-weight: bold;">
                <td colspan="3" style="text-align: right;">NET BALANCE</td>
                <td class="currency">
                    {{ number_format(abs($totalReceivable + $totalAdvance), 2) }}
                    {{ ($totalReceivable + $totalAdvance) >= 0 ? '(DR)' : '(CR)' }}
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        {{ config('app.name') }} - Receivables Report - Page {PAGE_NUM} of {PAGE_COUNT}
    </div>
</body>
</html>
