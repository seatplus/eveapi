<?php

namespace Seatplus\Eveapi\Exceptions;

/**
 * Sentinel exception thrown when a job is released back to the queue
 * due to ESI rate-limiting (429) or error-limiting (420).
 *
 * Caught silently in EsiBase::handle() so it does not get report()-ed.
 */
class EsiJobReleasedException extends \RuntimeException {}
