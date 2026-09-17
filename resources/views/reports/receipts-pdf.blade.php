<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt Report (Cash In)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #16a34a;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #16a34a;
            margin: 0 0 5px 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header .company {
            font-size: 14px;
            font-weight: bold;
            color: #111;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 15px;
        }
        .meta-info td {
            border: none;
            padding: 2px 0;
            font-size: 10px;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .summary-box td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: center;
            background-color: #f0fdf4;
        }
        .summary-box .title {
            font-size: 9px;
            color: #4b5563;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-box .val {
            font-size: 13px;
            font-weight: bold;
            color: #15803d;
            margin-top: 3px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
        }
        table.data-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 9px;
            border-radius: 3px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-cash { background-color: #dcfce7; color: #15803d; }
        .badge-bank { background-color: #dbeafe; color: #1d4ed8; }
        .badge-other { background-color: #f3f4f6; color: #4b5563; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .amount { font-weight: bold; color: #16a34a; }
        .footer {
            position: fixed;
            bottom: 10px;
            left: 15px;
            right: 15px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">{{ $company->company_name ?? 'AL-RAZA TRADERS' }}</div>
        <h1>Receipt Report (Cash In)</h1>
        <div style="font-size: 10px; color: #6b7280;">Customer Collections & Inflow Audit</div>
    </div>

    <table class="meta-info">
        <tr>
            <td><strong>Period:</strong> {{ $filters['start_date'] ? $filters['start_date'] . ' to ' . ($filters['end_date'] ?? 'Now') : 'All Time' }}</td>
            <td class="text-right"><strong>Generated On:</strong> {{ $generated_at }}</td>
        </tr>
    </table>

    <table class="summary-box">
        <tr>
            <td>
                <div class="title">Total Receipts (Cash In)</div>
                <div class="val">Rs {{ number_format($summary['total_amount'], 2) }}</div>
            </td>
            <td>
                <div class="title">Cash (Galla)</div>
                <div class="val" style="color: #16a34a;">Rs {{ number_format($summary['cash_amount'], 2) }}</div>
            </td>
            <td>
                <div class="title">Bank Transfers</div>
                <div class="val" style="color: #2563eb;">Rs {{ number_format($summary['bank_amount'], 2) }}</div>
            </td>
            <td>
                <div class="title">Total Receipts Count</div>
                <div class="val" style="color: #4b5563;">{{ $summary['count'] }} Vouchers</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 70px;">Date</th>
                <th style="width: 70px;">Voucher #</th>
                <th>Customer / Party Name</th>
                <th style="width: 80px;">Method</th>
                <th>Note / Reference</th>
                <th class="text-right" style="width: 90px;">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receipts as $index => $r)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->payment_date)->format('Y-m-d') }}</td>
                    <td style="font-family: monospace;">VCH-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        <strong>{{ $r->customer->name ?? 'Walk-in Customer' }}</strong>
                        @if(!empty($r->customer->phone))
                            <div style="font-size: 9px; color: #6b7280;">{{ $r->customer->phone }}</div>
                        @endif
                    </td>
                    <td>
                        @if($r->payment_method === 'cash')
                            <span class="badge badge-cash">Cash</span>
                        @elseif(in_array($r->payment_method, ['bank', 'bank_transfer']))
                            <span class="badge badge-bank">Bank</span>
                        @else
                            <span class="badge badge-other">{{ ucfirst($r->payment_method) }}</span>
                        @endif
                    </td>
                    <td style="color: #6b7280; font-size: 10px;">{{ $r->note ?: '-' }}</td>
                    <td class="text-right amount">{{ number_format($r->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #9ca3af;">
                        No receipt records found for the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f3f4f6; font-weight: bold;">
                <td colspan="6" class="text-right" style="text-transform: uppercase;">Total Collections (Cash In):</td>
                <td class="text-right" style="color: #16a34a; font-size: 12px;">Rs {{ number_format($summary['total_amount'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        {{ $company->company_name ?? 'AL-RAZA TRADERS' }} | Generated by System | Page 1
    </div>
</body>
</html>
