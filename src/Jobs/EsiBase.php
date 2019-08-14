<?php


namespace Seatplus\Eveapi\Jobs;


use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Traits\RateLimitsEsiCalls;

abstract class EsiBase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,
        RateLimitsEsiCalls;

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

    /**
     * Get the character_id we have for the token in this job.
     *
     * An exception will be thrown if an empty token is set.
     *
     * @return int
     * @throws \Exception
     */
    public function getCharacterId(): int
    {

        if (is_null($this->refresh_token))
            throw new Exception('No refresh_token specified');

        return $this->refresh_token->character_id;
    }

    /**
     * Assign this job a tag so that Horizon can categorize and allow
     * for specific tags to be monitored.
     *
     * If a job specifies the tags property, that is added to the
     * character_id tag that automatically gets appended.
     *
     * @return array
     * @throws \Exception
     */
    public function tags(): array
    {

        if (property_exists($this, 'tags')) {
            if (is_null($this->refresh_token))
                return array_merge($this->tags, ['public']);

            return array_merge($this->tags, ['character_id:' . $this->getCharacterId()]);
        }

        if (is_null($this->refresh_token))
            return ['unknown_tag', 'public'];

        return ['unknown_tag', 'character_id:' . $this->getCharacterId()];
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

    /**
     * Check if there are any pages left in a response
     * based on the number of pages available and the
     * current page.
     *
     * @param int $pages
     *
     * @return bool
     */
    public function nextPage(int $pages): bool
    {

        if ($this->page >= $pages)
            return false;

        $this->page++;

        return true;
    }

    /**
     * When a job fails, grab some information and send a
     * GA event about the exception. The Analytics job
     * does the work of checking if analytics is disabled
     * or not, so we don't have to care about that here.
     *
     * On top of that, we also increment the error rate
     * limiter. This is checked as part of the preflight
     * checks when API calls are made.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function failed(Exception $exception)
    {

        $this->incrementEsiRateLimit();

        // Rethrow the original exception for Horizon
        throw $exception;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function handle();
}