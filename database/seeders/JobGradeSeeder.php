<?php

namespace Database\Seeders;

use App\Models\JobGrade;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobGradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobGrades = [
            [
                'grade' => 'I',
                'numeric_value' => 1,
                // 'description' => 'Initial grade for new employees or those developing foundational skills in their role.',
                // 'salary_multiplier' => 1.00,
            ],
            [
                'grade' => 'II',
                'numeric_value' => 2,
                // 'description' => 'Demonstrates consistent performance and growing competency in role requirements.',
                // 'salary_multiplier' => 1.10,
            ],
            [
                'grade' => 'III',
                'numeric_value' => 3,
                // 'description' => 'Proficient performance level with ability to handle complex tasks independently.',
                // 'salary_multiplier' => 1.20,
            ],
            [
                'grade' => 'IV',
                'numeric_value' => 4,
                // 'description' => 'Advanced expertise with capability to mentor others and lead initiatives.',
                // 'salary_multiplier' => 1.35,
            ],
            [
                'grade' => 'V',
                'numeric_value' => 5,
                // 'description' => 'Expert level with exceptional performance and strategic contribution to the organization.',
                // 'salary_multiplier' => 1.50,
            ],
        ];

        foreach ($jobGrades as $jobGrade) {
            // DB::table('job_grades')->insert([
            //     ...$jobGrade,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ]);

            JobGrade::create($jobGrade);
        }
    }
}
