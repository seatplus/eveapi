<?php


namespace Seatplus\Eveapi\Jobs\Seatplus;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Models\RefreshToken;
use Seatplus\Eveapi\Services\Pipes\CharacterAssets;
use Seatplus\Eveapi\Services\Pipes\CharacterInfo;

class UpdateCharacters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $pipes = [
        CharacterInfo::class
    ];

    public function handle()
    {

        RefreshToken::cursor()->each(function ($token) {

            $job_container = new JobContainer([
                'refresh_token' => $token,
            ]);

            (new CharacterAssets)
                ->through($this->pipes)
                ->handle($job_container);

        });
    }
}
