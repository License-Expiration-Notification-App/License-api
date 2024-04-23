<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Arr;

class ClientsController extends Controller
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
        $condition = [];
        if ($user->hasRole('client')) {
            $id = $this->getClient()->id;
            $condition = ['id' => $id];
        }
        $searchParams = $request->all();
        $clientQuery = Client::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'keyword', '');
        $status = Arr::get($searchParams, 'status', '');
        if (!empty($keyword)) {
            $clientQuery->where(function ($q) use ($keyword) {
                $q->where('company_name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('company_email', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('description', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (!empty($status)) {
            $clientQuery->where('status',  $status);
        }

        $clients =  $clientQuery->with('subsidiaries', 'licenses')->where($condition)->paginate($limit);
        return response()->json(compact('clients'), 200);
    }
    public function show(Client $client)
    {
        $client = $client->with('users','subsidiaries', 'licenses')->find($client->id);
        return response()->json(compact('client'), 200);
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
            'company_name' => 'required|string',
            'company_email' => 'required|string|unique:clients'
        ]);
        $actor = $this->getUser();
        $name = $request->company_name;
        $client = Client::where('company_name', $name)->first();
        if (!$client) {
            $client = new Client();
            $client->company_name = $name;
            $client->company_email = $request->company_email;
            // $client->phone = $request->phone;
            $client->description = $request->description;
            if ($client->save()) {
                $request['client_id'] = $client->id;

                $this->registerClientUser($request);
                $title = "New Client Registered";
                //log this event
                $description = "$client->name was registered by $actor->name";
                $this->auditTrailEvent($title, $description, [$actor]);


                return $this->show($client);
                // response()->json(compact('client'), 200);
            }
            return response()->json(['message' => 'Unable to register'], 500);
        }
        return response()->json(['message' => 'Company already exists'], 401);
    }
    public function registerClientUser(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer',
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
        ]);
        $request['role'] = 'client';
        $client = Client::find($request->client_id);
        $user_obj = new User();
        $response = $user_obj->createUser($request);
        if ($response['message'] == 'success') {
            $user = $response['user'];
            $client->users()->syncWithoutDetaching($user->id);

            $role = Role::where('name', 'client')->first();
            $user->roles()->sync($role->id);
            return response()->json('success', 200);
        }
        return response()->json(['error' => $response['message']]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Client $client)
    {

        $client->company_name = $request->company_name;
        $client->company_email = $request->company_email;
        $client->description = $request->description;
        $client->save();
        return response()->json(compact('client'), 200);
    }
    // public function updateClientUser(Request $request, User $user)
    // {

    //     $user->name = $request->name;
    //     $user->email = $request->email;
    //     $user->save();

    //     // $client->users()->sync($user->id);
    //     // $role = Role::where('name', 'client')->first();
    //     // $user->roles()->sync($role->id); // role id 3 is client

    // }
    public function deleteClientUser(Request $request, User $user)
    {
        $actor = $this->getUser();
        $title = "Client User Deletion";
        //log this event
        $description = "$user->name was deleted by $actor->name";
        $this->auditTrailEvent($title, $description, [$actor]);
        $user->forceDelete();
        return 'success';
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function toggleClientStatus(Request $request, Client $client)
    {
        $value = $request->value; // 'Active' or 'Inactive'
        $client->status = $value;
        $client->save();
        return response()->json('success');
    }

    public function uploadClientLogo(Request $request)
    {
        $this->validate($request, [
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:1024',
        ]);
        $client_id = $request->client_id;
        $client = Client::find($client_id);
        if ($request->file('logo') != null && $request->file('logo')->isValid()) {

            $name = 'client_logo'.$client_id.'_'.$request->file('logo')->hashName();
            // $file_name = $name . "." . $request->file('file_uploaded')->extension();
            $link = $request->file('logo')->storeAs('client-logo', $name, 'public');

            $client->logo = $link;
            $client->save();
            return $this->show($client);
        }
        return response()->json(['message' => 'Please provide a valid image. Image types should be: jpeg, jpg and png. It must not be more than 1MB in size'], 500);
    }
}
