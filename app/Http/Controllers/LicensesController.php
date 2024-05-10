<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Mineral;
use App\Models\Subsidiary;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
class LicensesController extends Controller
{
    const ITEM_PER_PAGE = 10;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $this->getUser();
        $condition = [];
        if ($user->hasRole('client')) {
            $id = $this->getClient()->id;
            $condition = ['client_id' => $id];
        }
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
        $subsidiary_id = Arr::get($searchParams, 'subsidiary_id', '');
        $mineral_id = Arr::get($searchParams, 'mineral_id', '');
        $state_id = Arr::get($searchParams, 'state_id', '');
        $lga_id = Arr::get($searchParams, 'lga_id', '');
        $status = Arr::get($searchParams, 'status', '');
        $license_date = Arr::get($searchParams, 'license_date', '');
        $date_created = Arr::get($searchParams, 'date_created', '');
        $sort_by = Arr::get($searchParams, 'sort_by', 'license_no');
        $sort_direction = Arr::get($searchParams, 'sort_direction', 'ASC');
        if (!empty($keyword)) {
            $licenseQuery->where('license_no',  $keyword);
        }
        if (!empty($subsidiary_id)) {
            $licenseQuery->where('subsidiary_id',  $subsidiary_id);
        }
        if (!empty($license_type_id)) {
            $licenseQuery->where('license_type_id',  $license_type_id);
        }
        if (!empty($mineral_id)) {
            $licenseQuery->where('mineral_id',  $mineral_id);
        }
        if (!empty($state_id)) {
            $licenseQuery->where('state_id',  $state_id);
        }
        if (!empty($lga_id)) {
            $licenseQuery->where('lga_id',  $lga_id);
        }
        if (!empty($status)) {
            $licenseQuery->where('status',  $status);
        }
        if (!empty($license_date)) {
            $licenseQuery->where('license_date',  'LIKE', '%' . date('Y-m-d',strtotime($license_date)) . '%');
        }
        if (!empty($date_created)) {
            $licenseQuery->where('created_at', 'LIKE', '%' . date('Y-m-d',strtotime($date_created)) . '%');
        }
        if ($sort_by == '') {
            $sort_by = 'license_no';
        }
        if ($sort_direction == '') {
            $sort_direction = 'ASC';
        }

        $licenses =  $licenseQuery->select('licenses.*', 'clients.company_name as client','subsidiaries.name as subsidiary', 'license_types.name as license_type', 'license_types.slug as license_type_slug', 'minerals.name as mineral', 'states.name as state', 'local_government_areas.name as lga')->where($condition)->orderBy($sort_by, $sort_direction)->paginate($limit);


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
            $license->expiry_date = date('Y-m-d', strtotime($request->expiry_date));
            $license->one_month_before_expiration = date("Y-m-d H:i:s", strtotime("-1 month", strtotime($request->expiry_date)));
            $license->two_weeks_before_expiration = date("Y-m-d H:i:s", strtotime("-2 weeks", strtotime($request->expiry_date)));
            $license->three_days_before_expiration = date("Y-m-d H:i:s", strtotime("-3 days", strtotime($request->expiry_date)));
            $license->size_of_tenement = $request->size_of_tenement;
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
                $description = "New license was added for $subsidiary->name under " .$subsidiary->client->name . ". by $actor->name";
                $this->auditTrailEvent($title, $description, [$actor]);


                return $this->show($license);
                // response()->json(compact('client'), 200);
            }
            return response()->json(['message' => 'Unable to add license'], 500);
        }
        return response()->json(['message' => 'License Number already exists'], 401);
    }

    /**
     * Display the specified resource.
     */
    public function show(License $license)
    {
        $license = $license->join('subsidiaries', 'licenses.subsidiary_id', '=', 'subsidiaries.id')
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
        $license->client_id = $request->client_id;
        $license->subsidiary_id = $request->subsidiary_id; 
        $license->license_no = $request->license_no;
        $license->license_type_id = $request->license_type_id;
        $license->mineral_id = $request->mineral_id;
        $license->state_id = $request->state_id;
        $license->lga_id = $request->lga_id;
        $license->license_date = date('Y-m-d', strtotime($request->license_date));
        $license->expiry_date = date('Y-m-d', strtotime($request->expiry_date));
        $license->size_of_tenement = $request->size_of_tenement;
        $license->save();

        return $this->show($license);
    }
    public function uploadCertificate(Request $request)
    {
        $license_id = $request->license_id;
        $license = License::find($license_id);
        if ($request->file('certificate') != null && $request->file('certificate')->isValid()) {
            // remove previous upload
            if($license->certificate_link != NULL) {

                Storage::disk('public')->delete(str_replace(env('APP_URL').'/storage/', '', $license->certificate_link));
            }
            // upload new
            $name = 'cert_'.time().'_'.$request->file('certificate')->hashName();
            $link = $request->file('certificate')->storeAs('certificate', $name, 'public');

            $license->certificate_link = env('APP_URL').'/storage/'.$link;
            $license->save();
        }
        return $this->show($license);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(License $license)
    {
        //
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
}
