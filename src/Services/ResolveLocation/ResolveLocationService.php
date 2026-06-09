<?php

declare(strict_types=1);

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
        private readonly ?RefreshToken $refreshToken = null,
        private array $resolvers = []
    ) {
        $this->resolvers = $resolvers ?: [
            new StationResolver,
            new StructureResolver($this->refreshToken),
        ];
    }

    public static function make(?RefreshToken $refreshToken = null): self
    {
        return new self($refreshToken);
    }

    /**
     * @throws Exception
     */
    public function handle(int $locationId): void
    {
        $location = Location::with('locatable')->firstOrNew([
            'location_id' => $locationId,
        ]);

        foreach ($this->resolvers as $resolver) {
            if ($resolver instanceof ResolverInterface) {
                $isResolved = $resolver->handle($location);

                if ($isResolved) {
                    break;
                }
            }
        }
    }
}
