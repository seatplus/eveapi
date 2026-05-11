<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Skills;

readonly class SkillsResponse
{
    /**
     * @param  array<SkillItemResponse>  $skills
     */
    public function __construct(
        public array $skills,
        public int $total_sp,
        public ?int $unallocated_sp = null,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            skills: array_map(
                fn (object $skill) => SkillItemResponse::from($skill),
                (array) ($data->skills ?? [])
            ),
            total_sp: $data->total_sp,
            unallocated_sp: $data->unallocated_sp ?? null,
        );
    }
}
