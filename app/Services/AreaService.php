<?php

namespace App\Services;

/**
 * AreaService - Framework-independent area calculation service
 * 
 * Handles unit conversions and area calculations for Panaflex products.
 * Maintains 4 decimal precision internally, returns 2 decimals for display.
 * 
 * @package App\Services
 */
class AreaService
{
    /**
     * Conversion constants for accurate calculations
     */
    private const INCH_TO_FEET = 0.0833333333;
    private const METER_TO_FEET = 3.28;
    private const FEET_TO_METER = 0.30487804878;
    
    /**
     * Convert inches to feet
     * 
     * @param float $inches Value in inches
     * @return float Value in feet (4 decimal precision)
     */
    public static function inchToFeet(float $inches): float
    {
        return round($inches / 12, 4);
    }
    
    /**
     * Convert meters to feet
     * 
     * @param float $meters Value in meters
     * @return float Value in feet (4 decimal precision)
     */
    public static function meterToFeet(float $meters): float
    {
        return round($meters * self::METER_TO_FEET, 4);
    }
    
    /**
     * Convert feet to meters
     * 
     * @param float $feet Value in feet
     * @return float Value in meters (4 decimal precision)
     */
    public static function feetToMeter(float $feet): float
    {
        return round($feet / self::METER_TO_FEET, 4);
    }
    
    /**
     * Calculate total area in square feet
     * 
     * This is the main calculation method used throughout the system.
     * Handles mixed units and quantity multiplication.
     * 
     * @param float $length Length value
     * @param string $lengthUnit Length unit ('m' for meters, 'ft' for feet)
     * @param float $width Width value
     * @param string $widthUnit Width unit ('in' for inches, 'ft' for feet)
     * @param int $quantity Number of pieces (default: 1)
     * @return float Total area in square feet (2 decimal precision for display)
     * 
     * @example
     * // Invoice example: Length 50m, Width 126in, Qty 10
     * $area = AreaService::calcAreaSqFt(50, 'm', 126, 'in', 10);
     * // Returns: 17224.41 sq.ft
     */
    public static function calcAreaSqFt(
        float $length,
        string $lengthUnit,
        float $width,
        string $widthUnit,
        int $quantity = 1
    ): float {
        // Convert length to feet
        $lengthFt = ($lengthUnit === 'm') 
            ? self::meterToFeet($length) 
            : $length;
        
        // Convert width to feet
        $widthFt = ($widthUnit === 'in') 
            ? self::inchToFeet($width) 
            : $width;
        
        // Calculate area per piece (maintain 4 decimal precision internally)
        $areaSqFtPerPiece = round($lengthFt * $widthFt, 4);
        
        // Calculate total area for all quantities
        $totalAreaSqFt = round($areaSqFtPerPiece * $quantity, 4);
        
        // Return with 2 decimal precision for display
        return round($totalAreaSqFt, 2);
    }
    
    /**
     * Calculate area per single piece (for detailed breakdowns)
     * 
     * @param float $length Length value
     * @param string $lengthUnit Length unit
     * @param float $width Width value
     * @param string $widthUnit Width unit
     * @return float Area per piece in square feet (2 decimal precision)
     */
    public static function calcAreaPerPiece(
        float $length,
        string $lengthUnit,
        float $width,
        string $widthUnit
    ): float {
        return self::calcAreaSqFt($length, $lengthUnit, $width, $widthUnit, 1);
    }
    
    /**
     * Format area value for display with consistent formatting
     * 
     * @param float $area Area value
     * @return string Formatted area (e.g., "17,224.41")
     */
    public static function formatArea(float $area): string
    {
        return number_format($area, 2, '.', '');
    }
    
    /**
     * Validate unit strings
     * 
     * @param string $unit Unit to validate
     * @param array $allowedUnits Allowed units
     * @return bool True if valid
     */
    public static function validateUnit(string $unit, array $allowedUnits): bool
    {
        return in_array($unit, $allowedUnits, true);
    }
    
    /**
     * Get all supported length units
     * 
     * @return array Supported length units
     */
    public static function getSupportedLengthUnits(): array
    {
        return ['m', 'ft'];
    }
    
    /**
     * Get all supported width units
     * 
     * @return array Supported width units
     */
    public static function getSupportedWidthUnits(): array
    {
        return ['in', 'ft'];
    }
}