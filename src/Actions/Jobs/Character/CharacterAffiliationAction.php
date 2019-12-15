<?php


namespace Seatplus\Eveapi\Actions\Jobs\Character;


use Illuminate\Support\Collection;
use Seatplus\Eveapi\Actions\HasRequestBodyInterface;
use Seatplus\Eveapi\Actions\RetrieveFromEsiBase;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;

class CharacterAffiliationAction extends RetrieveFromEsiBase implements HasRequestBodyInterface
{

    /**
     * @var string
     */
    protected $method = 'post';

    /**
     * @var string
     */
    protected $endpoint = '/characters/affiliation/';

    /**
     * @var int
     */
    protected $version = 'v1';

    /**
     * @var array|null
     */
    private $request_body;

    public function execute(?int $character_id = null)
    {

        collect($character_id)->pipe(function (Collection $collection) {

            if($collection->isEmpty())
                return $collection;

            return $collection->filter(function ($value) {

                // Remove $character_id that is already in DB and younger then 60minutes
                $db_entry = CharacterAffiliation::find($value);

                return $db_entry
                    ? $db_entry->last_pulled->diffInMinutes(now()) > 60
                    : true;

            });
        })->pipe(function (Collection $collection) {
            $character_affiliations = CharacterAffiliation::cursor()->filter(function ($character_affiliation) {
                return $character_affiliation->last_pulled->diffInMinutes(now()) > 60;
            });

            foreach ($character_affiliations as $character_affiliation)
                $collection->push($character_affiliation->character_id);

            return $collection;

        })->unique()->chunk(1000)->each(function (Collection $chunk) {

            if($chunk->isEmpty()) return;

            $this->setRequestBody($chunk->values()->all());

            $response = $this->retrieve();

            if ($response->isCachedLoad()) return;

            $timestamp = now();

            collect($response)->map(function ($result) use ($timestamp) {

                return CharacterAffiliation::updateOrCreate(
                    [
                        'character_id' => $result->character_id,
                        'corporation_id' => $result->corporation_id,
                    ],
                    [
                        'alliance_id' => optional($result)->alliance_id,
                        'faction_id' => optional($result)->faction_id,
                        'last_pulled' => $timestamp
                    ]
                );
            })->each(function (CharacterAffiliation $character_affiliation) use ($timestamp) {

                $character_affiliation->last_pulled = $timestamp;
                $character_affiliation->save();
            });
        });


        return CharacterAffiliation::find($character_id);

    }

    public function getRequestBody(): array
    {
        return $this->request_body;
    }

    public function setRequestBody(array $character_ids): void
    {
        $this->request_body = $character_ids;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
