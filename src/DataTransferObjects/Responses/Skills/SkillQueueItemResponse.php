<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Skills;

readonly class SkillQueueItemResponse
{
    public function __construct(
        public int $skill_id,
        public int $queue_position,
        public int $finished_level,
        public ?string $start_date = null,
        public ?string $finish_date = null,
        public ?int $training_start_sp = null,
        public ?int $level_start_sp = null,
        public ?int $level_end_sp = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            skill_id: $data->skill_id,
            queue_position: $data->queue_position,
            finished_level: $data->finished_level,
            start_date: $data->start_date ?? null,
            finish_date: $data->finish_date ?? null,
            training_start_sp: $data->training_start_sp ?? null,
            level_start_sp: $data->level_start_sp ?? null,
            level_end_sp: $data->level_end_sp ?? null,
        );
    }
}
