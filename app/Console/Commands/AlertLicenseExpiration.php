<?php

namespace App\Console\Commands;

use App\Models\License;
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
        License::with('client.users')
        ->where('one_month_before_expiration', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%one month%')
        ->chunk(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',one month,';
                $license->save();
                $users = $license->client->users;
                $title = "One Month License Expiration Notice";
                //log this event
                $action = "$license->license_no will expire on $license->expiry_date. Kindly prepare for renewal";
                $status = 'Expire in one month';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    private function alertTwoWeeksToExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users')
        ->where('two_weeks_before_expiration', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%two weeks%')
        ->chunk(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',two weeks,';
                $license->save();
                $users = $license->client->users;
                $title = "Two Weeks License Expiration Notice";
                //log this event
                $action = "$license->license_no will expire on $license->expiry_date. Kindly initiate renewal process";
                $status = 'Expired in two weeks';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    private function alertThreeDaysToExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users')
        ->where('three_days_before_expiration', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%three days%')
        ->chunk(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',three days,';
                $license->save();
                $users = $license->client->users;
                $title = "Three Days License Expiration Notice";
                //log this event
                $action = "$license->license_no will expire on $license->expiry_date. Please renew.";
                $status = 'Expires in three days';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    private function alertExpiration()
    {
        $today  = date('Y-m-d', strtotime('now'));
        License::with('client.users')
        ->where('expiry_date', '<=', $today)
        ->where('expiry_alert_sent', 'NOT LIKE', '%expired%')
        ->chunk(200, function ($licenses) {
            foreach ($licenses as $license) {
                $license->expiry_alert_sent .= ',expired,';
                $license->save();
                $users = $license->client->users;
                $title = "License Expired";
                //log this event
                $action = "$license->license_no expired on $license->expiry_date. Please renew.";
                $status = 'Expired';
                $this->licenseExpiration($title, $action, $status, $users);
            }
        });
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
