
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        // Determine Customer Name for Filename
        $fName = $sale->customer ? $sale->customer->name : 'Walk-in Customer';
        if (!$sale->customer && $sale->notes && preg_match('/Walk-in Name: (.*?)(\n|$)/', $sale->notes, $matches)) {
            $fName = $matches[1];
        }
        // Sanitize for filename (spaces to _, remove special chars)
        $fNameSanitized = preg_replace('/[^A-Za-z0-9\- ]/', '', $fName);
        $fNameSanitized = str_replace(' ', '_', $fNameSanitized);
        
        $dateStr = now()->format('d-M-Y');
        $fileName = "{$fNameSanitized}_{$sale->invoice_no}_{$dateStr}";
    @endphp
    <title>{{ $fileName }}</title>
    <style>
        @page {
            size: 148mm 210mm; /* A5 Size */
            margin: 5mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 8px; /* Compact font */
            line-height: 1.2;
            color: #000;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            font-weight: bold;
            color: rgba(200, 200, 200, 0.2);
            z-index: -1;
            pointer-events: none;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .logo {
            max-height: 40px; /* Smaller Logo */
            margin-bottom: 2px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        
        .company-tagline {
            font-size: 9px;
            color: #333;
            margin-bottom: 3px;
        }
        
        .company-details {
            font-size: 9px;
            color: #333;
        }
        
        .invoice-meta {
            width: 100%;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        
        .meta-box {
            width: 48%;
            float: left;
        }

        /* Clearfix for float layout if flex fails in some PDF engines */
        .invoice-meta::after {
            content: "";
            clear: both;
            display: table;
        }
        
        .invoice-meta h3 {
            margin: 0 0 3px 0;
            font-size: 11px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
            text-transform: uppercase;
        }
        
        .invoice-meta p {
            margin: 2px 0;
            font-size: 9px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 8px;
        }
        
        th {
            background-color: #eee;
            font-weight: bold;
            text-align: center;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .totals-section {
            width: 200px; /* Reduced width */
            float: right;
            margin-bottom: 10px;
        }
        
        .totals-section table {
            margin: 0;
        }
        
        .totals-section th, .totals-section td {
            border: none;
            border-bottom: 1px solid #eee;
            padding: 2px 5px;
        }
        
        .grand-total {
            font-weight: bold;
            font-size: 10px;
            background-color: #eee;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000 !important;
        }
        
        .footer {
            clear: both;
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            font-size: 8px;
            color: #555;
        }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>




    @php
        $grossTotal = (float) ($sale->subtotal ?? 0);
        $discountTotal = (float) ($sale->discount_total ?? 0);
        $taxTotal = (float) ($sale->tax_total ?? 0);
        $utilitiesCharges = (float) ($sale->utilities_charges ?? 0);
        $otherCharges = (float) ($sale->other_charges ?? 0);
        
        // Calculate Current Bill Total
        $currentBillTotal = $grossTotal - $discountTotal + $taxTotal + $utilitiesCharges + $otherCharges;
        
        // Previous Balance Logic
        $previousBalance = (float) ($sale->previous_balance ?? 0);
        $isNegativeBalance = $previousBalance < 0;
        $previousBalanceAbs = abs($previousBalance);
        
        // Determine Advance Available
        // 1. From Ledger (Negative Previous Balance)
        $ledgerAdvanceAvailable = $isNegativeBalance ? $previousBalanceAbs : 0;
        
        // 2. From Advances Table (Reconstructed)
        $explicitAdvanceUsed = (float) ($sale->advance_used ?? 0);
        $currentRemainingAdvance = $sale->customer ? (float) ($sale->customer->current_advance_balance ?? 0) : 0;
        $tableAdvanceAvailable = $currentRemainingAdvance + $explicitAdvanceUsed;
        
        // Use the greater of the two to handle both legacy (ledger-only) and new (advances-table) data
        $customerAdvanceAvailable = max($ledgerAdvanceAvailable, $tableAdvanceAvailable);
        
        // Determine Advance Used
        if ($explicitAdvanceUsed > 0) {
            $advanceUsed = $explicitAdvanceUsed;
        } else {
            // If no explicit advance, check if cash paid covers the bill
            $cashPaid = (float) ($sale->paid_amount ?? 0);
            
            if ($cashPaid >= $currentBillTotal) {
                // If fully paid by cash, NO advance is used
                $advanceUsed = 0;
            } else {
                // Otherwise, simulate usage against the bill
                $advanceUsed = min($customerAdvanceAvailable, $currentBillTotal - $cashPaid);
            }
        }
        
        // Calculate Final Totals
        // If previous balance was negative (Advance), we treat it as 0 for the "Due" calculation
        // because we are handling it via the Advance section.
        $previousDues = $isNegativeBalance ? 0 : $previousBalance;
        
        $payableBeforeAdvance = max(0, $currentBillTotal + $previousDues);
        $computedGrandTotal = max(0, $payableBeforeAdvance - $advanceUsed);
        $remainingAdvance = max(0, $customerAdvanceAvailable - $advanceUsed);

        // Extract Walk-in Name from notes
        $walkInName = 'Walk-in Customer';
        if ($sale->notes && preg_match('/Walk-in Name: (.*?)(\n|$)/', $sale->notes, $matches)) {
            $walkInName = $matches[1];
        }
    @endphp

    @if(isset($isCopy) && $isCopy)
        <div class="watermark">COPY</div>
    @endif

    <div class="header" style="text-align: left; display: flex; align-items: center; justify-content: space-between; padding: 10px 10px;">
        <div style="display: flex; align-items: center;padding: 5px 5px;">
            @if($settings && $settings->logo_url)
                <img src="{{ $settings->logo_url }}" alt="Logo" class="logo" style="margin-right: 15px;">
            @endif
            
            <div>
                <div class="company-name" style="margin-bottom: 5px;">{{ $settings->company_name ?? 'POS System' }}</div>
                
                <!-- @if($settings && $settings->tagline)
                    <div class="company-tagline">{{ $settings->tagline }}</div>
                @endif -->
                
                <div class="company-details">
                    @if($settings && $settings->address)
                        <div>{{ $settings->address }}</div>
                    @endif
                    
                    @if($settings && ($settings->phone_1 || $settings->phone_2 || $settings->whatsapp_number || $settings->email))
                        <div>
                            @if($settings->phone_1)Phone: {{ $settings->phone_1 }}@endif
                            @if($settings->phone_2) | {{ $settings->phone_2 }}@endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div style="text-align: right;">
            <h1 style="margin: 0; font-size: 10px; text-transform: uppercase;">SAL-Invoice</h1>
        </div>
    </div>

    <!-- Invoice Meta -->
    <div class="invoice-meta" style="border: 1px solid #000; padding: 10px 15px; margin-bottom: 15px; box-sizing: border-box;">
        <div style="width: 40%; padding-right: 10px; line-height: 1.5;">
            @if($sale->customer)
                <h3 style="margin-top: 0; font-size: 10px;">Customer Detail</h3>
                <p><strong>Name:</strong> {{ $sale->customer->name === 'Walk-in Customer' ? $walkInName : $sale->customer->name }}</p>
                <p><strong>Adv Available:</strong> PKR {{ number_format($customerAdvanceAvailable, 2) }}</p>
            @else
                <h3 style="margin-top: 0;">{{ $walkInName }}</h3>
                <p>Cash Sale</p>
            @endif
        </div>
        
        <div style="width: 40%; text-align: right;">
            <div style="display: inline-block; text-align: left; line-height: 1.6;">
                <p><strong>Date:</strong> {{ ($sale->invoice_date ?? $sale->sold_at)->format('M d, Y') }}</p>
                <p><strong>INV-No:</strong> {{ $sale->invoice_no }}</p>
                <p><strong>Payment:</strong> {{ ucfirst($sale->payment_type) }}</p>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 20%">Product Name</th>
                <th style="width: 15%">Description</th>
                <th style="width: 8%">Width</th>
                <th style="width: 8%">Length</th>
                <th style="width: 5%">Unit</th>
                <th style="width: 10%">Qty (sq.ft)</th>
                <th style="width: 12%">Rate (PKR)</th>
                <th style="width: 17%">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->saleItems as $index => $item)
                @php
                    $displayName = '';
                    $displayDesc = '';

                    // Function to check if a string looks like dimensions (e.g. "12x4", "10*5", "2ft", "3m")
                    $isDimension = function($str) {
                         $str = strtolower(trim($str));
                         // Strict Pattern: Number + [x*] + Number + Optional Unit
                         // e.g. 10x10, 10*5, 12.5x4, 10x5ft
                         if (preg_match('/^\d+(\.\d+)?\s*[x*]\s*\d+(\.\d+)?(\s*(ft|in|m|mm|cm))?$/', $str)) {
                              return true;
                         }
                         // Strict Pattern: Number + Unit
                         // e.g. 2ft, 10m, 5in
                         if (preg_match('/^\d+(\.\d+)?\s*(ft|in|m|mm|cm)$/', $str)) {
                              return true;
                         }
                         
                         return false;
                    };

                    // Function to clean description parts
                    $cleanDescription = function($desc) use ($isDimension) {
                        if (!$desc) return '';
                        $parts = explode('|', $desc);
                        $candidates = [];
                        
                        // First pass: Filter dimensions and empties
                        foreach ($parts as $p) {
                            $p = trim($p);
                            if (empty($p)) continue;
                            if (!$isDimension($p)) {
                                $candidates[] = $p;
                            }
                        }
                        
                        // Deduplicate exact matches
                        $candidates = array_unique($candidates);
                        
                        // Second pass: Remove redundant substrings
                        // If "A" is contained in "A (B)", we remove "A" and keep "A (B)"
                        $finalParts = [];
                        foreach ($candidates as $k1 => $p1) {
                            $isSubstring = false;
                            foreach ($candidates as $k2 => $p2) {
                                if ($k1 !== $k2 && str_contains($p2, $p1)) {
                                    $isSubstring = true;
                                    break;
                                }
                            }
                            if (!$isSubstring) {
                                $finalParts[] = $p1;
                            }
                        }
                        
                        return implode(' | ', $finalParts);
                    };

                    if ($item->product) {
                        $displayName = $item->product->name;
                        $rawDesc = $item->description;
                        $displayDesc = $cleanDescription($rawDesc);
                    } else {
                        // Custom Item Logic
                        if ($item->description) {
                             if (str_contains($item->description, '|')) {
                                 // Format: "Name | Description"
                                 $parts = explode('|', $item->description, 2);
                                 $displayName = trim($parts[0]);
                                 $rawDesc = trim($parts[1] ?? '');
                                 // Apply same cleaning to secondary part
                                 $displayDesc = $cleanDescription($rawDesc);
                             } else {
                                 // Only one part - assume it's the Name
                                 $displayName = $item->description;
                                 $displayDesc = '';
                             }
                        } else {
                            $displayName = 'Custom Item';
                            $displayDesc = '';
                        }
                    }
                    
                    // Final check: If description matches name exactly, hide it to avoid "Double" effect
                    if (trim(strtolower($displayDesc)) === trim(strtolower($displayName))) {
                        $displayDesc = '';
                    }
                    
                    $displayDesc = $displayDesc ?: '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $displayName }}</strong>
                    </td>
                    <td style="font-size: 10px; color: #555;">
                        {{ $displayDesc }}
                    </td>
                    <td class="text-center">
                        @if($item->width_input && $item->width_input > 0)
                            {{ (float)$item->width_input }}{{ $item->width_unit ?? '' }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item->length_input && $item->length_input > 0)
                             {{ (float)$item->length_input }}{{ $item->length_unit ?? '' }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $item->quantity }}
                    </td>
                    <td class="text-center">
                        @if($item->product && $item->product->type === 'panaflex_roll')
                            {{ number_format($item->units_sqft, 2) }}
                        @else
                            {{ $item->quantity }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($item->line_total, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals & Signatures Section -->
    <div style="margin-top: 20px; display: table; width: 100%;">
        <div style="display: table-cell; width: 60%; vertical-align: top; padding-right: 20px;">
            <div style="border: 1px solid #ccc; padding: 10px; min-height: 25px;">
                <strong>Amount in Words:</strong><br>
                <span style="text-transform: capitalize; font-style: italic;">
                    @php
                        if (class_exists('NumberFormatter')) {
                            $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                            echo $f->format($computedGrandTotal) . " Rupees Only";
                        } else {
                            // Fallback function for when intl extension is disabled
                            if (!function_exists('numberToWordsFallback')) {
                                function numberToWordsFallback($number) {
                                    $hyphen      = '-';
                                    $conjunction = ' and ';
                                    $separator   = ', ';
                                    $dictionary  = [
                                        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
                                        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
                                        11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
                                        15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
                                        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty',
                                        50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty',
                                        90 => 'ninety', 100 => 'hundred', 1000 => 'thousand',
                                        1000000 => 'million', 1000000000 => 'billion'
                                    ];
                                    if (!is_numeric($number)) return false;
                                    $string = null;
                                    $number = floor($number);
                                    if ($number == 0) return $dictionary[0];
                                    switch (true) {
                                        case $number < 21: $string = $dictionary[$number]; break;
                                        case $number < 100:
                                            $tens = ((int) ($number / 10)) * 10;
                                            $units = $number % 10;
                                            $string = $dictionary[$tens];
                                            if ($units) $string .= $hyphen . $dictionary[$units];
                                            break;
                                        case $number < 1000:
                                            $hundreds = floor($number / 100); 
                                            $remainder = $number % 100;
                                            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                                            if ($remainder) $string .= $conjunction . numberToWordsFallback($remainder);
                                            break;
                                        default:
                                            $baseUnit = pow(1000, floor(log($number, 1000)));
                                            $numBaseUnits = (int) ($number / $baseUnit);
                                            $remainder = $number % $baseUnit;
                                            $string = numberToWordsFallback($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                                            if ($remainder) {
                                                $string .= $remainder < 100 ? $conjunction : $separator;
                                                $string .= numberToWordsFallback($remainder);
                                            }
                                            break;
                                    }
                                    return $string;
                                }
                            }
                            echo numberToWordsFallback($computedGrandTotal) . " Rupees Only";
                        }
                    @endphp
                </span>
            </div>

            <!-- Signatures -->
            <div style="margin-top: 45px; display: flex; justify-content: space-between;">
                <div style="text-align: center; width: 40%; border-top: 1px solid #000; padding-top: 5px;">
                    Prepared By
                </div>
                <div style="text-align: center; width: 40%; border-top: 1px solid #000; padding-top: 5px;">
                    Received By
                </div>
            </div>
            
            @if($sale->notes)
                <div style="margin-top: 20px; font-size: 9px;">
                   <strong>Notes:</strong> {{ $sale->notes }}
                </div>
            @endif
        </div>

        <div style="display: table-cell; width: 40%; vertical-align: top; text-align: right;">
            <div class="totals-section" style="display: inline-block; width: auto; min-width: 250px; text-align: left;">
                <table style="width: 100%;">
                    <!-- Original Totals Logic -->
                    <tr>
                        <th style="text-align: left;">Gross Total:</th>
                        <td class="text-right">PKR {{ number_format($grossTotal, 2) }}</td>
                    </tr>
                    @if($discountTotal > 0)
                        <tr>
                            <th style="text-align: left;">Discount:</th>
                            <td class="text-right">- PKR {{ number_format($discountTotal, 2) }}</td>
                        </tr>
                    @endif
                    @if($taxTotal > 0)
                        <tr>
                            <th style="text-align: left;">Tax:</th>
                            <td class="text-right">PKR {{ number_format($taxTotal, 2) }}</td>
                        </tr>
                    @endif
                    @if($utilitiesCharges > 0)
                        <tr>
                            <th style="text-align: left;">Bility/Rent Charges:</th>
                            <td class="text-right">PKR {{ number_format($utilitiesCharges, 2) }}</td>
                        </tr>
                    @endif
                    @if($otherCharges > 0)
                        <tr>
                            <th style="text-align: left;">Other Charges:</th>
                            <td class="text-right">PKR {{ number_format($otherCharges, 2) }}</td>
                        </tr>
                    @endif
                    @if(abs(($sale->bill_total ?? 0) - $computedGrandTotal) > 0.009)
                        <tr>
                            <th style="text-align: left;">Bill Total:</th>
                            <td class="text-right"><strong>PKR {{ number_format($sale->bill_total, 2) }}</strong></td>
                        </tr>
                    @endif
                    @if($sale->customer && $previousBalance > 0)
                        <tr>
                            <th style="text-align: left;">Previous Balance:</th>
                            <td class="text-right">PKR {{ number_format($previousBalance, 2) }}</td>
                        </tr>
                    @endif
                    @if($sale->customer)
                        <tr>
                            <th style="text-align: left;">Adv Available:</th>
                            <td class="text-right">PKR {{ number_format($customerAdvanceAvailable, 2) }}</td>
                        </tr>
                    @endif
                    <tr style="color: #059669;">
                        <th style="text-align: left;">Advance Used:</th>
                        <td class="text-right">PKR {{ number_format($advanceUsed, 2) }}</td>
                    </tr>
                    <tr>
                        <th style="text-align: left;">Remaining Advance:</th>
                        <td class="text-right">PKR {{ number_format($remainingAdvance, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <th style="text-align: left;">Grand Total:</th>
                        <td class="text-right"><strong>PKR {{ number_format($computedGrandTotal, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <th style="text-align: left;">Paid Amount (Cash):</th>
                        <td class="text-right">PKR {{ number_format(max(0, $sale->paid_amount - $sale->advance_used), 2) }}</td>
                    </tr>
                    @if($sale->customer)
                        <tr>
                            <th style="text-align: left;">Current Balance:</th>
                            <td class="text-right"><strong>PKR {{ number_format($previousBalance + $currentBillTotal - (($sale->paid_amount ?? 0) - ($sale->advance_used ?? 0)), 2) }}</strong></td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    
    <!-- Footer (Commented out as requested? No, "footer comment kar do left side pa..." -> "Add footer comment on left side?") -->
    <!-- The user said "aur fr footer comment kar do" which might mean "comment out the footer" OR "add a footer comment". -->
    <!-- Given "left side pa total in words ka nicha jo jaga baca gi aska nicha Preparedby aur Recivedby ka sign ka lia area banana ha", -->
    <!-- Use of "Footer comment" might refer to the Terms & Conditions text. -->
    
    <!-- Footer Section
    <div class="footer" style="margin-top: 30px; border-top: none;">
        <p style="font-size: 10px;">
            Generated on {{ now()->format('M d, Y \a\t h:i A') }}
        </p>
    </div> -->

    <!-- Print Button (hidden when printing) -->
    <div class="no-print" style="position: fixed; top: 10px; right: 10px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print Invoice
        </button>
    </div>
</body>
</html>
