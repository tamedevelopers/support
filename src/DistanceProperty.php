<?php

declare(strict_types=1);

namespace Tamedevelopers\Support;

class DistanceProperty {

    /**
     * Mean Earth radius in miles.
     *
     * @var float
     */
    protected const EARTH_RADIUS_MILES = 3958.8;

    /**
     * Default average speed for road transport in MPH.
     *
     * @var float
     */
    protected const DEFAULT_SPEED_ROAD_MPH = 50.0;

    /**
     * Default average speed for air transport in MPH.
     *
     * @var float
     */
    protected const DEFAULT_SPEED_AIR_MPH = 450.0;

    /**
     * Default average speed for sea transport in MPH (~17.3 knots).
     *
     * @var float
     */
    protected const DEFAULT_SPEED_SEA_MPH = 20.0;

    /**
     * Road distance multiplier to account for actual road network paths vs straight lines.
     *
     * @var float
     */
    protected const ROAD_TORTUOSITY_FACTOR = 1.20;

    /**
     * Sea distance multiplier to account for maritime routing, canal detour, and coastlines.
     *
     * @var float
     */
    protected const SEA_TORTUOSITY_FACTOR = 1.30;

    /**
     * Freight type identifier for road transit.
     *
     * @var string
     */
    public const TYPE_ROAD = 'road';

    /**
     * Freight type identifier for air transit.
     *
     * @var string
     */
    public const TYPE_AIR = 'air';

    /**
     * Freight type identifier for sea transit.
     *
     * @var string
     */
    public const TYPE_SEA = 'sea';

    /**
     * Origin coordinates ['lat' => float, 'lng' => float].
     *
     * @var array{lat: float, lng: float}|null
     */
    protected ?array $origin = null;

    /**
     * Destination coordinates ['lat' => float, 'lng' => float].
     *
     * @var array{lat: float, lng: float}|null
     */
    protected ?array $destination = null;

    /**
     * Calculated distance in miles.
     *
     * @var float|int
     */
    protected int|float $miles = 0;

    /**
     * Freight transport type ('road', 'air', or 'sea').
     *
     * @var string
     */
    protected string $freightType = self::TYPE_ROAD;

    /**
     * Singular and plural unit labels used for duration formatting.
     *
     * @var array<string, string>
     */
    protected array $unitLabels = [
        'day'  => 'day',
        'days' => 'days',
        'hr'   => 'hr',
        'hrs'  => 'hrs',
        'min'  => 'min',
        'mins' => 'mins',
    ];

}