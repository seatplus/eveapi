<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;


use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Tests\TestCase;

class CreateOrUpdateMissingGroupIdCacheTest extends TestCase
{

    /** @test */
    public function it_creates_cache_if_non_exists()
    {

        $test_class = new CreateOrUpdateMissingIdsCache('group_ids_to_resolve', collect(1337));

        $this->assertFalse(Cache::has('group_ids_to_resolve'));

        $test_class->handle();

        $this->assertEquals(collect(1337),$test_class->ids);

        $this->assertTrue(Cache::has('group_ids_to_resolve'));

        $this->assertEquals([1337],Cache::get('group_ids_to_resolve'));
    }

    /** @test */
    public function it_merges_cache_if_chache_exists()
    {

        Cache::put('group_ids_to_resolve', collect(42));

        $this->assertTrue(Cache::has('group_ids_to_resolve'));

        $test_class = new CreateOrUpdateMissingIdsCache('group_ids_to_resolve', collect(1337));

        $test_class->handle();

        $this->assertTrue(in_array(1337, $test_class->ids->toArray()));
        $this->assertTrue(in_array(42, $test_class->ids->toArray()));
        $this->assertEquals([1337,42],Cache::get('group_ids_to_resolve'));

    }

}
