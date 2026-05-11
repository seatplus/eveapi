<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Killmails;

readonly class KillmailResponse
{
    /**
     * @param  array<KillmailAttackerResponse>  $attackers
     */
    public function __construct(
        public int $solar_system_id,
        public KillmailVictimResponse $victim,
        public array $attackers,
        public ?string $killmail_time = null,
        public ?int $war_id = null,
        public ?int $moon_id = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            solar_system_id: $data->solar_system_id,
            victim: KillmailVictimResponse::from($data->victim),
            attackers: array_map(
                fn (object $attacker) => KillmailAttackerResponse::from($attacker),
                $data->attackers ?? []
            ),
            killmail_time: $data->killmail_time ?? null,
            war_id: $data->war_id ?? null,
            moon_id: $data->moon_id ?? null,
        );
    }
}
