<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Board of Directors',
                'code' => 'BOD',
                // 'description' => 'Executive leadership and strategic decision-making body responsible for overall organizational governance and direction.',
            ],
            [
                'name' => 'Program',
                'code' => 'PROG',
                // 'description' => 'Department responsible for planning, implementing, and monitoring all CSR programs and community development initiatives.',
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                // 'description' => 'Department managing financial operations, budgeting, accounting, and financial reporting for all organizational activities.',
            ],
            [
                'name' => 'Human Resources and General Affairs',
                'code' => 'HRGA',
                // 'description' => 'Department handling human resources management, organizational development, and general administrative affairs including IT support.',
            ],
        ];

        foreach ($departments as $department) {
            // DB::table('departments')->insert([
            //     ...$department,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ]);
            Department::create($department);
        }
    }
}
