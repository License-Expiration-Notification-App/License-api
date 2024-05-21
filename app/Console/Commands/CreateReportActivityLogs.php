<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\LicenseActivity;
use App\Models\Report;
use App\Models\User;
use App\Notifications\LicenseExpiration;
use Illuminate\Console\Command;
use Notification;
use Carbon\Carbon;
class CreateReportActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-report-activity-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command logs due reports';

    private function dueYearlyReport()
    {
        Report::with('client.users', 'subsidiary')
        ->where('report_type', 'yearly')
        ->where('entry_date', NULL)
        ->chunk(200, function ($reports) {
            foreach ($reports as $report) {
                $year = date('Y', strtotime($report->due_date));
                $this->logLicenseActivity($report, "<strong>Yearly Report</strong>", "For $year", $report->due_date);
            }
        });
    }
    private function dueQuarterlyReport()
    {
        Report::with('client.users', 'subsidiary')
        ->where('report_type', 'quarterly')
        ->where('entry_date', NULL)
        ->chunk(200, function ($reports) {
            foreach ($reports as $report) {          
                
                $month = date('F', strtotime($report->due_date));
                $this->logLicenseActivity($report, "<strong>Quarterly Report</strong>", "For $month", $report->due_date);
            }
        });
    }
    private function logLicenseActivity($report, $title, $desc, $date) {
        LicenseActivity::updateOrInsert(
            [
                'uuid' => $report->id,
                'license_id' => $report->license_id, 
                'title' => $title,
                'due_date', $date,
            ],
            ['description' =>$desc, 'status' => 'Pending', 'color_code' => '#735812']
        );
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dueYearlyReport();
        $this->dueQuarterlyReport();
    }
}
