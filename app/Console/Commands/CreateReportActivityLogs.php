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
        Report::with('client', 'license', 'subsidiary')->where('report_type', 'Yearly')
        ->where('entry_date', NULL)
        ->chunk(200, function ($reports) {
            foreach ($reports as $report) {
                $license = $report->license;
                $client = $report->client->company_name;
                $subsidiary = $report->subsidiary->name;

                $description = "<strong>$license->license_no</strong> annual report for <strong>$subsidiary($client)</strong> is due on <strong>$report->due_date</strong>";
                $year = date('Y', strtotime($report->due_date));
                $this->logLicenseActivity($report, "<strong>Annual Report for $year</strong>", $description, $report->due_date);
            }
        });
    }
    private function dueQuarterlyReport()
    {
        Report::with('client', 'license', 'subsidiary')->where('report_type', 'Quarterly')
        ->where('entry_date', NULL)
        ->chunk(200, function ($reports) {
            foreach ($reports as $report) {          
                $license = $report->license;
                $client = $report->client->company_name;
                $subsidiary = $report->subsidiary->name;

                $description = "<strong>$license->license_no</strong> quarterly report for <strong>$subsidiary($client)</strong> is due on <strong>$report->due_date</strong>";
                $month = date('F', strtotime($report->due_date));
                $this->logLicenseActivity($report, "<strong>Quarterly Report for $month</strong>", $description, $report->due_date);
            }
        });
    }
    private function logLicenseActivity($report, $title, $desc, $date) {
        LicenseActivity::firstOrCreate(
            [
                'client_id' => $report->client_id,
                'uuid' => $report->id,
                'license_id' => $report->license_id, 
                'title' => $title,
                'due_date' => $date,
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
