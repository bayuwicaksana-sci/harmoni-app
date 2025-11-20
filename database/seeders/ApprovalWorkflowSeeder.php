<?php

namespace Database\Seeders;

use App\Models\ApprovalRule;
use App\Models\ApprovalWorkflow;
use App\Models\JobTitle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the standard approval workflow
        // $workflow = ApprovalWorkflow::find(1);
        // Create the standard approval workflow
        $workflow = ApprovalWorkflow::create([
            'name' => 'Standard Payment Request Approval',
            'is_active' => true,
        ]);

        // Get Job Titles
        $ceoJobTitle = JobTitle::where('code', 'CEO')->first();
        $headOfFinanceJobTitle = JobTitle::where('code', 'HOF')->first();

        // Rule 1: Supervisor always approves first
        ApprovalRule::create([
            'approval_workflow_id' => $workflow->id,
            'sequence' => 1,
            'condition_type' => 'always',
            'condition_value' => null,
            'approver_type' => 'supervisor',
            'approver_job_level_id' => null,
            'approver_job_title_id' => null,
        ]);

        // Rule 2: CEO approves if amount >= 5,000,000
        ApprovalRule::create([
            'approval_workflow_id' => $workflow->id,
            'sequence' => 2,
            'condition_type' => 'amount',
            'condition_value' => 5000000,
            'approver_type' => 'job_title',
            'approver_job_level_id' => null,
            'approver_job_title_id' => $ceoJobTitle->id,
        ]);

        // Rule 3: Head of Finance always approves last
        ApprovalRule::create([
            'approval_workflow_id' => $workflow->id,
            'sequence' => 3,
            'condition_type' => 'always',
            'condition_value' => null,
            'approver_type' => 'job_title',
            'approver_job_level_id' => null,
            'approver_job_title_id' => $headOfFinanceJobTitle->id,
        ]);
    }
}
