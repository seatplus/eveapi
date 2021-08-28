<?php


namespace Seatplus\Eveapi\Tests\Integration;

use Illuminate\Support\Facades\Queue;
use Seatplus\Eveapi\Containers\JobContainer;
use Seatplus\Eveapi\Jobs\Alliances\AllianceInfoJob;
use Seatplus\Eveapi\Jobs\Character\CharacterAffiliationJob;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterAffiliationLifeCycleTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    protected CharacterAffiliationJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $job_container = new JobContainer([
            'character_id' => $this->test_character->character_id,
        ]);

        $this->job = new CharacterAffiliationJob($job_container);
    }

    /** @test */
    public function it_dispatches_alliance_job()
    {
        Queue::fake();
        Queue::assertNothingPushed();

        $character = CharacterInfo::factory()->create();
        $character->character_affiliation()->delete();

        $character_affiliation = CharacterAffiliation::factory()->create([
            'character_id' => $character->character_id,
            'alliance_id' => 123456,
        ]);

        Queue::assertPushedOn('high', AllianceInfoJob::class);
    }

    /** @test */
    public function it_dispatches_no_alliance_job_if_alliance_id_is_null()
    {
        Queue::fake();
        Queue::assertNothingPushed();

        $character = CharacterInfo::factory()->create();
        $character->character_affiliation()->delete();

        $character_affiliation = CharacterAffiliation::factory()->create([
            'character_id' => $character->character_id,
            'alliance_id' => null,
        ]);

        Queue::assertNotPushed(AllianceInfoJob::class);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_updates_affiliation_older_then_an_hours()
    {
        $old_data = CharacterAffiliation::factory()->create([
            'last_pulled' => now()->subMinutes(61),
        ]);

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
            'character_id' => $old_data->character_id,
        ]);

        $this->mockRetrieveEsiDataAction([$old_data->toArray()]);

        $job_container = new JobContainer([
            'character_id' => $old_data->character_id,
        ]);

        (new CharacterAffiliationJob($job_container))->handle();

        /*(new CharacterAffiliationAction)->execute($old_data->character_id);*/

        $this->assertDatabaseMissing('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_does_not_update_affiliation_younger_then_an_hours()
    {
        CharacterAffiliation::all()->each(function ($character_affiliation) {
            $character_affiliation->delete();
        });

        $old_data = CharacterAffiliation::factory()->create([
            'last_pulled' => now()->subMinutes(42),
        ]);

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
        ]);

        //$this->mockRetrieveEsiDataAction([$old_data->toArray()]);
        $this->assertRetrieveEsiDataIsNotCalled();

        //(new CharacterAffiliationAction)->execute($old_data->character_id);

        $job_container = new JobContainer([
            'character_id' => $old_data->character_id,
        ]);

        (new CharacterAffiliationJob($job_container))->handle();

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
            'character_id' => $old_data->character_id,
        ]);
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_updates_other_outdated_affiliations()
    {
        CharacterAffiliation::all()->each(function ($character_affiliation) {
            $character_affiliation->delete();
        });

        $old_datas = CharacterAffiliation::factory()->count(3)->create([
            'last_pulled' => now()->subMinutes(90),
        ]);

        //$old_datas = CharacterAffiliation::all();

        foreach ($old_datas as $old_data) {
            $this->assertDatabaseHas('character_affiliations', [
                'last_pulled' => $old_data->last_pulled,
            ]);
        }

        $this->mockRetrieveEsiDataAction(
            $old_datas->toArray()
        );

        // Only do send first character
        //(new CharacterAffiliationAction)->execute($old_datas->first()->character_id);

        $job_container = new JobContainer([
            'character_id' => $old_data->first()->character_id,
        ]);

        (new CharacterAffiliationJob($job_container))->handle();

        foreach ($old_datas as $old_data) {
            $this->assertDatabaseMissing('character_affiliations', [
                'character_id' => $old_data->character_id,
                'last_pulled' => $old_data->last_pulled,
            ]);
        }
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_updates_with_no_id_provided()
    {
        CharacterAffiliation::all()->each(function ($character_affiliation) {
            $character_affiliation->delete();
        });

        $old_data = CharacterAffiliation::factory()->create([
            'last_pulled' => now()->subMinutes(61),
        ]);

        $this->mockRetrieveEsiDataAction([
            $old_data->toArray(),
        ]);

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
        ]);

        //$return_value = (new CharacterAffiliationAction)->execute();
        (new CharacterAffiliationJob)->handle();

        //$this->assertNull($return_value);

        $this->assertDatabaseMissing('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
        ]);
    }
}
