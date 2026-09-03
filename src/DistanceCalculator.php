<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

use InvalidArgumentException;
use Tamedevelopers\Support\DistanceProperty;

/**
 * Class DistanceCalculator
 *
 * Calculates distances and transit durations between geographic coordinates
 * based on freight transport mode (road, air, or sea).
 */
class DistanceCalculator extends DistanceProperty
{   

    /**
     * DistanceCalculator constructor.
     *
     * @param int|float $miles Initial distance in miles.
     * @param 'road'|'air'|'sea' $freightType Transport mode
     * @throws InvalidArgumentException If an invalid freight type is provided.
     */
    public function __construct($miles = 0, $freightType = self::TYPE_ROAD)
    {
        $this->miles = $miles;
        $this->setFreightType($freightType);
    }

    /**
     * Static factory method to instantiate the calculator chain.
     *
     * @param 'road'|'air'|'sea' $freightType Transport mode
     * @return static
     */
    public static function make($freightType = self::TYPE_ROAD)
    {
        return new static(0, $freightType);
    }

    /**
     * Set the freight type for calculation
     *
     * @param 'road'|'air'|'sea' $type Transport mode identifier.
     * @return static
     * @throws InvalidArgumentException If an invalid freight type is provided.
     */
    public function freightType($type)
    {
        $this->setFreightType($type);
        return $this->calculateIfReady();
    }

    /**
     * Set transport mode to road.
     *
     * @return static
     */
    public function viaRoad()
    {
        return $this->freightType(self::TYPE_ROAD);
    }

    /**
     * Set transport mode to air.
     *
     * @return static
     */
    public function viaAir()
    {
        return $this->freightType(self::TYPE_AIR);
    }

    /**
     * Set transport mode to sea.
     *
     * @return static
     */
    public function viaSea()
    {
        return $this->freightType(self::TYPE_SEA);
    }

    /**
     * Set the origin point coordinates.
     *
     * @param float $lat Latitude of origin.
     * @param float $lng Longitude of origin.
     * @return static
     */
    public function origin($lat, $lng)
    {
        $this->origin = [
            'lat' => (float) $lat, 
            'lng' => (float) $lng
        ];

        return $this->calculateIfReady();
    }

    /**
     * Set the destination point coordinates.
     *
     * @param float $lat Latitude of destination.
     * @param float $lng Longitude of destination.
     * @return static
     */
    public function destination($lat, $lng)
    {
        $this->destination = [
            'lat' => (float) $lat, 
            'lng' => (float) $lng
        ];

        return $this->calculateIfReady();
    }

    /**
     * Set custom translations or unit labels for duration strings.
     *
     * @param array{
     *  day: string, 
     *  days: string, 
     *  hr: string, 
     *  hrs: string, 
     *  min: string, 
     *  mins: string
     * } $labels Key-value pairs for unit labels.
     * @return static
     */
    public function setUnitLabels(array $labels)
    {
        $this->unitLabels = array_merge($this->unitLabels, $labels);

        return $this;
    }

    /**
     * Get calculated distance in miles.
     *
     * @param bool $round Whether to round the resulting value.
     * @param int $precision Number of decimal places to round to.
     * @return float|int
     */
    public function miles($round = false, $precision = 2)
    {
        return $round ? round($this->miles, $precision) : $this->miles;
    }

    /**
     * Get calculated distance in kilometers.
     *
     * @param bool $round Whether to round the resulting value.
     * @param int $precision Number of decimal places to round to.
     * @return float|int
     */
    public function kilometers($round = false, $precision = 2)
    {
        $km = $this->miles * 1.60934;

        return $round ? round($km, $precision) : $km;
    }

    /**
     * Get calculated distance in nautical miles (NM).
     *
     * @param bool $round Whether to round the resulting value.
     * @param int $precision Number of decimal places to round to.
     * @return float|int
     */
    public function nauticalMiles($round = false, $precision = 2)
    {
        $nm = $this->miles * 0.868976;

        return $round ? round($nm, $precision) : $nm;
    }

    /**
     * Calculate transit duration in minutes.
     *
     * @param float|null $customMph Optional custom speed in MPH.
     * @param bool $round Whether to round to the nearest whole minute.
     * @return float|int
     */
    public function toMinutes($customMph = null, $round = true)
    {
        $speed = $customMph ?? $this->getDefaultSpeed();

        if ($speed <= 0) {
            return 0;
        }

        $minutes = ($this->miles / $speed) * 60;

        return $round ? round($minutes) : $minutes;
    }

