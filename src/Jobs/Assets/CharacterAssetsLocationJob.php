<?php


namespace Seatplus\Eveapi\Jobs\Assets;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Actions\Location\CacheAllPublicStrucutresIdAction;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Middleware\EsiAvailabilityMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\EsiRateLimitedMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\HasRefreshTokenMiddleware;
use Seatplus\Eveapi\Jobs\Middleware\RedisFunnelMiddleware;
use Seatplus\Eveapi\Models\Assets\CharacterAsset;

class CharacterAssetsLocationJob implements ShouldQueue
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
     * @var \Seatplus\Eveapi\Containers\JobContainer
     */
    private $job_container;

    public function __construct(JobContainer $job_container)
    {
        $this->refresh_token = $job_container->getRefreshToken();
        $this->job_container = $job_container;
    }

    public function middleware(): array
    {
        return [
            new RedisFunnelMiddleware,
            new HasRefreshTokenMiddleware,
            new EsiRateLimitedMiddleware,
            new EsiAvailabilityMiddleware,
        ];
    }

    public function tags() {
        return [
            'character',
            'character_id: ' . $this->refresh_token->character_id,
            'assets',
            'location'
        ];
    }

    public function handle(): void
    {

        (new CacheAllPublicStrucutresIdAction)->execute();

        CharacterAsset::query()
            ->select('character_assets.location_id')
            ->where('character_assets.character_id', $this->refresh_token->character_id)
            ->leftJoin('universe_locations','character_assets.location_id', '=', 'universe_locations.location_id')
            ->whereNull('universe_locations.location_id')
            ->pluck('location_id');

    }
}