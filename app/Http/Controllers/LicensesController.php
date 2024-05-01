<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\License;
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
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $license_no = Arr::get($searchParams, 'license_no', '');
        if (!empty($license_no)) {
            $licenseQuery->where('license_no',  $license_no);
        }

        $licenses =  $licenseQuery->with('client', 'subsidiary', 'licenseType', 'state', 'lga')->where($condition)->paginate($limit);
        return response()->json(compact('licenses'), 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'license_no' => 'required|string|unique:licenses'
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
            $license->renewed_date = date('Y-m-d', strtotime($request->renewed_date));
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
        $license = $license->with('client', 'subsidiary', 'licenseType', 'mineral', 'state', 'lga', 'createdBy')->find($license->id);
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
        $license->save();

        return $this->show($license);
    }
    public function uploadCertificate(Request $request)
    {
        $license_id = $request->license_id;
        $license = License::find($license_id);
        if ($request->file('certificate') != null && $request->file('certificate')->isValid()) {
            // remove previous upload
            Storage::disk('public')->delete(str_replace('storage/', '', $license->certificate));
            // upload new
            $name = 'cert_'.time().'_'.$request->file('certificate')->hashName();
            $link = $request->file('certificate')->storeAs('certificate', $name, 'public');

            $license->certificate = 'storage/'.$link;
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
}
