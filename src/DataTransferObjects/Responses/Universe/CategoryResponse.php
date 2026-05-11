<?php

namespace Seatplus\Eveapi\DataTransferObjects\Responses\Universe;

readonly class CategoryResponse
{
    public function __construct(
        public int $category_id,
        public string $name,
        public bool $published,
    ) {}

    public static function from(object $data): self
    {
        return new self(
            category_id: $data->category_id,
            name: $data->name,
            published: $data->published,
        );
    }
}
