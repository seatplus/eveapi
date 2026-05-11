<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Skills;

readonly class SkillItemResponse
{
    public function __construct(
        public int $skill_id,
        public int $active_skill_level,
        public int $skillpoints_in_skill,
        public int $trained_skill_level,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            skill_id: $data->skill_id,
            active_skill_level: $data->active_skill_level,
            skillpoints_in_skill: $data->skillpoints_in_skill,
            trained_skill_level: $data->trained_skill_level,
        );
    }
}
