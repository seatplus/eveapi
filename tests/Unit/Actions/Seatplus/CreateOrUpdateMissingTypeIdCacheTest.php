<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;


use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingTypeIdCache;
use Seatplus\Eveapi\Tests\TestCase;

class CreateOrUpdateMissingTypeIdCacheTest extends TestCase
{

    /** @test */
    public function it_creates_cache_if_non_exists()
    {

        $test_class = new CreateOrUpdateMissingTypeIdCache(collect(1337));

        $this->assertFalse(Cache::has('type_ids_to_resolve'));

        $test_class->handle();

        $this->assertEquals(collect(1337),$test_class->type_ids);
        $this->assertTrue(Cache::has('type_ids_to_resolve'));
        $this->assertEquals(collect(1337),Cache::get('type_ids_to_resolve'));
    }

    /** @test */
    public function it_merges_cache_if_chache_exists()
    {

        Cache::put('type_ids_to_resolve', collect(42));

        $this->assertTrue(Cache::has('type_ids_to_resolve'));

        $test_class = new CreateOrUpdateMissingTypeIdCache(collect(1337));

        $test_class->handle();

        $this->assertTrue(in_array(1337, $test_class->type_ids->toArray()));
        $this->assertTrue(in_array(42, $test_class->type_ids->toArray()));

    }

}
