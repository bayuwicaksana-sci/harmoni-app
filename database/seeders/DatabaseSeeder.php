<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            DepartmentSeeder::class,
            JobGradeSeeder::class,
            JobLevelSeeder::class,
            JobTitleSeeder::class,
            ProgramCategorySeeder::class,
            ClientSeeder::class,
            PartnershipContractSeeder::class,
            EmployeeSeeder::class,
            ProgramSeeder::class,
            CoaSeeder::class,
            ContractProgramSeeder::class,
            TaxSeeder::class,
            RequestItemTypeSeeder::class,
            ApprovalWorkflowSeeder::class,
        ]);
    }
}
