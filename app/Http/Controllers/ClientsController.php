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
        if ($user->hasRole('client') === 'client') {
            $id = $this->getClient()->id;
            $condition = ['id' => $id];
        }
        $searchParams = $request->all();
        $customerQuery = Client::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'keyword', '');
        $status = Arr::get($searchParams, 'status', '');
        if (!empty($keyword)) {
            $customerQuery->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('email', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('description', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (!empty($status)) {
            $customerQuery->where('status',  $status);
        }

        return $customerQuery->where($condition)->paginate($limit);
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
            'email' => 'required|string|unique:clients',
            'admin_name' => 'required|string',
            'admin_email' => 'required|string|unique:users',
        ]);
        $actor = $this->getUser();
        $name = $request->name;
        $client = Client::where('name', $name)->first();
        if (!$client) {
            $client = new Client();
            $client->name = $name;
            $client->email = $request->email;
            // $client->phone = $request->phone;
            $client->description = $request->contact_address;
            if ($client->save()) {
                $request->client_id = $client->id;
                $request->name = $request->admin_name;
                $request->email = $request->admin_email;
                $request->role = 'client';
                $this->registerClientUser($request);
                $title = "New Client Registered";
                //log this event
                $description = "$client->name was registered by $actor->name";
                $this->auditTrailEvent($title, $description, $actor);


                return response()->json(compact('client'), 200);
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
        $client = Client::find($request->client_id);
        $user_obj = new User();
        $response = $user_obj->createUser($request);
        if ($response['message'] == 'success') {
            $user = $response['user'];
            $client->users()->sync($user->id);

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
        //
        $client->name = $request->name;
        $client->email = $request->email;
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
        $this->auditTrailEvent($title, $description);
        $user->forceDelete();
        return response()->json([], 204);
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
        return response()->json(compact('client'), 200);
    }
}
