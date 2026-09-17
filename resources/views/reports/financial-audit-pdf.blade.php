<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Executive Financial & Audit Report</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 15px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header h1 {
            color: #0f172a;
            margin: 2px 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header .company {
            color: #2563eb;
            font-size: 15px;
            font-weight: bold;
            margin: 2px 0;
        }

        .header .sub-title {
            font-size: 11px;
            color: #64748b;
            margin: 2px 0;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            font-size: 10px;
        }

        .meta-table td {
            padding: 3px 0;
            border: none;
        }

        /* Executive Health Box */
        .executive-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #2563eb;
            padding: 8px 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .executive-box h3 {
            margin: 0 0 4px 0;
            font-size: 12px;
            color: #0f172a;
        }

        .executive-box p {
            margin: 2px 0;
            font-size: 10.5px;
            color: #334155;
        }

        /* Section Headings */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            background-color: #f1f5f9;
            padding: 5px 8px;
            margin-top: 14px;
            margin-bottom: 6px;
            border-left: 3px solid #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #f8fafc;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 9.5px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-success {
            color: #16a34a;
            font-weight: bold;
        }

        .text-danger {
            color: #dc2626;
            font-weight: bold;
        }

        .text-primary {
            color: #2563eb;
            font-weight: bold;
        }

        .total-row {
            background-color: #f1f5f9;
            font-weight: bold;
        }

        /* Grid for highlights */
        .kpi-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .kpi-table td {
            width: 25%;
            padding: 6px 8px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .kpi-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .kpi-val {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        .kpi-sub {
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Signatures */
        .signatures {
            margin-top: 35px;
            width: 100%;
        }

        .signatures td {
            border: none;
            padding-top: 30px;
            text-align: center;
            width: 33.33%;
        }

        .sig-line {
            border-top: 1px solid #475569;
            display: inline-block;
            width: 80%;
            padding-top: 4px;
            font-weight: bold;
            font-size: 10px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company">{{ $company->company_name ?? 'AL-RAZA TRADERS & PANAFLEX POS' }}</div>
        <h1>Executive Financial & Audit Report</h1>
        <div class="sub-title">Complete Business Position, P&L, Cash Flow, Market Status & Daily Audit</div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <strong>Audit Period:</strong> {{ ucfirst($filters['period']) }} ({{ $filters['start_date'] }} to {{ $filters['end_date'] }}) [{{ $audit['period_info']['days_count'] }} Days]
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>Report Generated:</strong> {{ $generated_at }}
            </td>
        </tr>
    </table>

    <!-- Executive Health Card -->
    <div class="executive-box">
        <h3>📊 Executive Summary & Business Position (خلاصہ حساب کتاب)</h3>
        <p>
            • <strong>Net Profit (خالص کمائی):</strong> During this period, the business achieved a Net Profit of 
            <span class="{{ $audit['profit']['is_profitable'] ? 'text-success' : 'text-danger' }}">
                Rs {{ number_format($audit['profit']['net_profit'], 2) }}
            </span> 
            (Net Margin: {{ $audit['profit']['net_margin'] }}%, Daily Avg Profit: Rs {{ number_format($audit['profit']['profit_per_day'], 2) }}).
        </p>
        <p>
            • <strong>Cash Flow (کیش فلو):</strong> Total Collections received from customers was 
            <strong>Rs {{ number_format($audit['cash_flow']['cash_in_total'], 2) }}</strong>, 
            while Total Outflows (Supplier payments Rs {{ number_format($audit['cash_flow']['cash_out_supplier_total'], 2) }} + Expenses Rs {{ number_format($audit['expenses']['total'], 2) }}) was 
            <strong>Rs {{ number_format($audit['cash_flow']['total_outflow'], 2) }}</strong>.
        </p>
        <p>
            • <strong>Market Standing (مارکیٹ لینا / دینا):</strong> 
            Receivables to collect: <strong>Rs {{ number_format($audit['market']['total_receivables'], 2) }}</strong> | 
            Payables to clear: <strong>Rs {{ number_format($audit['market']['total_payables'], 2) }}</strong> | 
            Net Balance: <strong class="{{ $audit['market']['is_surplus'] ? 'text-success' : 'text-danger' }}">{{ $audit['market']['is_surplus'] ? '+' : '' }}Rs {{ number_format($audit['market']['net_market_balance'], 2) }}</strong>.
        </p>
        <p>
            • <strong>Stock Asset (گودام مالیت):</strong> Current godown inventory holds 
            <strong>{{ number_format($audit['stock']['total_items']) }} items</strong> valued at purchase cost 
            <strong>Rs {{ number_format($audit['stock']['total_value'], 2) }}</strong>.
        </p>
    </div>

    <!-- Top Key Metrics Table -->
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label">Net Sales Revenue</div>
                <div class="kpi-val text-primary">Rs {{ number_format($audit['sales']['net_sales'], 2) }}</div>
                <div class="kpi-sub">{{ $audit['sales']['count'] }} Invoices (Gross: Rs {{ number_format($audit['sales']['gross_sales'], 0) }})</div>
            </td>
            <td>
                <div class="kpi-label">Gross Profit</div>
                <div class="kpi-val text-success">Rs {{ number_format($audit['profit']['gross_profit'], 2) }}</div>
                <div class="kpi-sub">Margin: {{ $audit['profit']['gross_margin'] }}% (COGS: Rs {{ number_format($audit['sales']['net_cogs'], 0) }})</div>
            </td>
            <td>
                <div class="kpi-label">Operating Expenses</div>
                <div class="kpi-val text-danger">Rs {{ number_format($audit['expenses']['total'], 2) }}</div>
                <div class="kpi-sub">{{ $audit['expenses']['count'] }} Expense Vouchers</div>
            </td>
            <td>
                <div class="kpi-label">Net Profit (صافی نفع)</div>
                <div class="kpi-val {{ $audit['profit']['is_profitable'] ? 'text-success' : 'text-danger' }}">
                    Rs {{ number_format($audit['profit']['net_profit'], 2) }}
                </div>
                <div class="kpi-sub">Net Margin: {{ $audit['profit']['net_margin'] }}%</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="kpi-label">Total Purchases</div>
                <div class="kpi-val">Rs {{ number_format($audit['purchases']['total'], 2) }}</div>
                <div class="kpi-sub">{{ $audit['purchases']['count'] }} Bills from Suppliers</div>
            </td>
            <td>
                <div class="kpi-label">Cash In (وصولیاں)</div>
                <div class="kpi-val text-success">Rs {{ number_format($audit['cash_flow']['cash_in_total'], 2) }}</div>
                <div class="kpi-sub">Galla: Rs {{ number_format($audit['cash_flow']['cash_in_galla'], 0) }} | Bank: Rs {{ number_format($audit['cash_flow']['cash_in_bank'], 0) }}</div>
            </td>
            <td>
                <div class="kpi-label">Cash Out (ادائگیاں)</div>
                <div class="kpi-val text-danger">Rs {{ number_format($audit['cash_flow']['total_outflow'], 2) }}</div>
                <div class="kpi-sub">Suppliers: Rs {{ number_format($audit['cash_flow']['cash_out_supplier_total'], 0) }} + Exp</div>
            </td>
            <td>
                <div class="kpi-label">Market Position</div>
                <div class="kpi-val {{ $audit['market']['is_surplus'] ? 'text-success' : 'text-danger' }}">
                    {{ $audit['market']['is_surplus'] ? '+' : '' }}Rs {{ number_format($audit['market']['net_market_balance'], 2) }}
                </div>
                <div class="kpi-sub">Rec: {{ number_format($audit['market']['total_receivables'], 0) }} | Pay: {{ number_format($audit['market']['total_payables'], 0) }}</div>
            </td>
        </tr>
    </table>

    <!-- 1. Income Statement (P&L) -->
    <div class="section-title">1. Income Statement (Profit & Loss / نفع و نقصان سمری)</div>
    <table>
        <thead>
            <tr>
                <th>Particulars / تفصیل</th>
                <th class="text-right">Details (PKR)</th>
                <th class="text-right">Net Amount (PKR)</th>
                <th class="text-right">% of Revenue</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Gross Sales Revenue</strong> ({{ $audit['sales']['count'] }} Sales Invoices)</td>
                <td class="text-right">{{ number_format($audit['sales']['gross_sales'], 2) }}</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td style="padding-left: 20px; color: #dc2626;">Less: Sales Returns & Allowances ({{ $audit['sales']['returns_count'] }} returns)</td>
                <td class="text-right text-danger">({{ number_format($audit['sales']['returns_total'], 2) }})</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
            </tr>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td><strong>Net Sales Revenue</strong></td>
                <td class="text-right">-</td>
                <td class="text-right text-primary">Rs {{ number_format($audit['sales']['net_sales'], 2) }}</td>
                <td class="text-right">100.0%</td>
            </tr>
            <tr>
                <td style="padding-left: 20px; color: #dc2626;">Less: Cost of Goods Sold (COGS / بیچے گئے مال کی لاگت)</td>
                <td class="text-right text-danger">({{ number_format($audit['sales']['net_cogs'], 2) }})</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ $audit['sales']['net_sales'] > 0 ? round(($audit['sales']['net_cogs'] / $audit['sales']['net_sales']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr style="background-color: #ecfdf5; font-weight: bold;">
                <td class="text-success"><strong>GROSS PROFIT (مجموعی منافع)</strong></td>
                <td class="text-right">-</td>
                <td class="text-right text-success">Rs {{ number_format($audit['profit']['gross_profit'], 2) }}</td>
                <td class="text-right text-success">{{ $audit['profit']['gross_margin'] }}%</td>
            </tr>
            <tr>
                <td style="padding-left: 20px; color: #dc2626;">Less: Total Operating Expenses (کاروباری اخراجات)</td>
                <td class="text-right text-danger">({{ number_format($audit['expenses']['total'], 2) }})</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ $audit['sales']['net_sales'] > 0 ? round(($audit['expenses']['total'] / $audit['sales']['net_sales']) * 100, 1) : 0 }}%</td>
            </tr>
            <tr class="total-row" style="background-color: #eff6ff; font-size: 11px;">
                <td class="font-bold"><strong>NET PROFIT / (LOSS) (صافی منافع / کمائی)</strong></td>
                <td class="text-right">-</td>
                <td class="text-right font-bold {{ $audit['profit']['is_profitable'] ? 'text-success' : 'text-danger' }}">
                    Rs {{ number_format($audit['profit']['net_profit'], 2) }}
                </td>
                <td class="text-right font-bold {{ $audit['profit']['is_profitable'] ? 'text-success' : 'text-danger' }}">
                    {{ $audit['profit']['net_margin'] }}%
                </td>
            </tr>
        </tbody>
    </table>

    <!-- 2. Cash Flow Statement & 3. Market Position side-by-side or stacked -->
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 49%; vertical-align: top; padding: 0; border: none;">
                <div class="section-title">2. Cash Flow Summary (کیش فلو)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Cash Flow Item</th>
                            <th class="text-right">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Cash In: Customer Receipts (Cash Galla)</strong></td>
                            <td class="text-right text-success">Rs {{ number_format($audit['cash_flow']['cash_in_galla'], 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Cash In: Bank Transfers / Online</strong></td>
                            <td class="text-right text-success">Rs {{ number_format($audit['cash_flow']['cash_in_bank'], 2) }}</td>
                        </tr>
                        <tr class="total-row" style="background-color: #ecfdf5;">
                            <td><strong>Total Cash In (کل وصولی)</strong></td>
                            <td class="text-right text-success"><strong>Rs {{ number_format($audit['cash_flow']['cash_in_total'], 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Cash Out: Supplier Payments (Galla)</td>
                            <td class="text-right text-danger">Rs {{ number_format($audit['cash_flow']['cash_out_supplier_galla'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Out: Supplier Payments (Bank)</td>
                            <td class="text-right text-danger">Rs {{ number_format($audit['cash_flow']['cash_out_supplier_bank'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Out: Operating Expenses</td>
                            <td class="text-right text-danger">Rs {{ number_format($audit['expenses']['total'], 2) }}</td>
                        </tr>
                        <tr class="total-row" style="background-color: #fef2f2;">
                            <td><strong>Total Cash Out (کل ادائیگی)</strong></td>
                            <td class="text-right text-danger"><strong>Rs {{ number_format($audit['cash_flow']['total_outflow'], 2) }}</strong></td>
                        </tr>
                        <tr class="total-row" style="background-color: #f8fafc;">
                            <td><strong>Net Period Cash Flow (صافی کیش فلو)</strong></td>
                            <td class="text-right {{ $audit['cash_flow']['is_positive_cashflow'] ? 'text-success' : 'text-danger' }}">
                                <strong>{{ $audit['cash_flow']['is_positive_cashflow'] ? '+' : '' }}Rs {{ number_format($audit['cash_flow']['net_cash_flow'], 2) }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td style="width: 2%; border: none;"></td>
            <td style="width: 49%; vertical-align: top; padding: 0; border: none;">
                <div class="section-title">3. Market Position & Stock (مارکیٹ پوزیشن)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Balance Sheet Item</th>
                            <th class="text-right">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Market Receivables (گاہکوں سے لینا ہے)</strong></td>
                            <td class="text-right text-primary">Rs {{ number_format($audit['market']['total_receivables'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Active Debtors (Receivable Parties)</td>
                            <td class="text-right">{{ $audit['market']['receivable_customers_count'] }} Customers</td>
                        </tr>
                        <tr>
                            <td><strong>Market Payables (سپلائرز کو دینا ہے)</strong></td>
                            <td class="text-right text-danger">Rs {{ number_format($audit['market']['total_payables'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Active Creditors (Payable Suppliers)</td>
                            <td class="text-right">{{ $audit['market']['payable_suppliers_count'] }} Suppliers</td>
                        </tr>
                        <tr class="total-row" style="background-color: #f1f5f9;">
                            <td><strong>Net Market Position (صافی بقایا)</strong></td>
                            <td class="text-right {{ $audit['market']['is_surplus'] ? 'text-success' : 'text-danger' }}">
                                <strong>{{ $audit['market']['is_surplus'] ? '+' : '' }}Rs {{ number_format($audit['market']['net_market_balance'], 2) }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td>Godown Stock Valuation (Cost Value)</td>
                            <td class="text-right font-bold">Rs {{ number_format($audit['stock']['total_value'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>Stock Units / Roll Meters</td>
                            <td class="text-right">{{ number_format($audit['stock']['total_qty']) }} Qty / {{ number_format($audit['stock']['total_meters'], 1) }} Mtr</td>
                        </tr>
                        <tr class="total-row" style="background-color: #eff6ff;">
                            <td><strong>Total Business Backing Assets</strong></td>
                            <td class="text-right font-bold text-primary">
                                <strong>Rs {{ number_format($audit['market']['net_market_balance'] + $audit['stock']['total_value'], 2) }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- 4. Expense Category Breakdown -->
    @if(count($audit['expenses']['by_category']) > 0)
    <div class="section-title">4. Operating Expenses by Category (اخراجات کی تفصیل)</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Expense Category</th>
                <th class="text-right">Amount (PKR)</th>
                <th class="text-right">% of Total Expenses</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audit['expenses']['by_category'] as $index => $cat)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $cat['name'] }}</strong></td>
                <td class="text-right text-danger">Rs {{ number_format($cat['amount'], 2) }}</td>
                <td class="text-right">{{ $cat['percentage'] }}%</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>Total Expenses:</strong></td>
                <td class="text-right text-danger"><strong>Rs {{ number_format($audit['expenses']['total'], 2) }}</strong></td>
                <td class="text-right"><strong>100.0%</strong></td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- 5. Daily Audit Timeline Table -->
    @if(count($audit['timeline']) > 0)
    <div class="section-title">5. Daily Timeline Audit Breakdown (روزانہ آڈٹ تفصیل)</div>
    <table>
        <thead>
            <tr>
                <th>Date (Day)</th>
                <th class="text-right">Net Sales</th>
                <th class="text-right">Purchases</th>
                <th class="text-right">Cash In</th>
                <th class="text-right">Cash Out</th>
                <th class="text-right">Expenses</th>
                <th class="text-right">Gross Profit</th>
                <th class="text-right">Net Profit</th>
                <th class="text-right">Net Cashflow</th>
            </tr>
        </thead>
        <tbody>
            @foreach($audit['timeline'] as $day)
            <tr>
                <td><strong>{{ $day['formatted_date'] }}</strong> ({{ $day['day_name'] }})</td>
                <td class="text-right">{{ number_format($day['sales'], 0) }}</td>
                <td class="text-right">{{ number_format($day['purchases'], 0) }}</td>
                <td class="text-right text-success">{{ number_format($day['cash_in'], 0) }}</td>
                <td class="text-right text-danger">{{ number_format($day['cash_out'], 0) }}</td>
                <td class="text-right text-danger">{{ number_format($day['expenses'], 0) }}</td>
                <td class="text-right {{ $day['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($day['gross_profit'], 0) }}
                </td>
                <td class="text-right font-bold {{ $day['is_profitable'] ? 'text-success' : 'text-danger' }}">
                    {{ number_format($day['net_profit'], 0) }}
                </td>
                <td class="text-right {{ $day['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($day['net_cash_flow'], 0) }}
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>Total:</strong></td>
                <td class="text-right"><strong>Rs {{ number_format($audit['sales']['net_sales'], 0) }}</strong></td>
                <td class="text-right"><strong>Rs {{ number_format($audit['purchases']['total'], 0) }}</strong></td>
                <td class="text-right text-success"><strong>Rs {{ number_format($audit['cash_flow']['cash_in_total'], 0) }}</strong></td>
                <td class="text-right text-danger"><strong>Rs {{ number_format($audit['cash_flow']['total_outflow'], 0) }}</strong></td>
                <td class="text-right text-danger"><strong>Rs {{ number_format($audit['expenses']['total'], 0) }}</strong></td>
                <td class="text-right text-success"><strong>Rs {{ number_format($audit['profit']['gross_profit'], 0) }}</strong></td>
                <td class="text-right font-bold {{ $audit['profit']['is_profitable'] ? 'text-success' : 'text-danger' }}">
                    <strong>Rs {{ number_format($audit['profit']['net_profit'], 0) }}</strong>
                </td>
                <td class="text-right {{ $audit['cash_flow']['is_positive_cashflow'] ? 'text-success' : 'text-danger' }}">
                    <strong>Rs {{ number_format($audit['cash_flow']['net_cash_flow'], 0) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Signatures -->
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Prepared By / Accountant</div>
            </td>
            <td>
                <div class="sig-line">Audited & Verified By</div>
            </td>
            <td>
                <div class="sig-line">Business Owner / Director</div>
            </td>
        </tr>
    </table>

</body>
</html>
