<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\JobLevel;
use App\Models\JobTitle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobTitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all()->pluck('id', 'code');
        $jobLevels = JobLevel::all()->pluck('id', 'level');

        $jobTitles = [
            // Board of Directors
            [
                'title' => 'Chief Executive Officer',
                'code' => 'CEO',
                'department_id' => $departments['BOD'],
                'job_level_id' => $jobLevels[5],
                // 'responsibilities' => 'Provides overall strategic leadership and vision for the organization. Oversees all departments and ensures alignment with organizational mission. Represents SCI in high-level stakeholder engagements and partnership negotiations.',
                // 'requirements' => 'Minimum 10 years of executive leadership experience. Proven track record in CSR or social development sector. Strong strategic thinking and stakeholder management capabilities.',
                'is_active' => true,
            ],

            // Finance Department
            [
                'title' => 'Head of Finance',
                'code' => 'HOF',
                'department_id' => $departments['FIN'],
                'job_level_id' => $jobLevels[5],
                // 'responsibilities' => 'Leads all financial operations including budgeting, accounting, and financial reporting. Ensures financial compliance and develops financial strategies. Manages relationships with auditors and financial institutions.',
                // 'requirements' => 'Professional accounting certification (CPA or equivalent). Minimum 8 years of financial management experience. Expertise in non-profit financial management and CSR funding mechanisms.',
                'is_active' => true,
            ],
            [
                'title' => 'Finance Operator',
                'code' => 'FO',
                'department_id' => $departments['FIN'],
                'job_level_id' => $jobLevels[2],
                // 'responsibilities' => 'Handles day-to-day financial transactions and bookkeeping. Processes payments and reimbursements. Maintains financial records and assists in report preparation.',
                // 'requirements' => 'Diploma or degree in accounting or finance. Basic knowledge of accounting principles and software. Attention to detail and numerical accuracy.',
                'is_active' => true,
            ],

            // Program Department
            [
                'title' => 'Head of Program',
                'code' => 'HOP',
                'department_id' => $departments['PROG'],
                'job_level_id' => $jobLevels[5],
                // 'responsibilities' => 'Oversees all CSR program planning and implementation. Develops program strategies and ensures quality delivery. Manages partnerships with clients and stakeholders for program execution.',
                // 'requirements' => 'Advanced degree in social sciences or development studies. Minimum 8 years of program management experience in CSR or development sector. Strong project management and stakeholder engagement skills.',
                'is_active' => true,
            ],
            [
                'title' => 'Community Development Officer',
                'code' => 'CDO',
                'department_id' => $departments['PROG'],
                'job_level_id' => $jobLevels[2],
                // 'responsibilities' => 'Implements community development programs in the field. Conducts community assessments and beneficiary engagement. Monitors program progress and prepares field reports.',
                // 'requirements' => 'Degree in social sciences or related field. Experience in community engagement and development work. Strong communication and interpersonal skills.',
                'is_active' => true,
            ],
            [
                'title' => 'Staff Program',
                'code' => 'SP',
                'department_id' => $departments['PROG'],
                'job_level_id' => $jobLevels[2],
                // 'responsibilities' => 'Supports program planning and implementation activities. Assists in documentation and report preparation. Coordinates logistics for program activities and events.',
                // 'requirements' => 'Degree in any relevant field. Basic understanding of CSR and development concepts. Good organizational and administrative skills.',
                'is_active' => true,
            ],

            // HRGA Department
            [
                'title' => 'Head of HRGA',
                'code' => 'HOHRGA',
                'department_id' => $departments['HRGA'],
                'job_level_id' => $jobLevels[5],
                // 'responsibilities' => 'Leads human resources and general affairs functions. Develops HR policies and organizational development strategies. Oversees facilities management and administrative operations.',
                // 'requirements' => 'Degree in human resources management or business administration. Minimum 8 years of HR leadership experience. Comprehensive knowledge of Indonesian labor laws and regulations.',
                'is_active' => true,
            ],
            [
                'title' => 'Manager of HRGA',
                'code' => 'MHRGA',
                'department_id' => $departments['HRGA'],
                'job_level_id' => $jobLevels[4],
                // 'responsibilities' => 'Manages day-to-day HR and general affairs operations. Implements HR policies and procedures. Handles recruitment, employee relations, and administrative management.',
                // 'requirements' => 'Degree in human resources or related field. Minimum 5 years of HR experience. Strong knowledge of HR best practices and labor regulations.',
                'is_active' => true,
            ],
            [
                'title' => 'Staff HRGA',
                'code' => 'SHRGA',
                'department_id' => $departments['HRGA'],
                'job_level_id' => $jobLevels[2],
                // 'responsibilities' => 'Supports HR and administrative functions. Maintains employee records and documentation. Assists in recruitment processes and event coordination.',
                // 'requirements' => 'Diploma or degree in human resources or business administration. Basic knowledge of HR processes. Good administrative and communication skills.',
                'is_active' => true,
            ],
            [
                'title' => 'IT Support & Office Administrator',
                'code' => 'ITOA',
                'department_id' => $departments['HRGA'],
                'job_level_id' => $jobLevels[2],
                // 'responsibilities' => 'Provides IT support and maintains office systems. Manages office supplies and equipment. Troubleshoots technical issues and coordinates with vendors.',
                // 'requirements' => 'Diploma in IT or computer science. Basic knowledge of hardware, software, and networking. Problem-solving skills and customer service orientation.',
                'is_active' => true,
            ],
        ];

        foreach ($jobTitles as $jobTitle) {
            // DB::table('job_titles')->insert([
            //     ...$jobTitle,
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // ]);
            JobTitle::create($jobTitle);
        }
    }
}
