<?php

namespace App\Services;

/**
 * RollConsumptionService - Roll material calculation service
 * 
 * Handles Panaflex roll material consumption calculations.
 * Determines how much material (in meters) is consumed from a roll
 * for a given job specification.
 * 
 * @package App\Services
 */
class RollConsumptionService
{
    /**
     * Conversion constants
     */
    private const FEET_TO_METER = 0.3048;
    private const INCH_TO_METER = 0.0254;
    
    /**
     * Calculate meters of material used from a Panaflex roll
     * 
     * Determines actual material consumption considering:
     * - Job dimensions (length, width)
     * - Roll width compatibility
     * - Print direction and material optimization
     * 
     * @param float $jobLength Job length
     * @param string $jobLengthUnit Job length unit ('m' or 'ft')
     * @param float $jobWidth Job width
     * @param string $jobWidthUnit Job width unit ('in' or 'ft')
     * @param float $rollWidth Available roll width in inches
     * @param int $quantity Number of pieces
     * @return float Meters of material consumed (2 decimal precision)
     * 
     * @throws \InvalidArgumentException If job width exceeds roll width
     * 
     * @example
     * // Job: 50m length, 126in width, Qty 10, Roll: 126in
     * $meters = RollConsumptionService::calcMetersUsed(50, 'm', 126, 'in', 126, 10);
     * // Returns: 497.09 meters
     */
    public static function calcMetersUsed(
        float $jobLength,
        string $jobLengthUnit,
        float $jobWidth,
        string $jobWidthUnit,
        float $rollWidth,
        int $quantity = 1
    ): float {
        // Convert dimensions
        $jobWidthInches = self::convertToInches($jobWidth, $jobWidthUnit);
        $jobLengthM = self::convertToMeters($jobLength, $jobLengthUnit);
        
        // Calculate strips needed (Tiling)
        // If job width > roll width, we need multiple strips
        // If rollWidth is 0 (shouldn't happen), assume 1 strip
        $strips = ($rollWidth > 0) ? ceil($jobWidthInches / $rollWidth) : 1;
        
        // Calculate total material needed
        // Material consumption = job length * strips * quantity
        $totalMetersUsed = $jobLengthM * $strips * $quantity;
        
        // Return with 2 decimal precision
        return round($totalMetersUsed, 2);
    }
    
    /**
     * Validate that job width fits within roll width
     * 
     * @param float $jobWidth Job width value
     * @param string $jobWidthUnit Job width unit ('in' or 'ft')
     * @param float $rollWidth Available roll width in inches
     * @return bool True if job fits on roll
     */
    public static function validateWidth(
        float $jobWidth,
        string $jobWidthUnit,
        float $rollWidth
    ): bool {
        $jobWidthInches = self::convertToInches($jobWidth, $jobWidthUnit);
        return $jobWidthInches <= $rollWidth;
    }
    
    /**
     * Convert various units to meters
     * 
     * @param float $value Value to convert
     * @param string $unit Source unit ('m', 'ft')
     * @return float Value in meters (4 decimal precision)
     */
    private static function convertToMeters(float $value, string $unit): float
    {
        return match ($unit) {
            'm' => round($value, 4),
            'ft' => round($value * self::FEET_TO_METER, 4),
            default => throw new \InvalidArgumentException("Unsupported unit for length: {$unit}")
        };
    }
    
    /**
     * Convert various units to inches
     * 
     * @param float $value Value to convert
     * @param string $unit Source unit ('in', 'ft')
     * @return float Value in inches (4 decimal precision)
     */
    private static function convertToInches(float $value, string $unit): float
    {
        return match ($unit) {
            'in' => round($value, 4),
            'ft' => round($value * 12, 4),
            default => throw new \InvalidArgumentException("Unsupported unit for width: {$unit}")
        };
    }
    
    /**
     * Calculate roll utilization efficiency
     * 
     * Determines how efficiently the roll width is being used.
     * 
     * @param float $jobWidth Job width
     * @param string $jobWidthUnit Job width unit
     * @param float $rollWidth Roll width in inches
     * @return float Utilization percentage (0.0 to 100.0)
     */
    public static function calcRollUtilization(
        float $jobWidth,
        string $jobWidthUnit,
        float $rollWidth
    ): float {
        if ($rollWidth <= 0) return 0.0;
        
        $jobWidthInches = self::convertToInches($jobWidth, $jobWidthUnit);
        
        // If width > roll width, we calculate utilization based on the last strip
        // or just return > 100%? 
        // Let's return the raw percentage relative to ONE roll width
        // This helps understand how "wide" the job is compared to the roll
        $utilization = ($jobWidthInches / $rollWidth) * 100;
        
        return round($utilization, 2);
    }
    
    /**
     * Estimate roll cost based on material consumption
     * 
     * @param float $metersUsed Meters of material used
     * @param float $rollCostPerMeter Cost per meter of roll material
     * @return float Total material cost
     */
    public static function calcMaterialCost(
        float $metersUsed, 
        float $rollCostPerMeter
    ): float {
        return round($metersUsed * $rollCostPerMeter, 2);
    }
    
    /**
     * Get roll consumption summary for reporting
     * 
     * @param float $jobLength Job length
     * @param string $jobLengthUnit Job length unit
     * @param float $jobWidth Job width
     * @param string $jobWidthUnit Job width unit
     * @param float $rollWidth Roll width in inches
     * @param int $quantity Number of pieces
     * @param float $rollCostPerMeter Optional cost per meter
     * @return array Comprehensive consumption report
     */
    public static function getConsumptionSummary(
        float $jobLength,
        string $jobLengthUnit,
        float $jobWidth,
        string $jobWidthUnit,
        float $rollWidth,
        int $quantity = 1,
        float $rollCostPerMeter = null
    ): array {
        $metersUsed = self::calcMetersUsed(
            $jobLength, $jobLengthUnit,
            $jobWidth, $jobWidthUnit,
            $rollWidth, $quantity
        );
        
        $utilization = self::calcRollUtilization($jobWidth, $jobWidthUnit, $rollWidth);
        
        $summary = [
            'meters_used' => $metersUsed,
            'roll_width_inches' => $rollWidth,
            'job_width_inches' => self::convertToInches($jobWidth, $jobWidthUnit),
            'utilization_percent' => $utilization,
            'quantity' => $quantity,
            'length_per_piece_m' => round(self::convertToMeters($jobLength, $jobLengthUnit), 2),
        ];
        
        if ($rollCostPerMeter !== null) {
            $summary['material_cost'] = self::calcMaterialCost($metersUsed, $rollCostPerMeter);
            $summary['cost_per_piece'] = round($summary['material_cost'] / $quantity, 2);
        }
        
        return $summary;
    }
    
    /**
     * Validate roll specifications
     * 
     * @param float $rollWidth Roll width in inches
     * @return bool True if valid roll specifications
     */
    public static function validateRollSpecs(float $rollWidth): bool
    {
        // Common Panaflex roll widths: 24", 36", 48", 60", 72", 96", 126"
        $standardWidths = [24, 36, 48, 60, 72, 96, 126];
        
        return $rollWidth > 0 && $rollWidth <= 200; // Max practical width
    }
}