<?php


namespace Seatplus\Eveapi\Services;


use Cron\CronExpression;
use Seatplus\Eveapi\Models\Schedules;

class MinutesUntilNextSchedule
{

    public static function get(string $scheduled_class) : int
    {
        $expression = Schedules::firstWhere('job', $scheduled_class)?->expression;

        if(is_null($expression))
            return 60;

        throw_unless(is_string($expression), new \Exception(sprintf('class %s could not be found in scheduled jobs', $scheduled_class)));

        $cron = new CronExpression($expression);

        return carbon()->diffInMinutes($cron->getNextRunDate());

    }

}
