<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\PartnershipContract;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PartnershipContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pertamina = Client::where('code', 'DMF')->first();
        $mandiri = Client::where('code', 'BM')->first();
        $telkom = Client::where('code', 'TLK')->first();

        $contracts = [
            // Pertamina - Multi-year contracts (2024, 2025)
            [
                'client_id' => $pertamina->id,
                'contract_number' => 'DMF/CSR/2024/001',
                'contract_year' => 2024,
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'contract_value' => 500000000, // 500 million
            ],
            [
                'client_id' => $pertamina->id,
                'contract_number' => 'DMF/CSR/2025/001',
                'contract_year' => 2025,
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'contract_value' => 600000000, // 600 million
            ],

            // Bank Mandiri - 2024 only
            [
                'client_id' => $mandiri->id,
                'contract_number' => 'BM/CSR/2024/001',
                'contract_year' => 2024,
                'start_date' => '2024-03-01',
                'end_date' => '2024-12-31',
                'contract_value' => 300000000, // 300 million
            ],

            // Telkom - 2025 only
            [
                'client_id' => $telkom->id,
                'contract_number' => 'TLK/CSR/2025/001',
                'contract_year' => 2025,
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'contract_value' => 450000000, // 450 million
            ],
        ];

        foreach ($contracts as $contract) {
            PartnershipContract::create($contract);
        }
    }
}
