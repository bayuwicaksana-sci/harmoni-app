<?php

namespace Database\Seeders;

use App\Models\JobGrade;
use App\Models\JobTitle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Get job titles
        $ceo = JobTitle::where('code', 'CEO')->first();
        $headOfFinance = JobTitle::where('code', 'HOF')->first();
        $financeOperator = JobTitle::where('code', 'FO')->first();
        $headOfProgram = JobTitle::where('code', 'HOP')->first();
        $cdOfficer = JobTitle::where('code', 'CDO')->first();
        $staffProgram = JobTitle::where('code', 'SP')->first();
        $headOfHrga = JobTitle::where('code', 'HOHRGA')->first();
        $managerHrga = JobTitle::where('code', 'MHRGA')->first();
        $staffHrga = JobTitle::where('code', 'SHRGA')->first();
        $itSupport = JobTitle::where('code', 'ITOA')->first();

        // Get job grades
        $grade1 = JobGrade::where('numeric_value', 1)->first();
        $grade2 = JobGrade::where('numeric_value', 2)->first();
        $grade3 = JobGrade::where('numeric_value', 3)->first();
        $grade4 = JobGrade::where('numeric_value', 4)->first();
        $grade5 = JobGrade::where('numeric_value', 5)->first();

        $allEmployeeData = [
            [ // Cahyo
                'user_data' => [
                    'name' => 'Cahyo Adileksana',
                    'email' => 'cahyo.adileksana@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Cahyo@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 1,
                    'internal_id' => '992200001',
                    'supervisor_id' => null,
                    'bank_name' => 'Mandiri',
                    'job_title_id' => $ceo->id,
                    'job_grade_id' => $grade4->id,
                    'bank_account_number' => '1370016965408',
                    'bank_cust_name' => 'Cahyo Adileksana',
                ],
            ],
            [ // Situt
                'user_data' => [
                    'name' => 'Situt Setiawan',
                    'email' => 'situt.setiawan@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Situt@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 2,
                    'internal_id' => '992200002',
                    'supervisor_id' => 1,
                    'job_title_id' => $headOfFinance->id,
                    'job_grade_id' => $grade1->id,
                    'bank_name' => 'BCA',
                    'bank_account_number' => '4560846466',
                    'bank_cust_name' => 'Situt Setiawan',
                ],
            ],
            [ // Ananta CHECK
                'user_data' => [
                    'name' => 'Ananta Bayu Pratama',
                    'email' => 'ananta.bayu@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Ananta@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 3,
                    'internal_id' => '992200003',
                    'supervisor_id' => 1,
                    'job_title_id' => $headOfProgram->id,
                    'job_grade_id' => $grade4->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1850002963269',
                    'bank_cust_name' => 'Ananta Bayu Pratama',
                ],
            ],
            [ // Fitria
                'user_data' => [
                    'name' => 'Fitria Alhumaira',
                    'email' => 'fitria.alhumaira@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Fitria@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 4,
                    'internal_id' => '992200004',
                    'supervisor_id' => 1,
                    'job_title_id' => $headOfHrga->id,
                    'job_grade_id' => $grade1->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1370015368695',
                    'bank_cust_name' => 'Fitria Alhumaira',
                ],
            ],
            [ // Sofiana CHECK
                'user_data' => [
                    'name' => 'Sofiana Nur Khasanah',
                    'email' => 'sofiana.khasanah@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Sofiana@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 5,
                    'internal_id' => '992200005',
                    'supervisor_id' => 4,
                    'job_title_id' => $managerHrga->id,
                    'job_grade_id' => $grade3->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1380020780206',
                    'bank_cust_name' => 'Sofiana Nur Khasanah',
                ],
            ],
            [ // Sunita
                'user_data' => [
                    'name' => 'Sunita Ayu Purnamaningsi',
                    'email' => 'sunita.ayu@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Sunita@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 6,
                    'internal_id' => '992200006',
                    'supervisor_id' => 4,
                    'job_title_id' => $staffHrga->id,
                    'job_grade_id' => $grade3->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1510016696335',
                    'bank_cust_name' => 'Sunita Ayu Purnamaningsi',
                ],
            ],
            [ // Bhaskara
                'user_data' => [
                    'name' => 'Bhaskara Anggarda',
                    'email' => 'bhaskara.anggarda@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Bhaskara@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 7,
                    'internal_id' => '992200007',
                    'supervisor_id' => 3,
                    'job_title_id' => $staffProgram->id,
                    'job_grade_id' => $grade3->id,
                    'bank_name' => null,
                    'bank_account_number' => null,
                    'bank_cust_name' => null,
                ],
            ],
            [ // Dedy
                'user_data' => [
                    'name' => 'Dedy Wahyu Rizaldy',
                    'email' => 'dedy.wahyu@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Dedy@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 8,
                    'internal_id' => '992200008',
                    'supervisor_id' => 3,
                    'job_title_id' => $cdOfficer->id,
                    'job_grade_id' => $grade2->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1510021016909',
                    'bank_cust_name' => 'Dedy Wahyu Rizaldy',
                ],
            ],
            [ // Syair
                'user_data' => [
                    'name' => 'Muh. Syair',
                    'email' => 'muh.syair@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Syair@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 9,
                    'internal_id' => '992200009',
                    'supervisor_id' => 3,
                    'job_title_id' => $cdOfficer->id,
                    'job_grade_id' => $grade2->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1700016409892',
                    'bank_cust_name' => 'Muh. Syair',
                ],
            ],
            [ // Maria
                'user_data' => [
                    'name' => 'Maria Ririh Handayani',
                    'email' => 'maria.ririh@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Maria@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 10,
                    'internal_id' => '992200010',
                    'supervisor_id' => 2,
                    'job_title_id' => $financeOperator->id,
                    'job_grade_id' => $grade2->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1150006837548',
                    'bank_cust_name' => 'Maria Ririh Handayani',
                ],
            ],
            [ // Bayu
                'user_data' => [
                    'name' => 'Bayu Ajiwicaksana',
                    'email' => 'bayu.wicaksana@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Bayu@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 11,
                    'internal_id' => '992200011',
                    'supervisor_id' => 5,
                    'job_title_id' => $itSupport->id,
                    'job_grade_id' => $grade2->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1370020827420',
                    'bank_cust_name' => 'Bayu Ajiwicaksana',
                ],
            ],
            [ // Sofa
                'user_data' => [
                    'name' => 'Sofa Afi Rahmawati',
                    'email' => 'sofa.afi@scindonesia.org',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Sofa@Harmoni'),
                    'user_type' => 'internal',
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                'employee_data' => [
                    'user_id' => 12,
                    'internal_id' => '992200012',
                    'supervisor_id' => 2,
                    'job_title_id' => $financeOperator->id,
                    'job_grade_id' => $grade2->id,
                    'bank_name' => 'Mandiri',
                    'bank_account_number' => '1380020857269',
                    'bank_cust_name' => 'Sofa Afi Rahmawati',
                ],
            ],
        ];

        foreach ($allEmployeeData as $employeeData) {
            foreach ($employeeData as $type => $value) {
                if ($type === 'user_data') {
                    DB::table('users')->insert($employeeData['user_data']);
                } else {
                    DB::table('employees')->insert($employeeData['employee_data']);
                }
            }
        }
    }
}
