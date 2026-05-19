<?php

namespace Seatplus\Eveapi\Services\ResolveLocation;

use Exception;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Models\Universe\Location;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\ResolverInterface;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\StationResolver;
use Seatplus\Eveapi\Services\ResolveLocation\Resolver\StructureResolver;

class ResolveLocationService
{
    public function __construct(
        private readonly ?RefreshToken $refresh_token = null,
        private array $resolvers = []
    ) {
        $this->resolvers = $resolvers ?: [
            new StationResolver,
            new StructureResolver($this->refresh_token),
        ];
    }

    public static function make(?RefreshToken $refresh_token = null): self
    {
        return new self($refresh_token);
    }

    /**
     * @throws Exception
     */
    public function handle(int $location_id): void
    {
        $location = Location::with('locatable')->firstOrNew([
            'location_id' => $location_id,
        ]);

        foreach ($this->resolvers as $resolver) {
            if ($resolver instanceof ResolverInterface) {
                $is_resolved = $resolver->handle($location);

                if ($is_resolved) {
                    break;
                }
            }
        }
    }
}
