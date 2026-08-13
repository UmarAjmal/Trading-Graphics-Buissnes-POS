<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PanaflexSpec;
use App\Models\StockBatch;
use App\Models\StockMove;
use App\Services\AreaService;
use App\Services\RollConsumptionService;
use Illuminate\Support\Facades\DB;

class PanaflexCuttingService
{
    /**
     * Process a Panaflex cut order
     */
    public static function processCutOrder(Product $product, array $cutData): array
    {
        if ($product->type !== 'panaflex_roll') {
            throw new \InvalidArgumentException('Product must be a Panaflex roll');
        }

        $panaflexSpec = $product->panaflexSpec;
        if (!$panaflexSpec) {
            throw new \InvalidArgumentException('Panaflex specifications not found');
        }

        // Validate cut dimensions against roll width
        $jobWidthInch = $cutData['width'];
        $jobWidthUnit = $cutData['width_unit'];
        $jobLength = $cutData['length'];
        $jobLengthUnit = $cutData['length_unit'];
        $quantity = $cutData['quantity'] ?? 1;

        // Convert job width to inches for validation
        $jobWidthInches = ($jobWidthUnit === 'ft') ? $jobWidthInch * 12 : $jobWidthInch;
        
        // Validate width
        if ($jobWidthInches > $panaflexSpec->roll_width_inch) {
            throw new \InvalidArgumentException(
                "Job width ({$jobWidthInches}\") exceeds available roll width ({$panaflexSpec->roll_width_inch}\")"
            );
        }

        // Calculate area in square feet
        $areaSqFt = AreaService::calcAreaSqFt(
            $jobLength,
            $jobLengthUnit,
            $jobWidthInch,
            $jobWidthUnit,
            $quantity
        );

        // Calculate meters consumed from roll
        $metersConsumed = RollConsumptionService::calcMetersUsed(
            $jobLength,
            $jobLengthUnit,
            $jobWidthInch,
            $jobWidthUnit,
            $panaflexSpec->roll_width_inch,
            $quantity
        );

        // Calculate line total
        $lineTotal = $areaSqFt * $panaflexSpec->rate_per_sqft;

        return [
            'area_sqft' => $areaSqFt,
            'meters_consumed' => $metersConsumed,
            'line_total' => $lineTotal,
            'rate_per_sqft' => $panaflexSpec->rate_per_sqft,
            'roll_width_inch' => $panaflexSpec->roll_width_inch,
            'roll_length_meter' => $panaflexSpec->roll_length_meter,
            'remaining_length_meter' => $panaflexSpec->roll_length_meter - $metersConsumed,
        ];
    }

    /**
     * Check if a cut is possible with current roll
     */
    public static function canCut(Product $product, float $jobWidth, string $jobWidthUnit): bool
    {
        if ($product->type !== 'panaflex_roll') {
            return false;
        }

        $panaflexSpec = $product->panaflexSpec;
        if (!$panaflexSpec) {
            return false;
        }

        // Convert job width to inches
        $jobWidthInches = ($jobWidthUnit === 'ft') ? $jobWidth * 12 : $jobWidth;
        
        return $jobWidthInches <= $panaflexSpec->roll_width_inch;
    }

    /**
     * Get available roll dimensions for a product
     */
    public static function getRollDimensions(Product $product): ?array
    {
        if ($product->type !== 'panaflex_roll') {
            return null;
        }

        $panaflexSpec = $product->panaflexSpec;
        if (!$panaflexSpec) {
            return null;
        }

        return [
            'width_inch' => $panaflexSpec->roll_width_inch,
            'width_ft' => round($panaflexSpec->roll_width_inch / 12, 2),
            'length_meter' => $panaflexSpec->roll_length_meter,
            'length_ft' => round($panaflexSpec->roll_length_meter * 3.28, 2),
            'total_sqft' => $panaflexSpec->getTotalSquareFeet(),
            'rate_per_sqft' => $panaflexSpec->rate_per_sqft,
        ];
    }

    /**
     * Calculate remaining roll after cuts
     */
    public static function calculateRemainingRoll(Product $product): array
    {
        if ($product->type !== 'panaflex_roll') {
            return [];
        }

        $panaflexSpec = $product->panaflexSpec;
        if (!$panaflexSpec) {
            return [];
        }

        // Get total meters consumed from all sales
        $totalConsumed = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->sum('sale_items.units_sqft'); // This should be meters, but we'll use units_sqft for now

        $remainingMeters = max(0, $panaflexSpec->roll_length_meter - $totalConsumed);
        $remainingSqFt = $remainingMeters * ($panaflexSpec->roll_width_inch / 12) * 3.28;

        return [
            'original_length_meter' => $panaflexSpec->roll_length_meter,
            'consumed_meters' => $totalConsumed,
            'remaining_meters' => $remainingMeters,
            'remaining_sqft' => round($remainingSqFt, 2),
            'utilization_percent' => round(($totalConsumed / $panaflexSpec->roll_length_meter) * 100, 2),
        ];
    }

    /**
     * Get cutting history for a product
     */
    public static function getCuttingHistory(Product $product, int $limit = 10): array
    {
        if ($product->type !== 'panaflex_roll') {
            return [];
        }

        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sale_items.product_id', $product->id)
            ->where('sales.status', 'completed')
            ->select([
                'sales.invoice_number',
                'sales.created_at',
                'customers.name as customer_name',
                'sale_items.length_input',
                'sale_items.length_unit',
                'sale_items.width_input',
                'sale_items.width_unit',
                'sale_items.quantity',
                'sale_items.units_sqft',
                'sale_items.line_total'
            ])
            ->orderBy('sales.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
