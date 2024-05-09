<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
        $clientQuery->join('subsidiaries', 'subsidiaries.client_id', '=', 'clients.id')
        ->join('licenses', 'licenses.client_id', '=', 'clients.id');
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $keyword = Arr::get($searchParams, 'search', '');
        $status = Arr::get($searchParams, 'status', '');
        $date_created = Arr::get($searchParams, 'date_created', '');
        $sort_by = Arr::get($searchParams, 'sort_by', 'name');
        $sort_direction = Arr::get($searchParams, 'sort_direction', 'ASC');
        if (!empty($keyword)) {
            $clientQuery->where(function ($q) use ($keyword) {
                $q->where('company_name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('company_email', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (!empty($date_created)) {
            $clientQuery->where('created_at',  'LIKE', '%' . date('Y-m-d',strtotime($date_created)) . '%');
        }
        if (!empty($status)) {
            $clientQuery->where('status',  $status);
        }

        $clients =  $clientQuery->withCount('subsidiaries', 'licenses')->where($condition)->orderBy($sort_by, $sort_direction)->paginate($limit);
        return response()->json(compact('clients'), 200);
    }
    public function fetchAllClients()
    {
        $clients = Client::get();
        return response()->json(compact('clients'), 200);
    }
    public function show(Client $client)
    {
        $client = $client->with('users','subsidiaries', 'licenses')->find($client->id);
        $users = $client->users;
        foreach ($users as $user) {
            $user->is_client_main_admin = false;
            if ($user->id == $client->main_admin) {
                $user->is_client_main_admin = true;
            }
        }
        $client->users = $users;
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
        $name = $request->company_name;
        $client = Client::where('company_name', $name)->first();
        if (!$client) {
            $client = DB::transaction(function () use ($request, $name){
                
                $actor = $this->getUser();
                $new_client = new Client();
                $new_client->company_name = $name;
                $new_client->company_email = $request->company_email;
                // $client->phone = $request->phone;
                $new_client->description = $request->description;
                if ($new_client->save()) {
                    $new_client->logo = env('APP_URL').'/'.$new_client->logo_path;
                    $new_client->save();
                    $request['client_id'] = $new_client->id;

                    $this->registerClientUser($request);
                    $title = "New Client Registered";
                    //log this event
                    $description = "$new_client->company_name was registered by $actor->name";
                    $this->auditTrailEvent($title, $description, [$actor]);


                    return $new_client;
                    // response()->json(compact('client'), 200);
                }
                
            });
            // return response()->json(['message' => 'Unable to register'], 500);
        }
        return $this->show($client);
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
        $actor = $this->getUser();
        $old_name = $client->company_name;
        $old_email = $client->company_email;

        $client->company_name = $request->company_name;
        $client->company_email = $request->company_email;
        $client->description = $request->description;
        $client->save();
        //log this event
        $title = "Client Details Updated";                    
        $description = "($old_name, $old_email) were modified to ($client->company_name, $client->company_email) by $actor->name";
        $this->auditTrailEvent($title, $description, [$actor]);

        return response()->json(compact('client'), 200);
    }
    public function registerClientUser(Request $request)
    {
        $actor = $this->getUser();
        $request->validate([
            'client_id' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|string',
        ]);
        $request['role'] = 'client';
        $client = Client::find($request->client_id);
        $user_obj = new User();
        $response = $user_obj->createUser($request);
        if ($response['message'] == 'success') {
            $user = $response['user'];
            // make this user the main admin since this is the first user added
            if($client->main_admin == NULL) {
                $client->main_admin = $user->id;
                $client->save();
            }
            $client->users()->syncWithoutDetaching($user->id);

            $role = Role::where('name', 'client')->first();
            $user->roles()->sync($role->id);

            // log this event
            $title = "Client Admin Registered";                    
            $description = "$user->name was registered as an admin for $client->company_name by $actor->name";
            $this->auditTrailEvent($title, $description, [$actor]);
                
            return response()->json('success', 200);
        }
        return response()->json(['error' => $response['message']]);
    }
    public function updateClientUser(Request $request, User $user)
    {
        $actor = $this->getUser();
        $old_name = $user->name;
        $old_email = $user->email;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        $title = "Client Admin Updated";
        $description = "($old_name, $old_email) were modified to ($user->name, $user->email) by $actor->name";
        $this->auditTrailEvent($title, $description, [$actor]);
        // $client->users()->sync($user->id);
        // $role = Role::where('name', 'client')->first();
        // $user->roles()->sync($role->id); // role id 3 is client

    }
    public function makeClientUserMainAdmin(Request $request, Client $client)
    {
        if ($client) {
            $client->main_admin = $request->user_id;
            $client->save();
            
            return 'success';
        }
        return response()->json(['message' => 'Client does not exist'], 500);
    }
    public function deleteClientUser(Request $request, User $user)
    {
        if ($user) {
            $actor = $this->getUser();            
            $user->clients()->sync([]);
            $title = "Client User Deletion";
            //log this event
            $description = "$user->name was deleted by $actor->name";
            $this->auditTrailEvent($title, $description, [$actor]);
            $user->delete();
            return 'success';
        }
        return response()->json(['message' => 'User does not exist'], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Client  $client
     * @return \Illuminate\Http\Response
     */
    public function toggleClientStatus(Request $request, Client $client)
    {
        if ($client) {
            $actor = $this->getUser();     
            $value = $request->value; // 'Active' or 'Inactive'
            $client->status = $value;
            $client->save();
            $title = "Client Status Changed";
            $description = "$client->company_name status was changed to $client->status by $actor->name";
            $this->auditTrailEvent($title, $description, [$actor]);
            return response()->json('success');
        }
        return response()->json(['message' => 'Client does not exist'], 500);
    }

    public function uploadClientLogo(Request $request)
    {
        $this->validate($request, [
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:1024',
        ]);
        $client_id = $request->client_id;
        $client = Client::find($client_id);
        if ($client) {
            if ($request->file('logo') != null && $request->file('logo')->isValid()) {
                
                if ($client->logo_path !== 'storage/client-logo/default.jpeg') {

                    Storage::disk('public')->delete(str_replace('storage/', '', $client->logo_path));
                }

                $name = 'client_logo'.$client_id.'_'.$request->file('logo')->hashName();
                // $file_name = $name . "." . $request->file('file_uploaded')->extension();
                $link = $request->file('logo')->storeAs('client-logo', $name, 'public');

                $client->logo_path = 'storage/'.$link;
                $client->logo = env('APP_URL').'/'.$client->logo_path;
                $client->save();
                return $this->show($client);
            }
            return response()->json(['message' => 'Please provide a valid image. Image types should be: jpeg, jpg and png. It must not be more than 1MB in size'], 500);
        }
        return response()->json(['message' => 'Client does not exist'], 500);
    }
}
