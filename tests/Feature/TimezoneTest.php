<?php

namespace Tests\Feature;

use App\Services\ReservationAvailability;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Le site vit a l'heure de Guyane (UTC-3, sans heure d'ete). */
class TimezoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_local_time_is_guyane_time(): void
    {
        $this->assertSame('America/Cayenne', config('app.local_timezone'));

        // 15 janvier 12:00 UTC = 09:00 a Cayenne (en hiver comme en ete : pas d'heure d'ete).
        Carbon::setTestNow(Carbon::parse('2027-01-15 12:00:00', 'UTC'));
        $this->assertSame('2027-01-15 09:00', ReservationAvailability::now()->format('Y-m-d H:i'));

        Carbon::setTestNow(Carbon::parse('2027-07-15 12:00:00', 'UTC'));
        $this->assertSame('2027-07-15 09:00', ReservationAvailability::now()->format('Y-m-d H:i'));
    }

    public function test_stored_dates_stay_utc(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
    }
}
