<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class GroupResponse
{
    public function __construct(
        public int $group_id,
        public int $category_id,
        public string $name,
        public bool $published,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            group_id: $data->group_id,
            category_id: $data->category_id,
            name: $data->name,
            published: $data->published,
        );
    }
}
