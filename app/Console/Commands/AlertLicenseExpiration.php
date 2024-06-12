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

    
    private function licenseExpiration($title, $action,$status = 'Expired', $users)
    {
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
            $count = 0;
            $title = "The following Licences require renewal in&nbsp;<strong>one month</strong>";
            $table = '<table border="1" cellpadding="2"><tr><td>Company</td><td>Subsidiary</td><td>Licence Number</td><td>Renewal Date</td><td>Expiry Date</td></tr>';
            $users = User::where('role', 'staff')->get();
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'one month,';
                $license->save();
                $users = $users->merge($license->client->users);
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;                
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) requires renewal on&nbsp;<strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action);

                $table .= "<tr><td>$client</td><td>$subsidiary</td><td>$license->license_no</td><td>$license->renewal_date</td><td>$license->expiry_date</td><tr>";
                $count++;
                
            }
            $table .= "</table>";
            $status = 'Renewal required in one month';
            if ($count > 0) {
                $this->licenseExpiration($title, $table, $status, $users);
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
            $count = 0;
            $title = "The following Licences require renewal in&nbsp;<strong>two weeks</strong>";
            $table = '<table border="1" cellpadding="2"><tr><td>Company</td><td>Subsidiary</td><td>Licence Number</td><td>Renewal Date</td><td>Expiry Date</td></tr>';
            $users = User::where('role', 'staff')->get();
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'two weeks,';
                $license->save();                
                $users = $users->merge($license->client->users);
                
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) requires renewal on&nbsp;<strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action);

                $table .= "<tr><td>$client</td><td>$subsidiary</td><td>$license->license_no</td><td>$license->renewal_date</td><td>$license->expiry_date</td><tr>";
                $count++;
                
            }
            $table .= "</table>";
            $status = 'Renewal required in two weeks';
            if ($count > 0) {
                $this->licenseExpiration($title, $table, $status, $users);
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
            $count = 0;
            $title = "The following Licences require renewal in&nbsp;<strong>three days</strong>";
            $table = '<table border="1" cellpadding="2"><tr><td>Company</td><td>Subsidiary</td><td>Licence Number</td><td>Renewal Date</td><td>Expiry Date</td></tr>';
            $users = User::where('role', 'staff')->get();
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'three days,';
                $license->save();
                $users = $users->merge($license->client->users);
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) requires renewal on&nbsp;<strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action);

                $table .= "<tr><td>$client</td><td>$subsidiary</td><td>$license->license_no</td><td>$license->renewal_date</td><td>$license->expiry_date</td><tr>";
                $count++;

            }
            $table .= "</table>";
            $status = 'Renewal required in three days';
            if ($count > 0) {
                $this->licenseExpiration($title, $table, $status, $users);
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
            $count = 0;
            $title = "The following Licences are due for Renewal";
            $table = '<table border="1" cellpadding="2"><tr><td>Company</td><td>Subsidiary</td><td>Licence Number</td><td>Renewal Date</td><td>Expiry Date</td></tr>';
            $users = User::where('role', 'staff')->get();
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'renewal_due,';
                $license->save();
                $users = $users->merge($license->client->users);
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) is due today,&nbsp;<strong>$license->renewal_date.</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action, '#FEE4E2');

                $table .= "<tr><td>$client</td><td>$subsidiary</td><td>$license->license_no</td><td>$license->renewal_date</td><td>$license->expiry_date</td><tr>";
                $count++;
            }
            $table .= "</table>";
            $status = 'Renewal Due';
            if ($count > 0) {
                $this->licenseExpiration($title, $table, $status, $users);
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
            $count = 0;
            $title = "License Expired";
            $table = '<table border="1" cellpadding="2"><tr><td>Company</td><td>Subsidiary</td><td>Licence Number</td><td>Renewal Date</td><td>Expiry Date</td></tr>';
            $users = User::where('role', 'staff')->get();
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= 'expired,';
                $license->status = 'Expired';
                $license->save();
                $users = $users->merge($license->client->users);
                $subsidiary = $license->subsidiary->name;
                $client = $license->client->company_name;
                
                //log this event
                $action = "<strong>$license->license_no</strong> for $subsidiary ($client) has expired today,&nbsp;<strong>$license->expiry_date</strong>";
                $this->logLicenseActivity($license, $license->renewal_date, $action, '#FEE4E2');

                $table .= "<tr><td>$client</td><td>$subsidiary</td><td>$license->license_no</td><td>$license->renewal_date</td><td>$license->expiry_date</td><tr>";
                $count++;
            }
            $table .= "</table>";
            $status = 'Expired';
            if ($count > 0) {
                $this->licenseExpiration($title, $table, $status, $users);
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
        $description = "<strong>$license->license_no</strong> for&nbsp;<strong>$subsidiary($client)</strong> requires renewal";
        LicenseActivity::firstOrCreate(
            [
                'client_id' => $license->client_id,
                'license_id' => $license->id,
                'uuid' => $license->id,
                'title' => "<strong>Licence Renewal</strong>",
                
            ], 
            [
                'due_date' => $due_date,
                'description' => $description, 'status' => 'Pending', 'color_code' => $color_code, 'type' => 'Licence Renewal'
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
