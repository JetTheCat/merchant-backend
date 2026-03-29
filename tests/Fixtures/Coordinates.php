<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class Coordinates
{
    // Central reference point (Kuala Lumpur City Centre)
    public const KLCC = ['lat' => 3.1390, 'lon' => 101.6869];

    // Nearby locations (< 10km)
    public const CHERAS = ['lat' => 3.1167, 'lon' => 101.7333];

    public const PETALING_JAYA = ['lat' => 3.1073, 'lon' => 101.6067];

    // Medium distance (10-50km)
    public const SHAH_ALAM = ['lat' => 3.0733, 'lon' => 101.5185];

    public const SEREMBAN = ['lat' => 2.7258, 'lon' => 101.9424];

    // Far locations (> 100km)
    public const PENANG = ['lat' => 5.4164, 'lon' => 100.3327];

    public const JOHOR_BAHRU = ['lat' => 1.4927, 'lon' => 103.7414];
}
