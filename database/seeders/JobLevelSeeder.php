<?php

namespace Database\Seeders;

use App\Models\JobLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobLevels = [
            [
                'level' => 1,
                'name' => 'Support/Assistant',
                // 'description' => 'Entry-level positions providing administrative and operational support. Requires minimal experience and focuses on learning organizational processes.',
                // 'min_experience_years' => 0,
            ],
            [
                'level' => 2,
                'name' => 'Officer/Staff/Specialist',
                // 'description' => 'Professional positions with specific functional responsibilities. Requires foundational expertise and ability to work independently on assigned tasks.',
                // 'min_experience_years' => 1,
            ],
            [
                'level' => 3,
                'name' => 'Senior/Coordinator/Lead',
                // 'description' => 'Advanced professional positions with coordination responsibilities. May supervise junior staff and lead specific initiatives or projects.',
                // 'min_experience_years' => 3,
            ],
            [
                'level' => 4,
                'name' => 'Manager/Head of Unit',
                // 'description' => 'Middle management positions responsible for departmental units. Manages teams, develops strategies, and ensures operational excellence.',
                // 'min_experience_years' => 5,
            ],
            [
                'level' => 5,
                'name' => 'Director/Head of Department/Chief',
                // 'description' => 'Senior executive positions responsible for entire departments or organization. Sets strategic direction and makes critical decisions.',
                // 'min_experience_years' => 8,
            ],
        ];

        foreach ($jobLevels as $jobLevel) {
            // DB::table('job_levels')->insert([
            //     ...$jobLevel,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ]);

            JobLevel::create($jobLevel);
        }
    }
}
