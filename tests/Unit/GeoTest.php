<?php

namespace Tests\Unit;

use App\Support\Geo;
use PHPUnit\Framework\TestCase;

class GeoTest extends TestCase
{
    public function test_distance_between_identical_points_is_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, Geo::distanceKm(14.5995, 120.9842, 14.5995, 120.9842), 0.001);
    }

    public function test_distance_between_manila_and_tagaytay_is_roughly_correct(): void
    {
        // Metro Manila to Tagaytay City is roughly 55-60km by great-circle distance.
        $distance = Geo::distanceKm(14.5995, 120.9842, 14.0997, 120.9425);

        $this->assertGreaterThan(50, $distance);
        $this->assertLessThan(65, $distance);
    }
}
