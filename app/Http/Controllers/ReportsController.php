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
        $today = date('Y-m-d', strtotime('now'));
        $client_id = '9bf54f1b-ddbb-4641-a21a-058e667acf0d'; //$this->getClient()->client_id; //'9bf54f1b-ddbb-4641-a21a-058e667acf0d';
        $total_subsidiaries = Subsidiary::where('client_id', $client_id)->count();

        $total_licenses = License::where('client_id', $client_id)->count();
        
        $license_analysis = License::join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->where('licenses.client_id', $client_id)
        ->select('license_types.name as type',\DB::raw('COUNT(*) as total'))->groupBy('license_types.name')
        ->get();

        $pending_activities = LicenseActivity::where('client_id', $client_id)
        ->where('status', 'Pending')
        ->select('title',\DB::raw('COUNT(*) as total'))->groupBy('title')
        ->get();

        $total_pending_activities = LicenseActivity::where('client_id', $client_id)
        ->where('status', 'Pending')
        ->count();

        $due_license_renewals = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('license_activities.client_id', $client_id)
        ->where('title', 'LIKE', '%License Renewal%')
        ->where('license_activities.status', 'Pending')
        ->where('license_activities.due_date', '<=', $today)
        ->select('license_activities.due_date', 'license_activities.title', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral')
        ->get();

        $due_reports = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('license_activities.client_id', $client_id)
        ->where('title', 'LIKE', '%Report%')
        ->where('license_activities.status', 'Pending')
        ->where('license_activities.due_date', '<=', $today)
        ->select('license_activities.due_date', 'license_activities.title', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral')
        ->get();

        
        $activity_schedules = LicenseActivity::join('licenses', 'license_activities.license_id', '=', 'licenses.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('clients', 'license_activities.client_id', '=', 'clients.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->where('license_activities.client_id', $client_id)
        ->where('license_activities.status', 'Pending')
        ->select('license_activities.*', 'clients.company_name as client', 'license_types.slug as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral')
        ->groupBy('license_activities.due_date')
        ->get();
        
        return response()->json(compact('total_subsidiaries', 'total_licenses', 'pending_activities', 'total_pending_activities', 'license_analysis', 'due_license_renewals', 'due_reports', 'activity_schedules'), 200);
    }

    public function adminDataAnalysisDashbord(Request $request)
    {
        // $year = date('Y', strtotime('now'));
        $client = $this->getClient();
        $my_projects = $this->getMyProjects();
        // $uploaded_documents = Upload::where(['client_id' => $client->id, 'is_exception' => 0])->where('link', '!=', NULL)->count();
        // $expected_documents = Upload::where(['client_id' => $client->id])->count();
        // $answered_questions = Answer::where(['client_id' => $client->id, 'is_exception' => 0])->where('is_submitted', 1)->count();
        // $all_questions = Answer::where(['client_id' => $client->id])->count();
        // $exceptions = Exception::where('client_id', $client->id)->count();
        $all_projects_count = $my_projects->count();
        $completed_projects = $my_projects->where('is_completed', 1)
            // ->where('created_at', 'LIKE', '%' . $year . '%')
            ->count();

        $in_progress = $all_projects_count - $completed_projects;
        $all_projects = $my_projects;
        // foreach ($all_projects as $project) {
        //     $project->watchProjectProgress($project);
        // }
        return response()->json(compact('all_projects', 'all_projects_count', 'completed_projects', 'in_progress'), 200);
    }
}
