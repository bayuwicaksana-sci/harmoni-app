<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Link programs to contracts (many-to-many)

        $links = [
            ['partnership_contract_id' => 1, 'program_id' => 1],
            ['partnership_contract_id' => 2, 'program_id' => 1],

            ['partnership_contract_id' => 1, 'program_id' => 2],
            ['partnership_contract_id' => 2, 'program_id' => 3],

            ['partnership_contract_id' => 3, 'program_id' => 4],

            ['partnership_contract_id' => 4, 'program_id' => 5],
            ['partnership_contract_id' => 4, 'program_id' => 6],
        ];

        foreach ($links as $link) {
            DB::table('contract_program')->insert([
                'partnership_contract_id' => $link['partnership_contract_id'],
                'program_id' => $link['program_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
