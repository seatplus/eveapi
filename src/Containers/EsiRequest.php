<?php

namespace Seatplus\Eveapi\Containers;

use Seatplus\Eveapi\Exceptions\InvalidEsiRequestDataException;

class EsiRequest
{
    protected $data = [
        'method' => '',
        'version' => '',
        'endpoint' => '',
        'refresh_token' => null,
        'path_values' => [],
        'page' => null,
        'request_body' => [],
        'query_string' => []
    ];

    public function __construct(array $data = null)
    {

        if (! is_null($data)) {

            foreach ($data as $key => $value) {

                if (! array_key_exists($key, $this->data))
                    throw new InvalidEsiRequestDataException(
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

    public function isPublic() : bool
    {
        return is_null($this->data['refresh_token']);
    }



}