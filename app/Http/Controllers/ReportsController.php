<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Client;
use App\Models\License;
use App\Models\LicenseActivity;
use App\Models\Subsidiary;
use App\Models\Upload;
use App\Models\Exception;
use App\Models\Project;
use App\Models\RiskAssessment;
use App\Models\SOAArea;
use App\Models\Standard;
use App\Models\StatementOfApplicability;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    
    public function clientDataAnalysisDashbord(Request $request)
    {
        $user = $this->getUser();
        if($user->role != 'client') {
            return response()->json(['message' => 'You are not logged in as a client'], 403);
        }
        $today = date('Y-m-d', strtotime('now'));
        $client_id = $this->getClient()->id; //'9bf54f1b-ddbb-4641-a21a-058e667acf0d';
        $total_subsidiaries = Subsidiary::where('client_id', $client_id)->count();

        $total_licenses = License::where('client_id', $client_id)->count();
        
        $license_analysis = License::join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->where('licenses.client_id', $client_id)
        ->select('license_types.name as type',\DB::raw('COUNT(*) as total'))->groupBy('license_types.name')
        ->get();

        $pending_activities = LicenseActivity::where('client_id', $client_id)
        ->where('status', 'Pending')
        ->select('title', 'type', \DB::raw('COUNT(*) as total'))->groupBy('title', 'type')
        ->get();

        $total_pending_activities = LicenseActivity::where('client_id', $client_id)
        ->where('status', 'Pending')
        ->count();

        $due_license_renewals = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('license_activities.client_id', $client_id)
        ->where('title', 'LIKE', '%License Renewal%')
        ->where('license_activities.status', 'Pending')
        ->where('license_activities.due_date', '<=', $today)
        ->orderBy('license_activities.due_date', 'DESC')
        ->select('license_activities.license_id', 'license_activities.due_date', 'license_activities.title', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'subsidiaries.name as subsidiary', 'license_activities.color_code', 'license_activities.type')
        ->take(4)
        ->get();

        $due_reports = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('license_activities.client_id', $client_id)
        ->where('title', 'LIKE', '%Report%')
        ->where('license_activities.status', 'Pending')
        ->where('license_activities.due_date', '<=', $today)
        ->orderBy('license_activities.due_date', 'DESC')
        ->select('license_activities.license_id','license_activities.due_date', 'license_activities.title', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'subsidiaries.name as subsidiary', 'license_activities.color_code', 'license_activities.type')
        ->take(4)
        ->get();

        
        $activity_schedules = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('license_activities.client_id', $client_id)
        ->where('license_activities.status', 'Pending')
        ->select('license_activities.*', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'subsidiaries.name as subsidiary', 'license_activities.color_code', 'license_activities.type')
        ->get();
        // ->groupBy('due_date');
        
        return response()->json(compact('total_subsidiaries', 'total_licenses', 'pending_activities', 'total_pending_activities', 'license_analysis', 'due_license_renewals', 'due_reports', 'activity_schedules'), 200);
    }

    public function adminDataAnalysisDashbord(Request $request)
    {
        $user = $this->getUser();
        if($user->role != 'staff') {
            return response()->json(['message' => 'You are not a super admin'], 403);
        }
        $today = date('Y-m-d', strtotime('now'));
        $total_clients = Client::count();

        $total_licenses = License::count();
        
        $license_analysis = License::join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->select('license_types.name as type',\DB::raw('COUNT(*) as total'))->groupBy('license_types.name')
        ->get();

        $pending_activities = LicenseActivity::where('status', 'Pending')
        ->select('title', 'type', \DB::raw('COUNT(*) as total'))->groupBy('title', 'type')
        ->get();

        $total_pending_activities = LicenseActivity::where('status', 'Pending')
        ->count();

        $due_license_renewals = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('title', 'LIKE', '%License Renewal%')
        ->where('license_activities.status', 'Pending')
        ->where('license_activities.due_date', '<=', $today)
        ->orderBy('license_activities.due_date', 'DESC')
        ->select('license_activities.license_id','license_activities.due_date', 'license_activities.title', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'subsidiaries.name as subsidiary', 'license_activities.color_code', 'license_activities.type')
        ->take(4)
        ->get();

        $due_reports = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('title', 'LIKE', '%Report%')
        ->where('license_activities.status', 'Pending')
        ->where('license_activities.due_date', '<=', $today)
        ->orderBy('license_activities.due_date', 'DESC')
        ->select('license_activities.license_id','license_activities.due_date', 'license_activities.title', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'subsidiaries.name as subsidiary', 'license_activities.color_code', 'license_activities.type')
        ->take(4)
        ->get();

        
        $activity_schedules = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('license_activities.status', 'Pending')
        ->select('license_activities.*', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'subsidiaries.name as subsidiary', 'license_activities.color_code', 'license_activities.type')
        ->get();
        // ->groupBy('due_date');
        
        return response()->json(compact('total_clients', 'total_licenses', 'pending_activities', 'total_pending_activities', 'license_analysis', 'due_license_renewals', 'due_reports', 'activity_schedules'), 200);
    }
}
