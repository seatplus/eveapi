<?php


namespace Seatplus\Eveapi\Tests\Unit\Actions\Jobs\Character;


use Seatplus\Eveapi\Actions\Jobs\Character\CharacterAffiliationAction;
use Seatplus\Eveapi\Models\Character\CharacterAffiliation;
use Seatplus\Eveapi\Tests\TestCase;
use Seatplus\Eveapi\Tests\Traits\MockRetrieveEsiDataAction;

class CharacterAffiliationActionTest extends TestCase
{
    use MockRetrieveEsiDataAction;

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_returns_affiliation_for_a_character_id()
    {
        $mock_data = $this->buildMockEsiData();

        $result = (new CharacterAffiliationAction)->execute($mock_data->character_id);

        $this->assertEquals(
            $mock_data->character_id,
            $result->character_id
        );
    }

    /**
     * @test
     * @runTestsInSeparateProcesses
     */
    public function it_updates_affiliation_older_then_an_hours()
    {

        $old_data = CharacterAffiliation::factory()->create([
            'last_pulled' => now()->subMinutes(61)
        ]);

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
            'character_id' => $old_data->character_id
        ]);

        $this->mockRetrieveEsiDataAction([$old_data->toArray()]);

        (new CharacterAffiliationAction)->execute($old_data->character_id);

        $this->assertDatabaseMissing('character_affiliations', [
            'last_pulled' => $old_data->last_pulled
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
            'last_pulled' => now()->subMinutes(42)
        ]);

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled
        ]);

        $this->mockRetrieveEsiDataAction([$old_data->toArray()]);

        (new CharacterAffiliationAction)->execute($old_data->character_id);

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled,
            'character_id' => $old_data->character_id
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
            'last_pulled' => now()->subMinutes(90)
        ]);

        //$old_datas = CharacterAffiliation::all();

        foreach ($old_datas as $old_data)
            $this->assertDatabaseHas('character_affiliations', [
                'last_pulled' => $old_data->last_pulled
            ]);

        $this->mockRetrieveEsiDataAction(
            $old_datas->toArray()
        );

        // Only do send first character
        (new CharacterAffiliationAction)->execute($old_datas->first()->character_id);

        foreach ($old_datas as $old_data)
            $this->assertDatabaseMissing('character_affiliations', [
                'character_id' => $old_data->character_id,
                'last_pulled' => $old_data->last_pulled
            ]);
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
            'last_pulled' => now()->subMinutes(61)
        ]);

        $this->mockRetrieveEsiDataAction([
                $old_data->toArray()
        ]);

        $this->assertDatabaseHas('character_affiliations', [
            'last_pulled' => $old_data->last_pulled
        ]);

        $return_value = (new CharacterAffiliationAction)->execute();

        $this->assertNull($return_value);

        $this->assertDatabaseMissing('character_affiliations', [
            'last_pulled' => $old_data->last_pulled
        ]);
    }


    private function buildMockEsiData()
    {
        $mock_data = CharacterAffiliation::factory()->make();

        $this->mockRetrieveEsiDataAction([$mock_data->toArray()]);

        return $mock_data;
    }

}
