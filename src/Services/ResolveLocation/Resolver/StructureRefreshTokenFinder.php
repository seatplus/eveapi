<?php

namespace Seatplus\Eveapi\Services\ResolveLocation\Resolver;

use Illuminate\Database\Eloquent\Collection;
use Seatplus\Eveapi\Models\LocationRefreshToken;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\FinderInterface;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughCharacterAssetsFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughContractsFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughCorporationMemberTrackingFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughPreviouslyFailedRefreshTokenFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughRandomRefreshTokenFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughSuccessfulRefreshTokenFinder;
use Seatplus\Eveapi\Services\ResolveLocation\Finders\ThroughWalletTransactionsFinder;

class StructureRefreshTokenFinder
{
    private int $location_id;

    public function __construct(
        public ?RefreshToken $refreshToken = null,
    ) {}

    public function findValidToken(int $location_id): ?RefreshToken
    {
        $this->location_id = $location_id;

        if ($this->refreshToken) {
            return $this->refreshToken;
        }

        $tracings = $this->getLocationRefreshTokens();

        $classes = $this->getClasses();

        return $this->findRefreshToken($tracings, $classes);
    }

    public function markAsResolved(): void
    {
        $this->updateOrCreateLocationRefreshToken(true);
    }

    public function markAsFailed(): void
    {
        $this->updateOrCreateLocationRefreshToken(false);
    }

    private function updateOrCreateLocationRefreshToken(bool $resolved): void
    {
        LocationRefreshToken::query()
            ->updateOrCreate([
                'location_id' => $this->location_id,
                'character_id' => $this->refreshToken->character_id,
            ], [
                'resolved' => $resolved,
            ]);

        if ($resolved) {
            $this->resetAttempts();
        } else {
            $this->incrementAttempts();
        }
    }

    private function incrementAttempts(): void
    {
        LocationRefreshToken::query()
            ->where('location_id', $this->location_id)
            ->where('character_id', $this->refreshToken->character_id)
            ->increment('attempts');
    }

    private function resetAttempts(): void
    {
        LocationRefreshToken::query()
            ->where('location_id', $this->location_id)
            ->where('character_id', $this->refreshToken->character_id)
            ->update(['attempts' => 0]);
    }

    private function getClasses(): array
    {
        return [
            new ThroughSuccessfulRefreshTokenFinder,
            new ThroughCharacterAssetsFinder,
            new ThroughCorporationMemberTrackingFinder,
            new ThroughContractsFinder,
            new ThroughWalletTransactionsFinder,
            new ThroughRandomRefreshTokenFinder,
            new ThroughPreviouslyFailedRefreshTokenFinder,
        ];
    }

    private function getLocationRefreshTokens(): Collection
    {
        return LocationRefreshToken::query()
            ->where('location_id', $this->location_id)
            ->inRandomOrder()
            ->get();
    }

    private function findRefreshToken(Collection $tracings, array $classes): ?RefreshToken
    {
        foreach ($classes as $class) {
            $instance = new $class;

            if ($instance instanceof FinderInterface) {
                $this->refreshToken = $instance->handle($this->location_id, $tracings);

                if ($this->refreshToken) {
                    break;
                }
            }
        }

        return $this->refreshToken;
    }
}
