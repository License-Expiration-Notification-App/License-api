<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\LicenseActivity;
use App\Models\User;
use App\Notifications\LicenseExpiration;
use Illuminate\Console\Command;
use Notification;
class AlertLicenseExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:alert-license-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command alerts when license is to expire';

    
    private function licenseExpiration($title, $action,$status = 'Expired', $clients = null)
    {

        // $user = $this->getUser();
        $users = User::where('role', 'staff')->get();
        if ($clients != null) {
            $users = $users->merge($clients);
        }
        $notification = new LicenseExpiration($title, $action, $status);
        return Notification::send($users->unique(), $notification);
    }
    private function alertOneMonthToExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users', 'subsidiary')
        ->where('one_month_before_expiration', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%one month%')
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',one month,';
                $license->save();                
                $users = $license->client->users;
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->name;
                $title = "License expires in <strong>one month</strong>";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) will expire on <strong>$license->expiry_date.</strong>";
                $status = 'Expire in one month';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    private function alertTwoWeeksToExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users','subsidiary')
        ->where('two_weeks_before_expiration', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%two weeks%')
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',two weeks,';
                $license->save();                
                $users = $license->client->users;
                
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->name;
                $title = "License expires in <strong>two weeks</strong>";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) will expire on <strong>$license->expiry_date.</strong>";
                $status = 'Expired in two weeks';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    private function alertThreeDaysToExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users','subsidiary')
        ->where('three_days_before_expiration', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%three days%')
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',three days,';
                $license->save();
                $users = $license->client->users;
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->name;
                $title = "License expires in <strong>three days</strong>";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) will expire on <strong>$license->expiry_date.</strong>";
                $status = 'Expires in three days';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    private function alertExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users','subsidiary')
        ->where('expiry_date', '<=', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%expired%')
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',expired,';
                $license->save();
                $users = $license->client->users;
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->name;
                $title = "License Expired";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) has expired today.";
                $status = 'Expired';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    private function logClientExpiryActivity()
    {
        License::with('client.users','subsidiary')
        // ->where('expiry_date', '<=', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%activity logged%')
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $this->logLicenseActivity($license);
                $license->expiry_alert_sent .= ',activity logged,';
                $license->save();
            }
        });
    }
    private function logLicenseActivity($license) {
        LicenseActivity::updateOrInsert(
            [
                'client_id' => $license->client_id,
                'license_id' => $license->id, 
                'title' => '<strong>License Renewal</strong>',
                'due_date', $license->expiry_date
            ], 
            [
            
                'status' => 'Pending', 'color_code' => '#98A2B3'
            ]
        );
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->logClientExpiryActivity();
        $this->alertExpiration();
        $this->alertOneMonthToExpiration();
        $this->alertTwoWeeksToExpiration();
        $this->alertThreeDaysToExpiration();
    }
}
