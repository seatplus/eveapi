<?php

namespace Seatplus\Eveapi\Jobs\Mail;

use Seatplus\EsiClient\EsiClient;
use Seatplus\Eveapi\Jobs\EsiJob;
use Seatplus\Eveapi\Models\Mail\Mail;
use Seatplus\Eveapi\Models\RefreshToken;

class MailBodyJob extends EsiJob
{
    public function __construct(public int $character_id, public int $mail_id) {}

    #[\Override]
    public function getRefreshToken(): ?RefreshToken
    {
        return RefreshToken::findOrFail($this->character_id);
    }

    #[\Override]
    public function tags(): array
    {
        return ['mail', 'body', "character_id:{$this->character_id}", "mail_id:{$this->mail_id}"];
    }

    #[\Override]
    protected function executeJob(EsiClient $esi): void
    {
        $response = $esi->mail()->getCharactersCharacterIdMailMailId($this->character_id, $this->mail_id);
        if ($response->isCachedLoad) {
            return;
        }

        Mail::where('id', $this->mail_id)->update(['body' => $response->body]);
    }
}
