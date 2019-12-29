<?php

namespace Seatplus\Eveapi\Containers;

use Seatplus\Eveapi\Exceptions\InvalidContainerDataException;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\RefreshToken;

class JobContainer
{
    protected $data = [
        'refresh_token' => null,
        'character_id' => null,
        'corporation_id' => null,
        'alliance_id' => null,
    ];

    public function __construct(array $data = null)
    {

        if (! is_null($data)) {

            foreach ($data as $key => $value) {

                if (! array_key_exists($key, $this->data))
                    throw new InvalidContainerDataException(
                        'Key ' . $key . ' is not valid for this container'
                    );

                $this->$key = $value;
            }
        }
    }

    public function __set($key, $value): void
    {

        if (array_key_exists($key, $this->data))
            $this->data[$key] = $value;

    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->data))
            return $this->data[$key];

        return '';
    }

    public function getCharacterId()
    {

        return $this->character_id ?? optional($this->refresh_token)->character_id;
    }

    public function getCorporationId()
    {

        return $this->corporation_id ?? optional(CharacterInfo::find($this->getCharacterId()))->corporation_id;
    }

    public function getAllianceId()
    {

        return $this->alliance_id ?? optional(CharacterInfo::find($this->getCharacterId()))->alliance_id;
    }

    public function getRefreshToken()
    {

        return $this->refresh_token;
    }
}
