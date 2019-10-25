<?php

namespace Seatplus\Eveapi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
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
    public $character_id;

    /**
     * @var int
     */
    protected $corporation_id;

    /**
     * @var int
     */
    protected $alliance_id;

    /**
     * @param \Seatplus\Eveapi\Models\RefreshToken $refresh_token
     */
    public function setRefreshToken(RefreshToken $refresh_token): void
    {

        $this->refresh_token = $refresh_token;
    }

    /**
     * @param int $character_id
     */
    public function setCharacterId(?int $character_id): void
    {

        $this->character_id = $character_id;
    }

    /**
     * @param int $corporation_id
     */
    public function setCorporationId(?int $corporation_id): void
    {

        $this->corporation_id = $corporation_id;
    }

    /**
     * @param int $alliance_id
     *
     * @return void
     */
    public function setAllianceId(?int $alliance_id): void
    {

        $this->alliance_id = $alliance_id;
    }

    /**
     * EsiBase constructor.
     *
     * @param \Seatplus\Eveapi\Containers\JobContainer|null $job_container
     *
     * @throws \Seatplus\Eveapi\Exceptions\InvalidContainerDataException
     */
    public function __construct(?JobContainer $job_container = null)
    {
        $job_container = $job_container ?? new JobContainer();

        $this->setCharacterId($job_container->getCharacterId());
        $this->setCorporationId($job_container->getCorporationId());
        $this->setAllianceId($job_container->getAllianceId());
    }

    public function tags() : array
    {
        $array = property_exists($this, 'tags') ? $this->tags : [];

        if(is_null($this->refresh_token))
            $array = array_merge($array, ['public']);

        return array_filter(array_merge($array, [
            'character_id:' . $this->character_id,
            'corporation_id:' . $this->corporation_id,
            'alliance_id:' . $this->alliance_id,
        ]));

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function handle();
}
