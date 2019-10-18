<?php

namespace Seatplus\Eveapi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Models\RefreshToken;

abstract class EsiBase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;

    /**
     * @var int
     */
    protected $alliance_id;

    /**
     * @var int
     */
    protected $character_id;

    /**
     * @return mixed
     */
    public function getAllianceId()
    {

        return $this->alliance_id;
    }

    /**
     * @param mixed $alliance_id
     *
     * @return EsiBase
     */
    public function setAllianceId(int $alliance_id)
    {

        $this->alliance_id = $alliance_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getCharacterId(): int
    {

        return $this->character_id;
    }

    /**
     * @param int $character_id
     */
    public function setCharacterId(int $character_id): void
    {

        $this->character_id = $character_id;
    }



    /**
     * EsiBase constructor.
     *
     * @param \Seatplus\Eveapi\Models\RefreshToken|null $refresh_token
     */
    public function __construct(?RefreshToken $refresh_token = null)
    {

        $this->refresh_token = $refresh_token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function handle();

}
