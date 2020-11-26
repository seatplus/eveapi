<?php


namespace Seatplus\Eveapi\Jobs\Hydrate\Character;


use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Hydrate\Hydrate;

abstract class HydrateCharacterBase implements Hydrate
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public JobContainer $job_container;

    public string $required_scope = '';

    public function __construct(JobContainer $job_container)
    {
        $this->job_container = $job_container;
    }

    public function setRequiredScope(string $required_scope): void
    {
        $this->required_scope = $required_scope;
    }

    public function hasRequiredScope(): bool
    {
        return $this->required_scope
            ? in_array($this->required_scope, $this->job_container->refresh_token->refresh()->scopes)
            : true;
    }
}
