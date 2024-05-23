<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Subsidiary;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SubsidiariesController extends Controller
{

    const ITEM_PER_PAGE = 10;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $this->getUser();
        $searchParams = $request->all();
        $subsidiaryQuery = Subsidiary::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'keyword', '');
        $status = Arr::get($searchParams, 'status', '');
        $client_id = Arr::get($searchParams, 'client_id', '');
        if ($user->hasRole('client')) {
            $id = $this->getClient()->id;
            $subsidiaryQuery->where('client_id',  $id);
        }else if (!empty($client_id)) {
            $subsidiaryQuery->where('client_id',  $client_id);
        }
        if (!empty($keyword)) {
            $subsidiaryQuery->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (!empty($status)) {
            $subsidiaryQuery->where('status',  $status);
        }

        $subsidiaries =  $subsidiaryQuery->withCount('licenses')->paginate($limit);
        return response()->json(compact('subsidiaries'), 200);
    }

    public function fetchClientSubsidiaries(Request $request)
    {
        $subsidiaries = Subsidiary::withCount('licenses')->where('client_id', $request->client_id)->paginate(10);
        return response()->json(compact('subsidiaries'), 200);
    }
    public function show(Subsidiary $subsidiary)
    {
        $subsidiary = $subsidiary/*->with('client', 'licenses')*/->find($subsidiary->id);
        return response()->json(compact('subsidiary'), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'client_id' => 'required|string',
        ]);
        $actor = $this->getUser();
        $name = $request->name;
        $client_id = $request->client_id;        
        $subsidiary = Subsidiary::firstOrCreate(['name' => $name, 'client_id' => $client_id]);
        $client = Client::find($client_id);
        $title = "New Subsidiary Registered";
        //log this event
        $description = "<strong>$subsidiary->name</strong> was registered under $client->company_name by $actor->name";
        $this->auditTrailEvent($title, $description, 'Subsidiary Management', 'add', [$actor]);


        return response()->json(compact('subsidiary'), 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Subsidiary  $Subsidiary
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Subsidiary $subsidiary)
    {
        $actor = $this->getUser();
        $old_name = $subsidiary->name;
        $subsidiary->name = $request->name;
        $subsidiary->save();
        $title = "Subsidiary Updated";
                //log this event
                $description = "<strong>$old_name</strong> was updated to <strong>$subsidiary->name</strong> by <strong>$actor->name</strong>";
                $this->auditTrailEvent($title, $description, 'Subsidiary Management', 'add', [$actor]);

        return $this->show($subsidiary);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Subsidiary  $Subsidiary
     * @return \Illuminate\Http\Response
     */
    public function toggleSubsidiaryStatus(Request $request, Subsidiary $subsidiary)
    {
        $value = $request->value; // 'Active' or 'Inactive'
        $subsidiary->status = $value;
        $subsidiary->save();
        return $this->show($subsidiary);
    }
}
