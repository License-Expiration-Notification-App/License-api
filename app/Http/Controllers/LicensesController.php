<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\License;
use App\Models\LicenseActivity;
use App\Models\LicenseType;
use App\Models\LocalGovernmentArea;
use App\Models\Mineral;
use App\Models\Renewal;
use App\Models\Report;
use App\Models\ReportUpload;
use App\Models\State;
use App\Models\Subsidiary;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
class LicensesController extends Controller
{
    const ITEM_PER_PAGE = 10;
    
    private function formatDate($date) 
    {
        $date = str_replace('TH', '', $date);
        $date = str_replace('1ST', '1', $date);
        $date = str_replace('2ND', '2', $date);
        $date = str_replace('3RD', '3', $date);
        $date = str_replace('.', ',', $date);
        $date = str_replace(',', '', $date);
        return $date;
    }
    
    private function missingHeaders($header)
    {
        $missing_headers = [];
        if(!in_array('MINERAL', $header)) {
            $missing_headers[] = 'A compulsory column header: MINERAL is missing. Please add a column header name titled: MINERAL to the csv file';
        }  
        if(!in_array('SUBSIDIARY NAME', $header)) {
            $missing_headers[] = 'A compulsory column header: SUBSIDIARY NAME is missing. Please add a column header name titled: SUBSIDIARY NAME to the csv file';
        }  
        if(!in_array('LICENSE NUMBER', $header)) {
            $missing_headers[] = 'A compulsory column header: LICENSE NUMBER is missing. Please add a column header name titled: LICENSE NUMBER to the csv file';
        }  
        if(!in_array('STATE', $header)) {
            $missing_headers[] = 'A compulsory column header: STATE is missing. Please add a column header name titled: STATE to the csv file';
        }  
        if(!in_array('LGA', $header)) {
            $missing_headers[] = 'A compulsory column header: LGA is missing. Please add a column header name titled: LGA to the csv file';
        }  
        if(!in_array('TENEMENT SIZE', $header)) {
            $missing_headers[] = 'A compulsory column header: TENEMENT SIZE is missing. Please add a column header name titled: TENEMENT SIZE to the csv file';
        }  
        if(!in_array('EXPIRY DATE', $header)) {
            $missing_headers[] = 'A compulsory column header: EXPIRY DATE is missing. Please add a column header name titled: EXPIRY DATE to the csv file';
        }  
        if(!in_array('ISSUE DATE', $header)) {
            $missing_headers[] = 'A compulsory column header: ISSUE DATE is missing. Please add a column header name titled: ISSUE DATE to the csv file';
        }  

        return $missing_headers;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $this->getUser();
        $searchParams = $request->all();
        $licenseQuery = License::query();
        $licenseQuery->join('clients', 'licenses.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->join('states', 'licenses.state_id', '=', 'states.id')
        ->join('local_government_areas', 'licenses.lga_id', '=', 'local_government_areas.id');

        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'search', '');
        // $license_no = Arr::get($searchParams, 'license_no', '');
        $license_type_id = Arr::get($searchParams, 'license_type_id', '');
        $client_id = Arr::get($searchParams, 'client_id', '');
        $subsidiary_id = Arr::get($searchParams, 'subsidiary_id', '');
        $mineral_id = Arr::get($searchParams, 'mineral_id', '');
        $state_id = Arr::get($searchParams, 'state_id', '');
        $lga_id = Arr::get($searchParams, 'lga_id', '');
        $status = Arr::get($searchParams, 'status', '');
        $license_date = Arr::get($searchParams, 'license_date', '');
        $license_date = Arr::get($searchParams, 'expiry_date', '');
        // $date_created = Arr::get($searchParams, 'date_created', '');
        $min_date = Arr::get($searchParams, 'min_date', '');
        $max_date = Arr::get($searchParams, 'max_date', '');
        $sort_by = Arr::get($searchParams, 'sort_by', 'license_no');
        $sort_direction = Arr::get($searchParams, 'sort_direction', 'ASC');
        if (!empty($keyword)) {
            $licenseQuery->where(function ($q) use ($keyword) {
                $q->where('licenses.license_no',  'LIKE', '%'.$keyword.'%')
                ->orWhereHas('client', function ($q) use ($keyword) {
                    $q->where('company_name', 'LIKE', '%' . $keyword . '%');
                })->orWhereHas('subsidiary', function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', '%' . $keyword . '%');
                })->orWhereHas('licenseType', function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', '%' . $keyword . '%');
                    $q->orWhere('slug', 'LIKE', '%' . $keyword . '%');
                })->orWhereHas('mineral', function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', '%' . $keyword . '%');
                })->orWhereHas('state', function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', '%' . $keyword . '%');
                })->orWhereHas('lga', function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', '%' . $keyword . '%');
                });
            });
          
            // $licenseQuery->where(function ($q) use ($keyword) {
            //     $q->where('name', 'LIKE', '%' . $keyword . '%');
            //     $q->orWhere('email', 'LIKE', '%' . $keyword . '%');
            //     $q->orWhere('phone', 'LIKE', '%' . $keyword . '%');
            //     $q->orWhere('address', 'LIKE', '%' . $keyword . '%');
            //     $q->orWhereIn('id', function ($query) use ($keyword) {
            //         $query->select('user_id')->from('customers');
            //         $query->where('type', 'LIKE', '%' . $keyword . '%');
            //         $query->orWhere('team', 'LIKE', '%' . $keyword . '%');
            //     });
            // });
        }
        
        if ($user->hasRole('client')) {
            $id = $this->getClient()->id;
            $licenseQuery->where('licenses.client_id',  $id);
        }else if (!empty($client_id)) {
            $licenseQuery->where('licenses.client_id',  $client_id);
        }
        if (!empty($subsidiary_id)) {
            $licenseQuery->where('licenses.subsidiary_id',  $subsidiary_id);
        }
        if (!empty($license_type_id)) {
            $licenseQuery->where('licenses.license_type_id',  $license_type_id);
        }
        if (!empty($mineral_id)) {
            $licenseQuery->where('licenses.mineral_id',  $mineral_id);
        }
        if (!empty($state_id)) {
            $licenseQuery->where('licenses.state_id',  $state_id);
        }
        if (!empty($lga_id)) {
            $licenseQuery->where('licenses.lga_id',  $lga_id);
        }
        if (!empty($status)) {
            $licenseQuery->where('licenses.status',  $status);
        }
        if (!empty($license_date)) {
            $licenseQuery->where('licenses.license_date',  'LIKE', '%' . date('Y-m-d',strtotime($license_date)) . '%');
        }
        if (!empty($expiry_date)) {
            $licenseQuery->where('licenses.expiry_date',  'LIKE', '%' . date('Y-m-d',strtotime($expiry_date)) . '%');
        }
        // if (!empty($date_created)) {
        //     $licenseQuery->where('licenses.created_at', 'LIKE', '%' . date('Y-m-d',strtotime($date_created)) . '%');
        // }
        if (!empty($min_date)) {
            $min_date = date('Y-m-d',strtotime($min_date)).' 00.00.00';
            $licenseQuery->where('licenses.expiry_date', '>=', $min_date);
        }
        if (!empty($max_date)) {
            $max_date = date('Y-m-d',strtotime($max_date)).' 23:59:59';
            $licenseQuery->where('licenses.expiry_date', '<=', $max_date);
        }
        if ($sort_by == '') {
            $sort_by = 'licenses.renewal_date';
        }
        if ($sort_direction == '') {
            $sort_direction = 'DESC';
        }

        $licenses =  $licenseQuery->select('licenses.*', 'clients.company_name as client','subsidiaries.name as subsidiary', 'license_types.name as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'states.name as state', 'local_government_areas.name as lga')->orderBy($sort_by, $sort_direction)->paginate($limit);


        // $licenses =  $licenseQuery->with('client', 'subsidiary', 'licenseType', 'mineral', 'state', 'lga')->where($condition)->paginate($limit);
        return response()->json(compact('licenses'), 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'license_no' => 'required|string|unique:licenses',
            'client_id' => 'required|string',
            'subsidiary_id' => 'required|string',
            'license_type_id' => 'required|string',
            'mineral_id' => 'required|string',
            'state_id' => 'required|integer',
            'lga_id' => 'required|integer',
            
        ]);
        $actor = $this->getUser();
        $license_no = $request->license_no;
        $license = License::where('license_no', $license_no)->first();
        if (!$license) {
            $license = new License();
            $license->client_id = $request->client_id;
            $license->subsidiary_id = $request->subsidiary_id;
            $license->license_no = $license_no;
            $license->license_type_id = $request->license_type_id;
            $license->mineral_id = $request->mineral_id;
            $license->state_id = $request->state_id;
            $license->lga_id = $request->lga_id;
            $license->license_date = date('Y-m-d', strtotime($request->license_date));

            $license->expiry_date = date('Y-m-d', strtotime('+3 years -1 day', strtotime($license->license_date)));
            
            $license->renewal_date = date("Y-m-d", strtotime("-3 month", strtotime($license->expiry_date)));

            $license->one_month_before_expiration = date("Y-m-d H:i:s", strtotime("-1 month", strtotime($license->renewal_date)));
            $license->two_weeks_before_expiration = date("Y-m-d H:i:s", strtotime("-2 weeks", strtotime($license->renewal_date)));
            $license->three_days_before_expiration = date("Y-m-d H:i:s", strtotime("-3 days", strtotime($license->renewal_date)));
            $license->size_of_tenement = $request->size_of_tenement;
            $today = date('Y-m-d', strtotime('now'));
            $license->status = 'Active';
            if ($license->expiry_date <= $today) {
                $license->status = 'Expired';
            }
            // $license->renewed_date = date('Y-m-d', strtotime($request->renewed_date));
            if ($request->file('certificate') != null && $request->file('certificate')->isValid()) {

                $name = 'cert_'.time().'_'.$request->file('certificate')->hashName();
                $link = $request->file('certificate')->storeAs('certificate', $name, 'public');
    
                $license->certificate = 'storage/'.$link;
            }
            $license->added_by = $actor->id;
            if ($license->save()) {
                $subsidiary = Subsidiary::with('client')->find($request->subsidiary_id);
                $title = "New License Added";

                //log this event
                $description = "New license ($license->license_no) was added for <strong>$subsidiary->name</strong> (". $subsidiary->client->name .") by <strong>$actor->name</strong>";
                $this->licenseEvent($title, $description, 'License Management', 'add', [$actor]);

                return $this->show($license);
                // response()->json(compact('client'), 200);
            }
            return response()->json(['message' => 'Unable to add license'], 500);
        }
        return response()->json(['message' => 'License Number already exists'], 401);
    }

    public function uploadBulkLicenses(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        
        $request->validate([
            'bulk_licenses_file' => 'required|mimes:csv,txt',
        ]);
        try {
            $actor = $this->getUser();
            $client_id = $request->client_id;
            $file = $request->file('bulk_licenses_file');
            $csvAsArray = array_map('str_getcsv', file($file));
            $header = array_shift($csvAsArray);
            $issues = $this->missingHeaders($header);
            if(count($issues) > 0) {
                return response()->json($issues, 500);
            }  
            $csv    = array();
            foreach($csvAsArray as $row) {
                $csv[] = array_combine($header, $row);
            }
            $unsaved_data = [];
            $saved_data = [];
            $line = 2;
            foreach($csv as $csvRow) {
                
                    $issues_observed = [];          
                    $company = trim($csvRow['SUBSIDIARY NAME']);                
                    $mineral = ucwords(trim($csvRow['MINERAL'])); 
                    $license_no = trim($csvRow['LICENSE NUMBER']);
                    $exp_date = strtoupper(trim($csvRow['EXPIRY DATE']));                
                    $lic_date = trim($csvRow['ISSUE DATE']);
                    $state = trim($csvRow['STATE']);
                    $lga = trim($csvRow['LGA']);                
                    $size_of_tenement = trim($csvRow['TENEMENT SIZE']);
                    //code...
                    if($company == NULL || $company == 'SUBSIDIARY NAME') {
                        // $unsaved_data['Invalid Row on row #'.$line] = $csvRow;                    
                        // $line++;
                        continue;
                    }
                    if($exp_date == 'LICENCE IN PROGRESS' || $exp_date == NULL) {
                        $issues_observed[] = 'Invalid EXPIRY DATE on row #'.$line;
                        $unsaved_data[] = 'Invalid EXPIRY DATE on row #'.$line;
                    }
                    if($lic_date == 'LICENCE IN PROGRESS' || $lic_date == NULL) {
                        $issues_observed[] = 'Invalid ISSUE DATE on row #'.$line;
                        $unsaved_data[] = 'Invalid ISSUE DATE on row #'.$line;
                    }
                    if($mineral == NULL) {
                        $issues_observed[] = 'MINERAL field should not be empty on row #'.$line;
                        $unsaved_data[] = 'MINERAL field should not be empty on row #'.$line;
                    }
                    if($license_no == NULL) {
                        $issues_observed[] = 'LICENSE NUMBER field should not be empty on row #'.$line;
                        $unsaved_data[] = 'LICENSE NUMBER field should not be empty on row #'.$line;
                    }
                    
                    // check for correct state spelling
                    $state_data = State::where('name', ucwords($state))->first();
                    if (!$state_data) {
                        $issues_observed[] = "Invalid State on row #$line. Please check the spelling of $state.";
                        $unsaved_data[] = "Invalid State on row #$line. Please check the spelling of $state.";
                    }
                    $lga_data = LocalGovernmentArea::where('name', ucwords($lga))->first();
                    if (!$lga_data) {
                        $issues_observed[] = "Invalid LGA on row #$line. Please check the spelling of $lga.";
                        $unsaved_data[] = "Invalid LGA on row #$line. Please check the spelling of $lga.";
                    }
                    // $license = License::where('license_no', $license_no)->first();
                    // if ($license) {
                    //     $issues_observed[] = "Duplicate License Number: $license_no on row #$line. This license number has been registered already";
                    //     $unsaved_data[] = "Duplicate License Number: $license_no on row #$line. This license number has been registered already";
                    // }

                    $licence_no_array = explode(' ',$license_no);
                    $license_type_slug = strtoupper(end($licence_no_array));
                    $status = trim($csvRow['STATUS']);
                    $expiry_date = date('Y-m-d', strtotime($this->formatDate($exp_date)));
                    $license_date = date('Y-m-d', strtotime($this->formatDate($lic_date)));
                    if(count($issues_observed) > 0) {
                        // $unsaved_data = $issues_observed;                    
                        $line++;
                        continue;
                    }

                    // all issues are picked out. Now let's populate the DB
                    // let's store the mineral incase it does not exist;
                    $db_mineral = Mineral::firstOrCreate(['name' => $mineral]);
                    // let's fetch the license type from the slug;
                    $license_type = LicenseType::where('slug', $license_type_slug)->first();
                    // create the subsidiary if it does not exist
                    $subsidiary = Subsidiary::firstOrCreate(['name' => $company, 'client_id' => $client_id]);

                    $license = License::where('license_no', $license_no)->first();
                    if (!$license) {
                        $license = new License();
                        $license->client_id = $client_id;
                        $license->subsidiary_id = $subsidiary->id;
                        $license->license_no = $license_no;
                        $license->license_type_id = $license_type->id;
                        $license->mineral_id = $db_mineral->id;
                        $license->state_id = $state_data->id;
                        $license->lga_id = $lga_data->id;
                        $license->license_date = $license_date;

                        $license->expiry_date = date('Y-m-d', strtotime('+3 years -1 day', strtotime($license->license_date)));
            
                        $license->renewal_date = date("Y-m-d", strtotime("-3 month", strtotime($license->expiry_date)));

                        $license->one_month_before_expiration = date("Y-m-d H:i:s", strtotime("-1 month", strtotime($license->renewal_date)));
                        $license->two_weeks_before_expiration = date("Y-m-d H:i:s", strtotime("-2 weeks", strtotime($license->renewal_date)));
                        $license->three_days_before_expiration = date("Y-m-d H:i:s", strtotime("-3 days", strtotime($license->renewal_date)));
                        // $license->expiry_date = $expiry_date;
                        // $license->one_month_before_expiration = date("Y-m-d H:i:s", strtotime("-1 month", strtotime($license->expiry_date)));
                        // $license->two_weeks_before_expiration = date("Y-m-d H:i:s", strtotime("-2 weeks", strtotime($license->expiry_date)));
                        // $license->three_days_before_expiration = date("Y-m-d H:i:s", strtotime("-3 days", strtotime($license->expiry_date)));
                        $license->size_of_tenement = $size_of_tenement;
                        $license->license_status = $status;
                        // $license->renewed_date = date('Y-m-d', strtotime($request->renewed_date));
                        $today = date('Y-m-d', strtotime('now'));
                        $license->status = 'Active';
                        if ($license->expiry_date <= $today) {
                            $license->status = 'Expired';
                        }
                        $license->added_by = $actor->id;
                        if ($license->save()) {
                            $saved_data[] = "License Number: $license_no on row #$line is successfully saved.";
                            $subsidiary = Subsidiary::with('client')->find($license->subsidiary_id);
                            $title = "New License Added";

                            //log this event
                            $description = "New license ($license->license_no) was added for <strong>$subsidiary->name</strong> (". $subsidiary->client->name .") by <strong>$actor->name</strong>";
                            $this->licenseEvent($title, $description, 'License Management', 'add', [$actor]);

                            // return $this->show($license);
                            // response()->json(compact('client'), 200);
                        }
                    }
                    $line++;
            }
            if(count($unsaved_data)) {
                return response()->json(['error' => $unsaved_data, 'saved_data' => $saved_data], 500);
            }
            
            ini_set('memory_limit', '128M');
            return 'success';
        
        } catch (\Throwable $th) {
            
            ini_set('memory_limit', '128M');
            return response()->json(['error' =>'Please upload a valid, non-empty .csv file'], 500);
        }
    }
    public function licenseActivityTimeLine(Request $request, License $license)
    {
        $searchParams = $request->all();
        $licenseActivityQuery = LicenseActivity::query();
        $submission_type = Arr::get($searchParams, 'submission_type', '');
        $status = Arr::get($searchParams, 'status', '');
        $min_date = Arr::get($searchParams, 'min_date', '');
        $max_date = Arr::get($searchParams, 'max_date', '');
        if (!empty($submission_type)) {
            $licenseActivityQuery->where('type', 'LIKE', '%'. $submission_type.'%');
        }
        if (!empty($status)) {
            $licenseActivityQuery->where('status', $status);
        }
        if (!empty($min_date)) {
            $min_date = date('Y-m-d',strtotime($min_date));//.' 00.00.00';
            $licenseActivityQuery->where('due_date', '>=', $min_date);
        }
        if (!empty($max_date)) {
            $max_date = date('Y-m-d',strtotime($max_date));//.' 23:59:59';
            $licenseActivityQuery->where('due_date', '<=', $max_date);
        }
        
       $activity_timeline = $licenseActivityQuery->where('license_id', $license->id)
       ->where('status', '!=', 'Pending')->select('license_id', 'title', 'description', 'created_at', 'status', 'type', 'color_code', 'due_date', 'uuid')->paginate(10);
       foreach($activity_timeline as $time_line) {
        $type = $time_line->type;
        if ($type == 'License Renewal') {
            $renewals = Renewal::where('license_id', $time_line->license_id)->select('link', 'status')->get();
            $time_line->uploads = $renewals;
        }
        if ($type == 'Annual Report' || $type == 'Quarterly Report') {
            $reports = Report::join('report_uploads', 'report_uploads.report_id', 'reports.id')
            ->where('reports.id', $time_line->uuid)->select('link', 'status')->get();
            $time_line->uploads = $reports;
        }
       }
        return response()->json(compact('activity_timeline'), 200);
    }
    public function licenseUpcomingActivities(Request $request, License $license)
    {
        $upcoming_activities = LicenseActivity::where('license_id', $license->id)
        ->where('status', 'Pending')->select('id', 'uuid', 'title', 'due_date', 'status', 'type', 'created_at')->paginate(10);
        return response()->json(compact('upcoming_activities'), 200);
    }
    /**
     * Display the specified resource.
     */
    public function show(License $license)
    {
        $license = $license->join('clients', 'licenses.client_id', '=', 'clients.id')
        ->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
        ->join('license_types', 'licenses.license_type_id', '=', 'license_types.id')
        ->join('minerals', 'licenses.mineral_id', '=', 'minerals.id')
        ->join('states', 'licenses.state_id', '=', 'states.id')
        ->join('local_government_areas', 'licenses.lga_id', '=', 'local_government_areas.id')
        ->select('licenses.*', 'clients.company_name as client', 'subsidiaries.name as subsidiary', 'license_types.name as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'states.name as state', 'local_government_areas.name as lga')
        ->find($license->id);
        return response()->json(compact('license'), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, License $license)
    {
        //

        $actor = $this->getUser();
        $license->client_id = $request->client_id;
        $license->subsidiary_id = $request->subsidiary_id; 
        $license->license_no = $request->license_no;
        $license->license_type_id = $request->license_type_id;
        $license->mineral_id = $request->mineral_id;
        $license->state_id = $request->state_id;
        $license->lga_id = $request->lga_id;
        $license->license_date = date('Y-m-d', strtotime($request->license_date));
        $license->expiry_date = date('Y-m-d', strtotime('+3 years -1 day', strtotime($license->license_date)));
            
        $license->renewal_date = date("Y-m-d", strtotime("-3 month", strtotime($license->expiry_date)));

        $license->one_month_before_expiration = date("Y-m-d H:i:s", strtotime("-1 month", strtotime($license->renewal_date)));
        $license->two_weeks_before_expiration = date("Y-m-d H:i:s", strtotime("-2 weeks", strtotime($license->renewal_date)));
        $license->three_days_before_expiration = date("Y-m-d H:i:s", strtotime("-3 days", strtotime($license->renewal_date)));

        // $license->expiry_date = date('Y-m-d', strtotime($request->expiry_date));
        $license->size_of_tenement = $request->size_of_tenement;
        $license->expiry_alert_sent = NULL;
        $today = date('Y-m-d', strtotime('now'));
        $license->status = 'Active';
        if ($license->expiry_date <= $today) {
            $license->status = 'Expired';
        }
        $license->save();
        $title = "License Updated";
        //log this event
        $description = "<strong>$actor->name</strong> updated <strong>($license->license_no)</strong>";
        $this->licenseEvent($title, $description, 'License Management', 'edit', [$actor]);
        return $this->show($license);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(License $license)
    {
        $actor = $this->getUser();
        //
        $title = "License Deleted";
        //log this event
        $description = "<strong>$actor->name</strong> removed <strong>($license->license_no)</strong>";
        $this->licenseEvent($title, $description, 'License Management', 'remove', [$actor]);
        $license->delete();
        return response()->json([], 204);
    }

    public function fetchLicenseTypes()
    {
        $license_types = LicenseType::get();
        return response()->json(compact('license_types'), 200);
    }
    public function fetchMinerals()
    {
        $minerals = Mineral::get();
        return response()->json(compact('minerals'), 200);
    }
    public function storeMineral(Request $request)
    {
        $mineral = Mineral::withTrashed()->where('name', $request->name)->first();
        if($mineral) {
            $mineral->restore();
        }else {

            Mineral::firstOrCreate(['name' => $request->name]);
        }
        return $this->fetchMinerals();
    }

    public function updateMineral(Request $request, Mineral $mineral)
    {
        $mineral->name = $request->name;
        $mineral->save();
        return response()->json(compact('mineral'), 200);
    }
    public function deleteMineral(Mineral $mineral)
    {
        $mineral->delete();
        return response()->json([], 204);
    }
    public function uploadCertificate(Request $request)
    {
        $actor = $this->getUser();
        $license_id = $request->license_id;
        $is_renewal = $request->is_renewal; // true or false
        $expiry_date = NULL;
        if(isset($request->expiry_date)) {
            
            $expiry_date = date('Y-m-d', strtotime($request->expiry_date));
        }
        $license = License::find($license_id);

        if($request->hasFile('certificate_file')){
            
            $files = $request->file('certificate_file');
            foreach ($files as $file) {
                $name = $file->getClientOriginalName().'_'.time();
                $file_name = $name . "." . $file->extension();
                // $name = 'cert_'.time().'_'.$request->file('certificate')->hashName();
                $link = $file->storeAs('certificate', $file_name, 'public');

                $renewal = new Renewal();
                $renewal->license_id  = $license_id;
                $renewal->link = env('APP_URL').'/storage/'.$link;
                $renewal->expiry_date = $expiry_date;
                $renewal->status = 'Submitted';
                $renewal->submitted_by = $actor->id;      
                $renewal->save();
            }
        }        

        if($is_renewal == true) {
            $actor = $this->getUser();
            $license->expiry_date = $expiry_date;
            $license->save();
            // then since we are renewing, we need to log the activity
            LicenseActivity::firstOrCreate(
                [
                    'license_id' => $license_id,
                    'client_id' => $license->client_id,
                    'uuid' => $license_id,
                    'title' => '<strong>License Renewal</strong>',
                    'due_date' => $license->expiry_date,
                    
                ],
                ['status' => 'Submitted', 'description' => "submitted for approval by <strong>$actor->name</strong>", 'color_code' => '#475467', 'type' =>'License Renewal']
            );
        }
        return 'success';
        // if ($request->file('certificate') != null && $request->file('certificate')->isValid()) {
        //     // remove previous upload
        //     // if($license->certificate_link != NULL) {

        //     //     Storage::disk('public')->delete(str_replace(env('APP_URL').'/storage/', '', $license->certificate_link));
        //     // }
        //     // upload new
        // }
        // return $this->show($license);
    }

    public function uploadReport(Request $request)
    {
        $actor = $this->getUser();
        $report_id = $request->uuid;
        if($report_id != NULL) {
            $entry_date = date('Y-m-d', strtotime('now'));
            $report = Report::find($report_id);

            $report->entry_date = $entry_date;
            $report->status = 'Submitted';
            $report->submitted_by = $actor->id;            
            $report->save();
            if($request->hasFile('report_file')){
                
                $files = $request->file('report_file');
                foreach ($files as $file) {
                    $name = $file->getClientOriginalName().'_'.time();
                    $file_name = $name . "." . $file->extension();
                    // $name = 'cert_'.time().'_'.$request->file('report_file')->hashName();
                    $link = $file->storeAs('report', $file_name, 'public');

                    $upload = new ReportUpload();
                    $upload->report_id  = $report_id;
                    $upload->link = env('APP_URL').'/storage/'.$link;
                    $upload->save();
                }
            }

            // log the activity
            LicenseActivity::firstOrCreate(
                [
                    'uuid' => $report->id,
                    'client_id' => $report->client_id,
                    'license_id' => $report->license_id,
                    'title' => 'strong>'.$report->report_type.' Report</strong>',
                    'due_date' => $report->due_date,
                ],
                ['status' => 'Submitted', 'description' => "submitted for approval by <strong>$actor->name</strong>", 'color_code' => '#475467', 'type' =>'Report Status']
            );
        }
        return 'success';
    }
    public function approveReport(Request $request, Report $report)
    {
        $actor = $this->getUser();
        $report->status = 'Approved';
        $report->approved_by = $actor->id;            
        $report->save();

        LicenseActivity::firstOrCreate(
            [
                'uuid' => $report->id,
                'client_id' => $report->client_id,
                'license_id' => $report->license_id,
                'title' => 'strong>'.$report->report_type.' Report</strong>',
                'status' => 'Approved',
                'due_date' => $report->due_date,
            ],
            ['description' => "approved by <strong>$actor->name</strong>", 'color_code' => '#D1FADF', 'type' =>'Report Status']
        );
        return 'success';
    }
    public function rejectReport(Request $request, Report $report)
    {
        $actor = $this->getUser();
        $report->status = 'Rejected';
        $report->rejected_by = $actor->id;            
        $report->save();

        LicenseActivity::firstOrCreate(
            [
                'uuid' => $report->id,
                'client_id' => $report->client_id,
                'license_id' => $report->license_id,
                'title' => 'strong>'.$report->report_type.' Report</strong>',
                'status' => 'Rejected',
                'due_date' => $report->due_date,
            ],
            ['description' => "rejected by <strong>$actor->name</strong>", 'color_code' => '#B42318', 'type' =>'Report Status']
        );
        return 'success';
    }

    public function approveLicenseRenewal(Request $request, License $license)
    {
        $actor = $this->getUser();
        
        $renewals = Renewal::where('license_id', $license->id)->get();
        foreach ($renewals as $renewal) {
            $renewal->status = 'Approved';
            $renewal->approved_by = $actor->id;            
            $renewal->save();
        }
        LicenseActivity::firstOrCreate(
            [
                'uuid' => $license->id,
                'client_id' => $license->client_id,
                'license_id' => $license->id,
                'title' => '<strong>License Renewal</strong>',
                'status' => 'Approved',
                'due_date' => $license->expiry_date,
            ],
            ['status' => 'Approved', 'description' => "approved by <strong>$actor->name</strong>", 'color_code' => '#D1FADF', 'type' =>'License Renewal']
        );
        return 'success';
    }
    public function rejectLicenseRenewal(Request $request, License $license)
    {
        $actor = $this->getUser();
        
        $renewals = Renewal::where('license_id', $license->id)->get();
        foreach ($renewals as $renewal) {
            $renewal->status = 'Rejected';
            $renewal->approved_by = $actor->id;            
            $renewal->save();
        }
        LicenseActivity::firstOrCreate(
            [
                'uuid' => $license->id,
                'client_id' => $license->client_id,
                'license_id' => $license->id,
                'title' => '<strong>License Renewal</strong>',
                'status' => 'Rejected',
                'due_date' => $license->expiry_date,
            ],
            ['status' => 'Rejected', 'description' => "rejected by <strong>$actor->name</strong>", 'color_code' => '#B42318', 'type' =>'License Renewal']
        );
        return 'success';
    }
}
