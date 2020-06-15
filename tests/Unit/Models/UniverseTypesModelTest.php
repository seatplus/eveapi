<?php


namespace Seatplus\Eveapi\Tests\Unit\Models;

use Illuminate\Support\Facades\Event;
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

        $this->type = Event::fakeFor(fn () => factory(Type::class)->create());
    }

    /** @test */
    public function it_has_group()
    {
        $group = Event::fakeFor(fn () => factory(Group::class)->create(['group_id' => $this->type->group_id]));

        //$this->type->group()->save($group);

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

        $group = Event::fakeFor(fn () => factory(Group::class)->create());

        $this->type->group()->save($group);

        $this->assertNull($this->type->category);
    }

}
