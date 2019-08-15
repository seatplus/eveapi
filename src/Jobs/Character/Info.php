<?php


namespace Seatplus\Eveapi\Jobs\Character;


use Seatplus\Eveapi\Actions\Jobs\Character\InfoAction;
use Seatplus\Eveapi\Jobs\EsiBase;

class Info extends EsiBase
{
    /**
     * @var string
     */
    protected $method = 'get';

    /**
     * @var string
     */
    protected $endpoint = '/characters/{character_id}/';

    /**
     * @var int
     */
    protected $version = 'v4';

    /**
     * @var array
     */
    protected $tags = ['character', 'info'];

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $character_info = $this->retrieve([
            'character_id' => $this->getCharacterId(),
        ]);

        (new InfoAction())->execute($character_info, $this->getCharacterId());

    }
}