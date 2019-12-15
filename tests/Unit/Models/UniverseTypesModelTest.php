<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Seatplus\Eveapi\Models\Universe\Group;
use Seatplus\Eveapi\Models\Universe\Type;
use Seatplus\Eveapi\Tests\TestCase;

class UniverseTypesModelTest extends TestCase
{

    /**
     * @var \Illuminate\Database\Eloquent\FactoryBuilder
     */
    private $type;

    public function setUp(): void
    {

        parent::setUp();

        $this->type = factory(Type::class)->create();
    }

    /** @test */
    public function it_has_group()
    {
        $this->type->group()->save(factory(Group::class)->make());

        $this->assertNotNull($this->type->group);
    }

    /** @test */
    public function it_has_no_group()
    {

        $this->assertNull($this->type->group);
    }

    /** @test */
    public function it_has_no_category()
    {

        $this->type->group()->save(factory(Group::class)->create());

        $this->assertNull($this->type->category);
    }

}
