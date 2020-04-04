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

class UpdateCharacters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $pipes = [
        CharacterAssets::class
    ];

    public function handle()
    {
        RefreshToken::cursor()->each(function ($token) {

            $job_container = new JobContainer([
                'refresh_token' => $token,
            ]);

            app(Pipeline::class)
                ->send($job_container)
                ->through($this->pipes);

        });
    }
}
