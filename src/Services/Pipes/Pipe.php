<?php


namespace Seatplus\Eveapi\Services\Pipes;


use Seatplus\Eveapi\Containers\JobContainer;

abstract class Pipe
{
    protected array $successors;

    abstract public function handle(JobContainer $job_container);

    public function through(array $successors) : Pipe
    {
        $this->successors = $successors;
        return $this;
    }

    public function next(JobContainer $job_container)
    {
        if ($this->successors) {
            $next_pipe = array_shift($this->successors);
            (new $next_pipe)->through($this->successors)->handle($job_container);
        }

    }
}