    /**
     * Calculate transit duration in hours.
     *
     * @param float|null $customMph Optional custom speed in MPH.
     * @param int $precision Number of decimal places to round to.
     * @return float
     */
    public function toHours($customMph = null, $precision = 2)
    {
        $speed = $customMph ?? $this->getDefaultSpeed();

        if ($speed <= 0) {
            return 0.0;
        }

        return round($this->miles / $speed, $precision);
    }

    /**
     * Get human-readable duration string (e.g., "1 day 3 hrs 15 mins" or "1day 3hrs 15mins").
     *
     * @param bool $allowSpace Whether to include a space between numbers and units.
     * @param float|null $customMph Optional custom speed in MPH.
     * @return string
     */
    public function toFormattedDuration(bool $allowSpace = false, $customMph = null)
    {
        $totalMinutes = (int) $this->toMinutes($customMph, round: true);
        
        $days    = intdiv($totalMinutes, 1440);
        $hours   = intdiv($totalMinutes % 1440, 60);
        $minutes = $totalMinutes % 60;

        $separator = $allowSpace ? ' ' : '';

        $parts = [];
        if ($days > 0) {
            $label = $this->unitLabels[$days === 1 ? 'day' : 'days'];
            $parts[] = $days . $separator . $label;
        }
        if ($hours > 0) {
            $label = $this->unitLabels[$hours === 1 ? 'hr' : 'hrs'];
            $parts[] = $hours . $separator . $label;
        }
        if ($minutes > 0 || ($days === 0 && $hours === 0)) {
            $label = $this->unitLabels[$minutes === 1 ? 'min' : 'mins'];
            $parts[] = $minutes . $separator . $label;
        }

        return implode(' ', $parts);
    }

    /**
     * Convert distance in miles or freight parameters into a formatted or raw cost amount.
     * Supports standard per-mile calculation as well as modal freight pricing (Air, Sea FCL/LCL).
     *
     * @param float|int $ratePerMile Rate charged per mile (or base rate per unit).
     * @param bool $format Whether to format output using number_format().
     * @param int $decimals Number of decimal places to format to.
     * @return float|int|string
     */
    public function toRoadAmount($ratePerMile = 0, bool $format = true, int $decimals = 2)
    {
        $total = ($this->miles * $ratePerMile);

        return $format ? number_format($total, $decimals) : $total;
    }

    /**
     * Calculate air freight cost using Chargeable Weight (Volumetric vs Actual Weight).
     * Standard volumetric ratio: 1 m³ = 167 kg (or Length x Width x Height in cm / 6000).
     *
     * @param float|int $weightKg Actual weight in kilograms.
     * @param float|int $cbm Cargo volume in cubic meters.
     * @param float|int $ratePerKg Rate per chargeable kilogram.
     * @param bool $format Whether to format output using number_format().
     * @param int $decimals Number of decimal places to format to.
     * @return float|int|string
     */
    public function toAirAmount($weightKg, $cbm = 1, $ratePerKg, $format = true, $decimals = 2) 
    {
        // Air industry standard: 1 CBM = 167 kg chargeable weight
        $volumetricWeight = $cbm * 167;
        $chargeableWeight = max($weightKg, $volumetricWeight);

        $total = $chargeableWeight * $ratePerKg;

        return $format ? number_format($total, $decimals) : $total;
    }

    /**
     * Calculate sea freight cost based on Full Container Load (FCL).
     *
     * @param float|int $flatContainerRate Flat rate per container.
     * @param int $containerCount Number of containers (default: 1).
     * @param float|int $portFees Surcharges (BAF, port handling, documentation).
     * @param bool $format Whether to format output using number_format().
     * @param int $decimals Number of decimal places to format to.
     * @return float|int|string
     */
    public function toSeaAmountFcl($flatContainerRate, $containerCount = 1, $portFees = 0, $format = true, $decimals = 2)
    {
        $total = ($flatContainerRate * max(1, $containerCount)) + $portFees;

        return $format ? number_format($total, $decimals) : $total;
    }

