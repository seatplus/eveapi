<?php

/*
 * MIT License
 *
 * Copyright (c) 2019, 2020, 2021 Felix Huber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Seatplus\Eveapi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Seatplus\Eveapi\Models\Character\CharacterInfo;
use Seatplus\Eveapi\Models\Wallet\WalletJournal;

class WalletJournalFactory extends Factory
{
    protected $model = WalletJournal::class;

    public function definition()
    {
        return [
            'id' => $this->faker->unique()->randomNumber,
            'wallet_journable_id' => $this->faker->numberBetween(90_000_000, 98_000_000),
            'wallet_journable_type' => CharacterInfo::class,
            'date' => $this->faker->iso8601,
            'description' => $this->faker->realText(),
            'ref_type' => $this->faker->randomElement($this->getRefType()),
            'amount' => $this->faker->optional()->randomFloat(2),
            'balance' => $this->faker->optional()->randomFloat(2),
            'reason' => $this->faker->optional()->text,
            'first_party_id' => $this->faker->randomElement([1000132, 2, 23269]),
            'second_party_id' => $this->faker->numberBetween(90_000_000, 98_000_000),
        ];
    }

    public function withLabels()
    {
        return $this->state(function () {
            return [
                'label_ids' => [1, 2, 3],
            ];
        });
    }

    private function getRefType(): array
    {
        return [
            'acceleration_gate_fee',
            'advertisement_listing_fee',
            'agent_donation',
            'agent_location_services',
            'agent_miscellaneous',
            'agent_mission_collateral_paid',
            'agent_mission_collateral_refunded',
            'agent_mission_reward',
            'agent_mission_reward_corporation_tax',
            'agent_mission_time_bonus_reward',
            'agent_mission_time_bonus_reward_corporation_tax',
            'agent_security_services',
            'agent_services_rendered',
            'agents_preward',
            'alliance_maintainance_fee',
            'alliance_registration_fee',
            'asset_safety_recovery_tax',
            'bounty',
            'bounty_prize',
            'bounty_prize_corporation_tax',
            'bounty_prizes',
            'bounty_reimbursement',
            'bounty_surcharge',
            'brokers_fee',
            'clone_activation',
            'clone_transfer',
            'contraband_fine',
            'contract_auction_bid',
            'contract_auction_bid_corp',
            'contract_auction_bid_refund',
            'contract_auction_sold',
            'contract_brokers_fee',
            'contract_brokers_fee_corp',
            'contract_collateral',
            'contract_collateral_deposited_corp',
            'contract_collateral_payout',
            'contract_collateral_refund',
            'contract_deposit',
            'contract_deposit_corp',
            'contract_deposit_refund',
            'contract_deposit_sales_tax',
            'contract_price',
            'contract_price_payment_corp',
            'contract_reversal',
            'contract_reward',
            'contract_reward_deposited',
            'contract_reward_deposited_corp',
            'contract_reward_refund',
            'contract_sales_tax',
            'copying',
            'corporate_reward_payout',
            'corporate_reward_tax',
            'corporation_account_withdrawal',
            'corporation_bulk_payment',
            'corporation_dividend_payment',
            'corporation_liquidation',
            'corporation_logo_change_cost',
            'corporation_payment',
            'corporation_registration_fee',
            'courier_mission_escrow',
            'cspa',
            'cspaofflinerefund',
            'datacore_fee',
            'dna_modification_fee',
            'docking_fee',
            'duel_wager_escrow',
            'duel_wager_payment',
            'duel_wager_refund',
            'ess_escrow_transfer',
            'factory_slot_rental_fee',
            'gm_cash_transfer',
            'industry_job_tax',
            'infrastructure_hub_maintenance',
            'inheritance',
            'insurance',
            'item_trader_payment',
            'jump_clone_activation_fee',
            'jump_clone_installation_fee',
            'kill_right_fee',
            'lp_store',
            'manufacturing',
            'market_escrow',
            'market_fine_paid',
            'market_transaction',
            'medal_creation',
            'medal_issued',
            'mission_completion',
            'mission_cost',
            'mission_expiration',
            'mission_reward',
            'office_rental_fee',
            'operation_bonus',
            'opportunity_reward',
            'planetary_construction',
            'planetary_export_tax',
            'planetary_import_tax',
            'player_donation',
            'player_trading',
            'project_discovery_reward',
            'project_discovery_tax',
            'reaction',
            'release_of_impounded_property',
            'repair_bill',
            'reprocessing_tax',
            'researching_material_productivity',
            'researching_technology',
            'researching_time_productivity',
            'resource_wars_reward',
            'reverse_engineering',
            'security_processing_fee',
            'shares',
            'skill_purchase',
            'sovereignity_bill',
            'store_purchase',
            'store_purchase_refund',
            'structure_gate_jump',
            'transaction_tax',
            'upkeep_adjustment_fee',
            'war_ally_contract',
            'war_fee',
            'war_fee_surrender',
        ];
    }
}
