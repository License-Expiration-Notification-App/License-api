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
        // $year = date('Y', strtotime('now'));
        $client_id = '9bf54f1b-ddbb-4641-a21a-058e667acf0d'; // $this->getClient()->client_id;
        $total_subsidiaries = Subsidiary::where('client_id', $client_id)->count();
        $total_licenses = License::where('client_id', $client_id)->count();
        
        $pending_activities = LicenseActivity::where('client_id', $client_id)
        ->where('status', 'Pending')        
        ->get()->groupBy('title')->select(\DB::raw('COUNT(*) as total'));
        
        return response()->json(compact('total_subsidiaries', 'total_licenses', 'pending_activities'), 200);
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
