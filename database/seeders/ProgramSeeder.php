<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $health = ProgramCategory::where('code', 'HEALTH')->first();
        $education = ProgramCategory::where('code', 'EDU')->first();
        $economy = ProgramCategory::where('code', 'ECON')->first();
        $environment = ProgramCategory::where('code', 'ENV')->first();
        $socialCulture = ProgramCategory::where('code', 'SOCULT')->first();

        // Get contracts
        // $ptm2024 = PartnershipContract::where('contract_number', 'DMF/CSR/2024/001')->first();
        // $ptm2025 = PartnershipContract::where('contract_number', 'DMF/CSR/2025/001')->first();
        // $bm2024 = PartnershipContract::where('contract_number', 'BM/CSR/2024/001')->first();
        // $tlk2025 = PartnershipContract::where('contract_number', 'TLK/CSR/2025/001')->first();

        // Get series
        // $healthClinicSeries = ProgramSeries::where('code', 'DMF-HEALTH-CLINIC')->first();
        // $scholarshipSeries = ProgramSeries::where('code', 'DMF-EDU-SCHOLARSHIP')->first();
        // $umkmSeries = ProgramSeries::where('code', 'BM-ECONOMY-UMKM')->first();

        $programs = [
            [
                'program_category_id' => $health->id,
                'name' => 'Community Health Clinic Development',
                'code' => 'DMF-HEALTH',
            ],
            [
                'program_category_id' => $education->id,
                'name' => 'Scholarship Program',
                'code' => 'DMF-EDU',
            ],

            // One-year program (Pertamina - Environment)
            [
                'program_category_id' => $environment->id,
                'name' => 'River Cleanup Initiative',
                'code' => 'DMF-ENV',
            ],

            // One-year program (Bank Mandiri - UMKM, but has series for future)
            [
                'program_category_id' => $economy->id,
                'name' => 'UMKM Empowerment Program',
                'code' => 'BM-ECO',
            ],

            // One-year program (Telkom - Education)
            [
                'program_category_id' => $education->id,
                'name' => 'Digital Literacy Training',
                'code' => 'TLK-EDU',
            ],

            // One-year program (Telkom - Social Culture)
            [
                'program_category_id' => $socialCulture->id,
                'name' => 'Traditional Arts Preservation',
                'code' => 'TLK-SOC',
            ],
        ];

        foreach ($programs as $program) {
            Program::create($program);
        }
    }
}
