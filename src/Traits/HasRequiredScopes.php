<?php


namespace Seatplus\Eveapi\Traits;


trait HasRequiredScopes
{
    public string $required_scope;

    public function getRequiredScope(): string
    {
        return  $this->required_scope;
    }

    /**
     * @param string $required_scope
     */
    public function setRequiredScope(string $required_scope): void
    {
        $this->required_scope = $required_scope;
    }
}