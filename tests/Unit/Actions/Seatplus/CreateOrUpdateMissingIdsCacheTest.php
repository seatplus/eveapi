<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Seatplus;


use Illuminate\Support\Facades\Cache;
use Seatplus\Eveapi\Actions\Seatplus\CreateOrUpdateMissingIdsCache;
use Seatplus\Eveapi\Tests\TestCase;

class CreateOrUpdateMissingIdsCacheTest extends TestCase
{
    protected $cache_string;

    public function setUp(): void
    {

        parent::setUp();

        $this->cache_string = 'category_ids_to_resolve';

    }

    /** @test */
    public function it_creates_cache_if_non_exists()
    {

        $test_class = new CreateOrUpdateMissingIdsCache($this->cache_string, collect(1337));

        $this->assertFalse(Cache::has($this->cache_string));

        $test_class->handle();

        $this->assertEquals(collect(1337),$test_class->ids);

        $this->assertTrue(Cache::has($this->cache_string));

        $this->assertEquals([1337],Cache::get($this->cache_string));
    }

    /** @test */
    public function it_merges_cache_if_chache_exists()
    {

        Cache::put($this->cache_string, collect(42));

        $this->assertTrue(Cache::has($this->cache_string));

        $test_class = new CreateOrUpdateMissingIdsCache($this->cache_string, collect(1337));

        $test_class->handle();

        $this->assertTrue(in_array(1337, $test_class->ids->toArray()));
        $this->assertTrue(in_array(42, $test_class->ids->toArray()));
        $this->assertEquals([1337,42],Cache::get($this->cache_string));

    }

}
