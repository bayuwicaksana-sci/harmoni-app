<?php

namespace Database\Seeders;

use App\Models\Coa;
use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all programs
        $programs = Program::all();

        $coas = [
            [
                'code' => 'PRG-2024-HEALTH-001',
                'name' => 'Community Health Clinic Development',
                'type' => 'program',
                'program_id' => 1,
                'contract_year' => 2024,
                'budget_amount' => 2000000000, // 2 Billion
                'is_active' => true,
            ],
            [
                'code' => 'PRG-2025-HEALTH-001',
                'name' => 'Community Health Clinic Development',
                'type' => 'program',
                'program_id' => 1,
                'contract_year' => 2025,
                'budget_amount' => 2500000000, // 2.5 Billion
                'is_active' => true,
            ],

            [
                'code' => 'PRG-2025-ENV-001',
                'name' => 'River Cleanup Initiative',
                'type' => 'program',
                'program_id' => 3,
                'contract_year' => 2025,
                'budget_amount' => 1500000000, // 1.5 Billion
                'is_active' => true,
            ],

            // Program 3: UMKM - 2 years
            [
                'code' => 'PRG-2024-ECO-001',
                'name' => 'UMKM Empowerment Program',
                'type' => 'program',
                'program_id' => 4,
                'contract_year' => 2024,
                'budget_amount' => 1000000000, // 1 Billion
                'is_active' => true,
            ],
            [
                'code' => 'PRG-2025-EDU-003',
                'name' => 'Digital Literacy Training',
                'type' => 'program',
                'program_id' => 5,
                'contract_year' => 2025,
                'budget_amount' => 2800000000, // 2.8 Billion
                'is_active' => true,
            ],

            // Program 8: Traditional Arts - 1 year
            [
                'code' => 'PRG-2024-SOCULT-001',
                'name' => 'Traditional Arts Preservation',
                'type' => 'program',
                'program_id' => 6,
                'contract_year' => 2024,
                'budget_amount' => 1000000000, // 1 Billion
                'is_active' => true,
            ],
        ];

        // Add general expense COAs
        $expenseCoas = [
            [
                'code' => 'EXP-ADMIN-001',
                'name' => 'Administration Expense',
                'type' => 'expense',
                'program_id' => null,
                'contract_year' => null,
                'budget_amount' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'EXP-TRAVEL-001',
                'name' => 'Travel Expense',
                'type' => 'expense',
                'program_id' => null,
                'contract_year' => null,
                'budget_amount' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'EXP-OFFICE-001',
                'name' => 'Office Supplies Expense',
                'type' => 'expense',
                'program_id' => null,
                'contract_year' => null,
                'budget_amount' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'EXP-EVENT-001',
                'name' => 'Event & Meeting Expense',
                'type' => 'expense',
                'program_id' => null,
                'contract_year' => null,
                'budget_amount' => 0,
                'is_active' => true,
            ],
            [
                'code' => 'EXP-CONSULT-001',
                'name' => 'Consultation & Professional Services',
                'type' => 'expense',
                'program_id' => null,
                'contract_year' => null,
                'budget_amount' => 0,
                'is_active' => true,
            ],
        ];

        $coas = array_merge($coas, $expenseCoas);

        foreach ($coas as $coa) {
            Coa::create($coa);
        }
    }
}
