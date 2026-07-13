<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Commands;

use Illuminate\Console\Command;
use Seatplus\Eveapi\Jobs\Seatplus\UpdateCharacter;
use Seatplus\Eveapi\Models\RefreshToken;

class UpdateCharacterCommand extends Command
{
    protected $signature = 'seatplus:update-character {character_id : The character_id to force an update for}';

    protected $description = 'Force-dispatch a high-priority update batch for a single character (bypasses the 30-minute schedule).';

    public function handle(): int
    {
        $characterId = (int) $this->argument('character_id');

        $refreshToken = RefreshToken::find($characterId);

        if (! $refreshToken) {
            $this->error("No refresh token for character {$characterId} — add or re-authenticate the character first.");

            return self::FAILURE;
        }

        UpdateCharacter::dispatch($refreshToken, force: true)->onQueue('high');

        $this->info("Dispatched a high-priority update batch for character {$characterId}.");

        return self::SUCCESS;
    }
}
