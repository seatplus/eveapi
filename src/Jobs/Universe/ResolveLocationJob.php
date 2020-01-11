<?php

namespace Seatplus\Eveapi\Jobs\Universe;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\Location\AssetSafetyChecker;
use Seatplus\Eveapi\Actions\Location\StationChecker;
use Seatplus\Eveapi\Actions\Location\StructureChecker;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;

class ResolveLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken|null
     */
    public $refresh_token;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\AssetSafetyChecker
     */
    private $assert_safety_checker;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\StationChecker
     */
    private $station_checker;

    /**
     * @var \Seatplus\Eveapi\Actions\Location\StructureChecker
     */
    private $structure_checker;

    /**
     * @var int
     */
    public $location_id;

    public function middleware(): array
    {
        return [
            new RedisFunnelMiddleware,
            new HasRefreshTokenMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    public function __construct(int $location_id, RefreshToken $refresh_token)
    {

        $this->location_id = $location_id;
        $this->refresh_token = $refresh_token;

        $this->assert_safety_checker = new AssetSafetyChecker;
        $this->station_checker = new StationChecker;
        $this->structure_checker = new StructureChecker($this->refresh_token);

        $this->assert_safety_checker->succeedWith($this->station_checker);
        $this->station_checker->succeedWith($this->structure_checker);

    }

    public function tags() {
        return [
            'location_resolve',
            'location_id:' . $this->location_id,
            'via character: ' . $this->refresh_token->character_id,
        ];
    }

    public function handle(): void
    {
        $location = Location::firstOrNew(['location_id' => $this->location_id]);

        $this->assert_safety_checker->check($location);
    }
}
