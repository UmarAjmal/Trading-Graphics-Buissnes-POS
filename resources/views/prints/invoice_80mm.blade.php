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
        $fileName = "{$fNameSanitized}_{$dateStr}";
    @endphp
    <title>{{ $fileName }}</title>
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

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 40px;
            font-weight: bold;
            color: rgba(200, 200, 200, 0.3);
            z-index: -1;
            pointer-events: none;
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
        
        .invoice-meta {
            margin-bottom: 10px;
            font-size: 10px;
        }
        
        .invoice-meta div {
            margin-bottom: 2px;
        }
        
        .items {
            margin-bottom: 10px;
        }
        
        .item {
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #ccc;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .item-details {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .item-calc {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        
        .totals {
            border-top: 1px dashed #333;
            padding-top: 8px;
            margin-top: 10px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .grand-total {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            font-weight: bold;
            font-size: 12px;
            padding: 3px 0;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #333;
            font-size: 9px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        @media print {
            body { 
                margin: 0; 
                padding: 5px;
                -webkit-print-color-adjust: exact;
            }
            .no-print { display: none; }
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

    <!-- Header -->
    <div class="header">
        @if($settings && $settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="Logo" class="logo">
        @endif
        
        <div class="company-name">{{ $settings->company_name ?? 'View Media Zone' }}</div>
        <div class="company-tagline">{{ $settings->tagline ?? 'We Deal All Kind of Media & Inks' }}</div>
        
        <!-- Contact and Bill Info Side by Side -->
        <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 9px;">
            <!-- Left: Contact Numbers -->
            <div style="text-align: left;">
                @if($settings && $settings->phone_1)
                    <div>{{ $settings->phone_1 }}</div>
                @else
                    <div>062-2720822</div>
                @endif
                @if($settings && $settings->phone_2)
                    <div>{{ $settings->phone_2 }}</div>
                @else
                    <div>0301-8647887</div>
                @endif
                @if($settings && $settings->phone_3)
                    <div>{{ $settings->phone_3 }}</div>
                @else
                    <div>0302-8647887</div>
                @endif
            </div>
            
            <!-- Right: Bill Info -->
            <div style="text-align: right;">
                <div><strong>Bill. No.</strong></div>
                <div><strong>{{ explode('-', $sale->invoice_no)[1] ?? $sale->invoice_no }}</strong></div>
                <div><strong>Date</strong></div>
                <div><strong>{{ $sale->sold_at->format('d-M-Y') }}</strong></div>
            </div>
        </div>
        
        <!-- Address -->
        <div style="text-align: center; font-size: 9px; margin: 5px 0;">
            {{ $settings->address ?? 'Bindra Pully Stop Multan Road Bahawalpur' }}
        </div>
    </div>

    <!-- Sale Invoice Title -->
    <div style="text-align: center; font-size: 14px; font-weight: bold; margin: 8px 0; border-bottom: 1px solid #333; padding-bottom: 3px;">
        Sale Invoice
    </div>

    <!-- Customer Info -->
    <div style="margin: 8px 0; font-size: 10px;">
        <div><strong>Party Name:</strong> 
            @if($sale->customer)
                {{ $sale->customer->name === 'Walk-in Customer' ? $walkInName : $sale->customer->name }}
            @else
                {{ $walkInName }}
            @endif
        </div>
        @if($sale->customer && $sale->customer->address)
            <div>{{ $sale->customer->address }}</div>
        @endif
        @if($sale->customer && $sale->customer->phone)
            <div>{{ $sale->customer->phone }}</div>
        @endif
    </div>

    <!-- Invoice Details -->
    <div style="margin: 8px 0; font-size: 9px; border-top: 1px dotted #333; border-bottom: 1px dotted #333; padding: 5px 0;">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <div><strong>Invoice No:</strong> {{ $sale->invoice_no }}</div>
                <div><strong>Date:</strong> {{ $sale->sold_at->format('M d, Y') }}</div>
                <div><strong>Time:</strong> {{ $sale->sold_at->format('h:i A') }}</div>
            </div>
            <div style="text-align: right;">
                <div><strong>Cashier:</strong> {{ $sale->user->name ?? 'Admin' }}</div>
                <div><strong>Payment:</strong> {{ ucfirst($sale->payment_type) }}</div>
            </div>
        </div>
    </div>

    <!-- Items Table with Proper Layout -->
    <div class="items">
        <!-- Table Header -->
        <div style="border-bottom: 1px solid #333; border-top: 1px solid #333; padding: 2px 0; font-size: 8px; font-weight: bold;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 6%; text-align: center;">SR#</td>
                    <td style="width: 28%; text-align: center;">Product Name</td>
                    <td style="width: 12%; text-align: center;">Width</td>
                    <td style="width: 12%; text-align: center;">Length<br><span style="font-size: 7px;">Meter</span></td>
                    <td style="width: 8%; text-align: center;">Qty</td>
                    <td style="width: 8%; text-align: center;">Unit</td>
                    <td style="width: 12%; text-align: center;">Rate</td>
                    <td style="width: 14%; text-align: center;">Total</td>
                </tr>
            </table>
        </div>
        
        @foreach($sale->saleItems as $index => $item)
            @php
                $displayName = '';
                $displayDesc = '';

                // Function to check if a string looks like dimensions (e.g. "12x4", "10*5", "2ft", "3m")
                $isDimension = function($str) {
                     $str = strtolower(trim($str));
                     if (preg_match('/^\d+(\.\d+)?\s*[x*]\s*\d+(\.\d+)?(\s*(ft|in|m|mm|cm))?$/', $str)) {
                          return true;
                     }
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
                             $parts = explode('|', $item->description, 2);
                             $displayName = trim($parts[0]);
                             $rawDesc = trim($parts[1] ?? '');
                             $displayDesc = $cleanDescription($rawDesc);
                         } else {
                             $displayName = $item->description;
                             $displayDesc = '';
                         }
                    } else {
                        $displayName = 'Custom Item';
                        $displayDesc = '';
                    }
                }

                // Final check: If displayDesc matches displayName exactly, hide it
                if (trim(strtolower($displayDesc)) === trim(strtolower($displayName))) {
                    $displayDesc = '';
                }
                
                // For 80mm, if we have a description, we append it to name or show below?
                // The current layout has Name in one column. 
                // Let's append it if it exists: "Name (Desc)" or just keep it simple.
                // The user complained about "double description".
                // If I want to match A4, I should probably show it?
                // But 80mm space is limited.
                
                // Let's modify the name display to include description if valid
                if ($displayDesc) {
                    $displayName .= ' (' . $displayDesc . ')';
                }
            @endphp
            <div style="border-bottom: 1px dotted #ccc; padding: 2px 0; font-size: 8px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 6%; text-align: center;">{{ $index + 1 }}</td>
                        <td style="width: 28%; text-align: left; font-weight: bold;">
                            {{ $displayName }}
                        </td>
                        <td style="width: 12%; text-align: center;">
                            @if($item->width_input && $item->width_input > 0)
                                {{ (float)$item->width_input }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="width: 12%; text-align: center;">
                            @if($item->length_input && $item->length_input > 0)
                                {{ (float)$item->length_input }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="width: 8%; text-align: center;">
                            @if($item->product && $item->product->type === 'panaflex_roll')
                                {{ number_format($item->units_sqft, 2) }}
                            @else
                                {{ $item->quantity }}
                            @endif
                        </td>
                        <td style="width: 8%; text-align: center;">
                            @if($item->product && $item->product->unit)
                                {{ $item->product->unit->symbol }}
                            @elseif($item->length_unit)
                                {{ $item->length_unit }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="width: 12%; text-align: right;">{{ number_format($item->rate, 2) }}</td>
                        <td style="width: 14%; text-align: right; font-weight: bold;">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                </table>
            </div>
        @endforeach
        
        <!-- Total Products Line -->
        <div style="border-top: 1px solid #333; padding: 3px 0; font-size: 9px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; text-align: left;"><strong>Total Products</strong></td>
                    <td style="width: 20%; text-align: center;"><strong>{{ $sale->saleItems->count() }}</strong></td>
                    <td style="width: 20%; text-align: right;"><strong>Gross Total</strong></td>
                    <td style="width: 10%; text-align: right;"><strong>{{ number_format($sale->subtotal, 0) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Totals Section -->
    <div style="margin-top: 10px; font-size: 10px; text-align: right;">
        <!-- Show all totals in a clean right-aligned format -->
        <div style="margin-bottom: 15px;">
            <div style="margin-bottom: 2px;"><strong>Gross Total</strong> <span style="margin-left: 20px;">{{ number_format($grossTotal, 2) }}</span></div>
            
            @if($discountTotal > 0)
                <div style="margin-bottom: 2px;">Discount <span style="margin-left: 20px;">{{ number_format($discountTotal, 2) }}</span></div>
            @endif
            
            @if($taxTotal > 0)
                <div style="margin-bottom: 2px;">Tax <span style="margin-left: 20px;">{{ number_format($taxTotal, 2) }}</span></div>
            @endif
            
            @if($utilitiesCharges > 0)
                <div style="margin-bottom: 2px;">Bility/Rent Charges <span style="margin-left: 20px;">{{ number_format($utilitiesCharges, 2) }}</span></div>
            @endif
            
            @if($otherCharges > 0)
                <div style="margin-bottom: 2px;">Other Charges <span style="margin-left: 20px;">{{ number_format($otherCharges, 2) }}</span></div>
            @endif
            
            @if(abs(($sale->bill_total ?? 0) - $computedGrandTotal) > 0.009)
                <div style="margin-bottom: 2px;"><strong>Bill Total</strong> <span style="margin-left: 20px;">{{ number_format($sale->bill_total, 2) }}</span></div>
            @endif
            
            @if($sale->customer)
                <div style="margin-bottom: 2px;">Previous Balance <span style="margin-left: 20px;">{{ number_format($previousBalance, 2) }}</span></div>
            @endif
            
            @if($sale->customer)
                <div style="margin-bottom: 2px;">Customer Advance Available <span style="margin-left: 20px;">{{ number_format($customerAdvanceAvailable, 2) }}</span></div>
            @endif

            <div style="margin-bottom: 2px; color: #059669;">Advance Used <span style="margin-left: 20px;">{{ number_format($advanceUsed, 2) }}</span></div>
            <div style="margin-bottom: 2px;">Remaining Advance <span style="margin-left: 20px;">{{ number_format($remainingAdvance, 2) }}</span></div>
            
            <div style="margin-bottom: 2px; font-weight: bold; border-top: 1px solid #333; padding-top: 2px;">Grand Total <span style="margin-left: 20px;">{{ number_format($computedGrandTotal, 2) }}</span></div>
            <div style="margin-bottom: 2px;">Paid Amount (Cash) <span style="margin-left: 20px;">{{ number_format(max(0, $sale->paid_amount - $sale->advance_used), 2) }}</span></div>
            
            @if($sale->customer)
                <div style="margin-bottom: 2px; font-weight: bold;">Customer Current Balance <span style="margin-left: 10px;">{{ number_format($previousBalance + $currentBillTotal - (($sale->paid_amount ?? 0) - ($sale->advance_used ?? 0)), 2) }}</span></div>
            @endif
        </div>
    </div>
    
    <!-- Authorized Signature Section -->
    <div style="margin-top: 20px; border-top: 1px solid #333; padding-top: 10px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="display: inline-block; border-bottom: 1px solid #333; width: 150px; text-align: center; padding-bottom: 2px;">
                <strong>Authorized Signature</strong>
            </div>
        </div>
    </div>

    @if($sale->notes)
        <div style="margin-top: 10px; font-size: 9px; border-top: 1px dotted #ccc; padding-top: 5px;">
            <strong>Notes:</strong> {{ $sale->notes }}
        </div>
    @endif

    <!-- Operator Information -->
    <div style="margin-top: 15px; font-size: 10px;">
        <div><strong>0</strong></div>
        <div><strong>Operator:</strong> {{ $sale->cashier_name ?? 'Admin' }}</div>
    </div>

    <!-- Footer -->
    <div class="footer">
        @if($settings && $settings->print_footer_message)
            <div>{{ $settings->print_footer_message }}</div>
        @else
            <div>Quality guaranteed! Visit us again.</div>
        @endif
        
        <div style="margin-top: 8px; font-size: 8px;">
            Generated on {{ $sale->sold_at->format('M d, Y') }} at {{ $sale->sold_at->format('h:i A') }}
        </div>
    </div>

    <!-- Print Button (hidden when printing) -->
    <div class="no-print" style="position: fixed; top: 5px; right: 5px;">
        <button onclick="window.print()" style="padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 10px;">
            Print
        </button>
    </div>
</body>
</html>