    /**
     * Calculate sea freight cost based on Less than Container Load (LCL) using Weight or Measure (W/M).
     * Automatically calculates volume (CBM) and metric tons from cargo dimensions and weight.
     *
     * @param array{
     *      length: float|int, 
     *      width: float|int, 
     *      height: float|int, 
     *      cbm: float|int, 
     *      weight: float|int, 
     *      unit: 'cm'|'inch'|'meter', 
     *      quantity: int
     * } $cargo Cargo specs.
     * @param float|int $ratePerWm Rate per W/M unit.
     * @param float|int $minimumWm Minimum W/M chargeable units (default: 1.0).
     * @param bool $format Whether to format output using number_format().
     * @param int $decimals Number of decimal places to format to.
     * @return float|int|string
     */
    public function toSeaAmountLcl($cargo, $ratePerWm, $minimumWm = 1.0, $format = true, $decimals = 2) 
    {
        $quantity = max(1, $cargo['quantity'] ?? 1);
        $weightKg = ($cargo['weight'] ?? 0) * $quantity;
        $unit = strtolower($cargo['unit'] ?? 'cm');

        // Resolve CBM from direct value or dimensions
        if (isset($cargo['cbm'])) {
            $cbm = $cargo['cbm'] * $quantity;
        } else {
            $l = $cargo['length'] ?? 0;
            $w = $cargo['width'] ?? 0;
            $h = $cargo['height'] ?? 0;

            // Convert dimensions based on unit to calculate CBM
            $cbm = match ($unit) {
                'm', 'meter', 'meters' => ($l * $w * $h) * $quantity,
                'in', 'inch', 'inches' => (($l * $w * $h) / 61023.7) * $quantity,
                default                => (($l * $w * $h) / 1000000) * $quantity, // default cm
            };
        }

        // Convert total weight to Metric Tons (1 Metric Ton = 1,000 kg)
        $weightTons = $weightKg / 1000;

        // Chargeable W/M unit is the maximum of CBM vs Weight Tons vs Minimum threshold
        $chargeableWm = max($cbm, $weightTons, $minimumWm);

        $total = $chargeableWm * $ratePerWm;

        return $format ? number_format($total, $decimals) : $total;
    }

    /**
     * Automatically calculates distance once both origin and destination are present.
     *
     * @return static
     */
    private function calculateIfReady()
    {
        if (!empty($this->origin) && !empty($this->destination)) {
            $directMiles = self::computeHaversine(
                $this->origin['lat'],
                $this->origin['lng'],
                $this->destination['lat'],
                $this->destination['lng']
            );

            $this->miles = match ($this->freightType) {
                self::TYPE_ROAD => $directMiles * self::ROAD_TORTUOSITY_FACTOR,
                self::TYPE_SEA  => $directMiles * self::SEA_TORTUOSITY_FACTOR,
                default         => $directMiles, // Air distance (direct line)
            };
        }

        return $this;
    }

    /**
     * Validate and store freight type.
     *
     * @param 'road'|'air'|'sea' $type Transport mode.
     * @return void
     * @throws InvalidArgumentException
     */
    private function setFreightType($type)
    {
        $normalized = strtolower(trim($type));

        if (!in_array($normalized, [self::TYPE_ROAD, self::TYPE_AIR, self::TYPE_SEA], true)) {
            throw new InvalidArgumentException("Invalid freight type '{$type}'. Allowed types are 'road', 'air', or 'sea'.");
        }

        $this->freightType = $normalized;
    }

    /**
     * Retrieve default speed based on configured freight type.
     *
     * @return float Speed in miles per hour (MPH).
     */
    private function getDefaultSpeed()
    {
        return match ($this->freightType) {
            self::TYPE_ROAD => self::DEFAULT_SPEED_ROAD_MPH,
            self::TYPE_SEA  => self::DEFAULT_SPEED_SEA_MPH,
            default         => self::DEFAULT_SPEED_AIR_MPH,
        };
    }

    /**
     * Internal Core Haversine Calculation.
     *
     * @param float $lat1 Origin latitude.
     * @param float $lon1 Origin longitude.
     * @param float $lat2 Destination latitude.
     * @param float $lon2 Destination longitude.
     * @return float Great-circle distance in miles.
     */
    private static function computeHaversine($lat1, $lon1, $lat2, $lon2)
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_MILES * $c;
    }

    /**
     * Static shortcut method for quick distance calculations.
     *
     * @param float $lat1 Origin latitude.
     * @param float $lon1 Origin longitude.
     * @param float $lat2 Destination latitude.
     * @param float $lon2 Destination longitude.
     * @param 'road'|'air'|'sea' $freightType Transport mode.
     * @param bool $round Whether to round miles to 2 decimal places.
     * @return static
     */
    public static function getHaversineMiles($lat1, $lon1, $lat2, $lon2, $freightType = self::TYPE_ROAD, $round = true) 
    {
        $instance = new static(0, $freightType);
        $instance->origin($lat1, $lon1)->destination($lat2, $lon2);

        if ($round) {
            $instance->miles = round($instance->miles, 2);
        }

        return $instance;
    }

}