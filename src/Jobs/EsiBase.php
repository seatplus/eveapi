<?php


namespace Seatplus\Eveapi\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\RefreshToken;

abstract class EsiBase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Seatplus\Eveapi\Models\RefreshToken
     */
    protected $refresh_token;

    /**
     * @var bool
     */
    protected $public_call;

    /**
     * @var mixed
     */
    protected $client;

    /**
     * The endpoint version to use.
     *
     * Eg: v1, v4
     *
     * @var int
     */
    protected $version = '';

    /**
     * The body to send along with the request.
     *
     * @var array
     */
    protected $request_body = [];

    /**
     * Any query string parameters that should be sent
     * with the request.
     *
     * @var array
     */
    protected $query_string = [];

    /**
     * The page to retrieve.
     *
     * Jobs that expect paged responses should have
     * this value set.
     *
     * @var int
     */
    protected $page = null;

    public function __construct(RefreshToken $refresh_token = null)
    {

        is_null($refresh_token) ? $this->public_call = true : $this->refresh_token = $refresh_token;
    }

    public function retrieve(array $path_values = []): EsiResponse
    {
        $client = $this->eseye();
        $client->setVersion($this->version);
        $client->setBody($this->request_body);
        $client->setQueryString($this->query_string);

        // Configure the page to get
        if (! is_null($this->page))
            $client->page($this->page);

        $result = $client->invoke($this->method, $this->endpoint, $path_values);

        return $result;
    }

    /**
     * Get an instance of Eseye to use for this job.
     *
     * @return \Seat\Eseye\Eseye
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function eseye()
    {
        if ($this->client)
            return $this->client;

        $this->client = app('esi-client');

        if (is_null($this->refresh_token))
            return $this->client = $this->client->get();

        // retrieve up-to-date token
        $this->refresh_token = $this->refresh_token->fresh();

        return $this->client = $this->client->get(new EsiAuthentication([
            'refresh_token' => $this->refresh_token->refresh_token,
            'access_token'  => $this->refresh_token->token,
            'token_expires' => $this->refresh_token->expires_on,
            'scopes'        => $this->refresh_token->scopes,
        ]));
    }
}