<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Jobs\Mail;

use Seatplus\EsiClient\EsiClient;
use Seatplus\EsiSchema\Resources\Mail\GetCharactersCharacterIdMailMailId;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\RefreshToken;

final class MailBodyJob extends EsiJob
{
    protected const string OPERATION_CLASS = GetCharactersCharacterIdMailMailId::class;

    public function __construct(public int $characterId, public int $mailId) {}

    #[\Override]
    public function getRefreshToken(): RefreshToken
    {
        return RefreshToken::findOrFail($this->characterId);
    }

    #[\Override]
    public function tags(): array
    {
        return ['mail', 'body', "character_id:{$this->characterId}", "mail_id:{$this->mailId}"];
    }

    #[\Override]
    public function executeJob(EsiClient $esi): void
    {
        $response = self::OPERATION_CLASS::execute($esi, $this->characterId, $this->mailId);
        if ($response->isCachedLoad) {
            return;
        }

        Mail::where('id', $this->mailId)->update(['body' => $response->body]);
    }
}
