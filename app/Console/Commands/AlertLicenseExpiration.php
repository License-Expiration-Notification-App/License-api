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
        ->where('no_of_renewals', '<', 2)
        ->where(function ($q) {
            $q->where('expiry_alert_sent', 'NOT LIKE', '%one month%')
            ->orWhere('expiry_alert_sent', NULL);
        })
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'one month,';
                $license->save();                
                $users = $license->client->users;
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                $title = "License requires renewal in <strong>one month</strong>";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) requires renewal on <strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action);
                
                $status = 'Renewal required in one month';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        }, $column = 'id');
    }
    private function alertTwoWeeksToExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users','subsidiary')
        ->where('two_weeks_before_expiration', $today)
        ->where('no_of_renewals', '<', 2)
        ->where(function ($q) {
            $q->where('expiry_alert_sent', 'NOT LIKE', '%two weeks%')
            ->orWhere('expiry_alert_sent', NULL);
        })
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'two weeks,';
                $license->save();                
                $users = $license->client->users;
                
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                $title = "License requires renewal in <strong>two weeks</strong>";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) requires renewal on <strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action);
                $status = 'Renewal required in two weeks';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        }, $column = 'id');
    }
    private function alertThreeDaysToExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users','subsidiary')
        ->where('three_days_before_expiration', $today)
        ->where('no_of_renewals', '<', 2)
        ->where(function ($q) {
            $q->where('expiry_alert_sent', 'NOT LIKE', '%three days%')
            ->orWhere('expiry_alert_sent', NULL);
        })
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'three days,';
                $license->save();
                $users = $license->client->users;
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                $title = "License requires renewal in <strong>three days</strong>";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) requires renewal on <strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action);
                $status = 'Renewal required in three days';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        }, $column = 'id');
    }
    private function alertTheRenewalDay()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users','subsidiary')
        ->where('renewal_date', '<=', $today)
        ->where('no_of_renewals', '<', 2)
        ->where(function ($q) {
            $q->where('expiry_alert_sent', 'NOT LIKE', '%renewal_due%')
            ->orWhere('expiry_alert_sent', NULL);
        })
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'renewal_due,';
                $license->save();
                $users = $license->client->users;
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                $title = "License Renewal Due";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) is due today, <strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action, '#FEE4E2');
                $status = 'Renewal Due';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        }, $column = 'id');
    }
    private function alertExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users','subsidiary')
        ->where('expiry_date', '<=', $today)
        ->where('no_of_renewals', '<', 2)
        ->where(function ($q) {
            $q->where('expiry_alert_sent', 'NOT LIKE', '%expired%')
            ->orWhere('expiry_alert_sent', NULL);
        })
        ->chunkById(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'expired,';
                $license->status = 'Expired';
                $license->save();
                $users = $license->client->users;
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                $title = "License Expired";
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) has expired today, <strong>$license->expiry_date</strong>";
                $this->logLicenseActivity($license, $license->expiry_date, $action, '#FEE4E2');
                $status = 'Expired';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        }, $column = 'id');
    }
    // private function logClientExpiryActivity()
    // {
    //     License::with('client', 'subsidiary')
    //     ->where('expiry_alert_sent', 'NOT LIKE', '%activity logged%')
    //     ->orWhere('expiry_alert_sent', NULL)
    //     ->chunkById(200, function ($licenses) {
    //         foreach ($licenses as $license) {
    //             $this->logLicenseActivity($license);
    //             $license->expiry_alert_sent .= 'activity logged,';
    //             $license->save();
    //         }
    //     }, $column = 'id');
    // }
    
    private function logLicenseActivity($license, $due_date, $description, $color_code = '#EAECF0') {
        $client = $license->client->company_name;
        $subsidiary = $license->subsidiary->name;
        $description = "<strong>$license->license_no</strong> for <strong>$subsidiary($client)</strong> requires renewal";
        LicenseActivity::firstOrCreate(
            [
                'client_id' => $license->client_id,
                'license_id' => $license->id,
                'uuid' => $license->id,
                'title' => "<strong>License Renewal</strong>",
                'due_date' => $due_date,
                'description' => $description,
            ], 
            [
            
                 'status' => 'Pending', 'color_code' => $color_code, 'type' => 'License Renewal'
            ]
        );
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $this->logClientExpiryActivity();
        $this->alertExpiration();
        $this->alertOneMonthToExpiration();
        $this->alertTwoWeeksToExpiration();
        $this->alertThreeDaysToExpiration();
        $this->alertTheRenewalDay();
    }
}
