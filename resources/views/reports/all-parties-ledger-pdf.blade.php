<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Parties Ledger</title>
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
        .header .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header .period {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-red {
            color: #dc2626;
        }
        .text-green {
            color: #16a34a;
        }
        .totals-table {
            width: 50%;
            margin-left: auto;
            border: 1px solid #ddd;
        }
        .totals-table td {
            border: none;
            padding: 10px;
        }
        .totals-table .label {
            font-weight: bold;
            color: #555;
        }
        .totals-table .value {
            font-weight: bold;
            text-align: right;
        }
        .party-type {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($company))
            <div class="company-name">{{ $company->company_name }}</div>
        @endif
        <h1>All Parties Ledger</h1>
        <div class="period">
            From: {{ \Carbon\Carbon::parse($start_date)->format('d M, Y') }} 
            To: {{ \Carbon\Carbon::parse($end_date)->format('d M, Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Voucher No</th>
                <th>Party Name</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($item['date'])->format('d M, Y') }}</td>
                <td>{{ $item['voucher_no'] }}</td>
                <td>
                    {{ $item['party_name'] }}
                    <div class="party-type">{{ $item['party_type'] ?? '' }}</div>
                </td>
                <td>{{ $item['description'] }}</td>
                <td class="text-right">
                    @if($item['debit'] > 0)
                        {{ number_format($item['debit'], 2) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">
                    @if($item['credit'] > 0)
                        {{ number_format($item['credit'], 2) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right {{ $item['balance'] >= 0 ? 'text-red' : 'text-green' }}">
                    {{ number_format($item['balance'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Total Opening Balance:</td>
            <td class="value {{ $totals['opening_balance'] >= 0 ? 'text-red' : 'text-green' }}">
                {{ number_format($totals['opening_balance'], 2) }}
            </td>
        </tr>
        <tr>
            <td class="label">Total Debit (Transactions):</td>
            <td class="value">{{ number_format($totals['total_debit'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Credit (Transactions):</td>
            <td class="value">{{ number_format($totals['total_credit'], 2) }}</td>
        </tr>
        <tr style="border-top: 2px solid #333;">
            <td class="label">Closing Balance:</td>
            <td class="value {{ $totals['closing_balance'] >= 0 ? 'text-red' : 'text-green' }}">
                {{ number_format($totals['closing_balance'], 2) }}
            </td>
        </tr>
    </table>
</body>
</html>
