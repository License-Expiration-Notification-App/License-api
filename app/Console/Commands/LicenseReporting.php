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
class LicenseReporting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:license-reporting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command alerts when license reporting is due';

    private function createQuarterlyLicenseReporting()
    {
        $due_date = Carbon::now()->endOfQuarter();
        License::chunk(200, function ($licenses) use ($due_date) {
            foreach ($licenses as $license) {
                Report::firstOrCreate(
                    [
                        'client_id' => $license->client_id, 
                        'subsidiary_id' => $license->subsidiary_id,
                        'license_id' => $license->id,
                        'report_type' => 'Quarterly',
                        'due_date' => date('Y-m-d' ,strtotime(($due_date))),
                    ],
                );
            }
        });
    }
    private function createYearlyLicenseReporting()
    {
        $due_date = Carbon::now()->endOfYear();
        License::chunk(200, function ($licenses) use ($due_date) {
            foreach ($licenses as $license) {
                Report::firstOrCreate(
                    [
                        'client_id' => $license->client_id, 
                        'subsidiary_id' => $license->subsidiary_id,
                        'license_id' => $license->id,
                        'report_type' => 'Yearly',
                        'due_date' => date('Y-m-d' ,strtotime(($due_date))),
                    ],
                );
            }
        });
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->createQuarterlyLicenseReporting();
        $this->createYearlyLicenseReporting();
    }
}
