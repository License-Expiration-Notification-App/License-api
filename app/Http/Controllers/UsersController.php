<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\LicenseActivity;
use App\Models\User;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
class UsersController extends Controller
{
    
    public function index(Request $request)
    {
        $searchParams = $request->all();
        $userQuery = User::query();
        $keyword = Arr::get($searchParams, 'search', '');
        $status = Arr::get($searchParams, 'status', '');
        // $date_created = Arr::get($searchParams, 'date_created', '');
        $min_date = Arr::get($searchParams, 'min_date', '');
        $max_date = Arr::get($searchParams, 'max_date', '');
        $sort_by = Arr::get($searchParams, 'sort_by', 'name');
        $sort_direction = Arr::get($searchParams, 'sort_direction', 'ASC');

        if (!empty($keyword)) {
            $userQuery->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('email', 'LIKE', '%' . $keyword . '%');
            });
        }
        // if (!empty($date_created)) {
        //     $userQuery->where('created_at',  'LIKE', '%' . date('Y-m-d',strtotime($date_created)) . '%');
        // }
        if (!empty($min_date)) {
            $min_date = date('Y-m-d',strtotime($min_date)).' 00.00.00';
            $userQuery->where('created_at', '>=', $min_date);
        }
        if (!empty($max_date)) {
            $max_date = date('Y-m-d',strtotime($max_date)).' 23:59:59';
            $userQuery->where('created_at', '<=', $max_date);
        }
        if (!empty($status)) {
            $userQuery->where('status',  $status);
        }
        if ($sort_by == '') {
            $sort_by = 'created_at';
        }
        if ($sort_direction == '') {
            $sort_direction = 'DESC';
        }
        $users = $userQuery->where('role', 'staff')->orderBy($sort_by, $sort_direction)->paginate(10);
        return response()->json(compact('users'), 200);
    }
    public function auditTrail(Request $request)
    {
        $user = $this->getUser();
        $searchParams = $request->all();
        $types = Arr::get($searchParams, 'types', '');
        $min_date = Arr::get($searchParams, 'min_date', '');
        $max_date = Arr::get($searchParams, 'max_date', '');
        
        $notificationQuery = $user->notifications(); //->where('data', 'LIKE', '%Audit Trail%');

        if (!empty($types)) {
            $types_array = explode(',', $types);
            $notificationQuery->whereIn('type', $types_array);
        }
        if (!empty($min_date)) {
            $min_date = date('Y-m-d',strtotime($min_date)).' 00.00.00';
            $notificationQuery->where('created_at', '>=', $min_date);
        }
        if (!empty($max_date)) {
            $max_date = date('Y-m-d',strtotime($max_date)).' 23:59:59';
            $notificationQuery->where('created_at', '<=', $max_date);
        }
        $notifications = $notificationQuery->orderBy('created_at', 'DESC')->paginate(100);
        foreach ($notifications as $notification) {
            if ($notification->type != 'Authentication') {
                $data = $notification->data;
                $description = $data['description'];
                $actor = $data['actor'];
    
                if($actor == $user->id) {
                    $description .= '<strong>you</strong>';
                }else {
                    $actor_name = User::withTrashed()->find($actor)->name;
                    $description .= "<strong>$actor_name</strong>";
                }
                $data['description'] = $description;
                $notification->data = $data;
            }
            
        }
        $notifications = $notifications->setCollection($notifications->groupBy(['created_at' =>function($item){
            return Carbon::parse($item->created_at)->format('Y-m-d');
        }, 'type']));
        
        $unread_notifications = $user->unreadNotifications()->count();
        return response()->json(compact('notifications', 'unread_notifications'), 200);
    }
    public function licenseNotificationsOld(Request $request)
    {
        $searchParams = $request->all();
        $info_type = Arr::get($searchParams, 'info_type', '');
        $min_date = Arr::get($searchParams, 'min_date', '');
        $max_date = Arr::get($searchParams, 'max_date', '');
        $user = $this->getUser();
        $notificationQuery = $user->notifications()->where('data', 'NOT LIKE', '%Audit Trail%');
        
        if (!empty($info_type)) {
            $notificationQuery->where('data', 'LIKE', '%'.$info_type.'%');
        }
        if (!empty($min_date)) {
            $min_date = date('Y-m-d',strtotime($min_date)).' 00.00.00';
            $notificationQuery->where('created_at', '>=', $min_date);
        }
        if (!empty($max_date)) {
            $max_date = date('Y-m-d',strtotime($max_date)).' 23:59:59';
            $notificationQuery->where('created_at', '<=', $max_date);
        }
        $notifications = $notificationQuery->orderBy('created_at', 'DESC')->select('data as content')->paginate(10);
        // $unread_notifications = $user->unreadNotifications()->where('data', 'LIKE', '%'.$license_no.'%')->count();
        return response()->json(compact('notifications'), 200);
    }
    public function licenseNotifications(Request $request)
    {
        $user = $this->getUser();
        $searchParams = $request->all();
        $info_type = Arr::get($searchParams, 'info_type', '');
        $min_date = Arr::get($searchParams, 'min_date', '');
        $max_date = Arr::get($searchParams, 'max_date', '');
        $user = $this->getUser();
        // $notificationQuery = $user->notifications()->where('data', 'NOT LIKE', '%Audit Trail%');
        $notificationQuery = LicenseActivity::query();
        if (!empty($info_type)) {
            $notificationQuery->where('type', 'LIKE', '%'.$info_type.'%');
        }
        if (!empty($min_date)) {
            $min_date = date('Y-m-d',strtotime($min_date));//.' 00.00.00';
            $notificationQuery->where('due_date', '>=', $min_date);
        }
        if (!empty($max_date)) {
            $max_date = date('Y-m-d',strtotime($max_date));//.' 23:59:59';
            $notificationQuery->where('due_date', '<=', $max_date);
        }
        $condition = [];
        if ($user->role == 'client') {
            $client_id = $this->getClient()->id;
            $condition = ['client_id' => $client_id];
        }
        $notifications = $notificationQuery->where($condition)->orderBy('created_at', 'DESC')->select('id as notification_id', 'title', 'description', 'color_code', 'license_id as uuid', 'type', 'status', 'created_at', 'read_by', 'action_by')->paginate(10);
        foreach ($notifications as $notification) {
            $action_by = $notification->action_by;
            if ($user->id == $action_by) {
                $actor_name = "<strong>you</strong>";
            }else {
                $action_by = User::find($notification->action_by);
                $actor_name = ($action_by) ? "<strong>$action_by->name</strong>" : "";
            }     
            $notification->description .= $actor_name;
            $read_by = $notification->read_by;
            $readers_array = explode(',', $read_by);
            $notification->is_read = 0;
            if (in_array($user->id, $readers_array)) {
                $notification->is_read = 1;
            }
            unset($notification->read_by);
        }
        $unread_notifications = LicenseActivity::where($condition)->where('read_by', 'NOT LIKE', '%'.$user->id.'%')->orWhere('read_by', NULL)->count();
        return response()->json(compact('notifications', 'unread_notifications'), 200);
    }
    public function markNotificationAsRead(Request $request,LicenseActivity $notification)
    {
        $user = $this->getUser();
        $read_by = $notification->read_by;
        $readers_array = explode(',', $read_by);
        if (!in_array($user->id, $readers_array)) {
            $readers_array[] = $user->id;
            $notification->read_by = implode(',', $readers_array);
            $notification->save();
        }
        
        $unread_notifications = LicenseActivity::where('read_by', 'NOT LIKE', '%'.$user->id.'%')->orWhere('read_by', NULL)->count();
        return response()->json(compact('unread_notifications'), 200);
    }



    public function show(User $user)
    {
        return response()->json(compact('user'), 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     */
    public function uploadPhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:1024',
            ]);
            $user_id = $request->user_id;
            $user = User::find($user_id);
            if ($request->file('photo') != null && $request->file('photo')->isValid()) {
                if ($user->photo_path !== 'storage/photo/default.png') {

                    Storage::disk('public')->delete(str_replace('storage/', '', $user->photo_path));
                }
                $name = 'photo_'.$user_id.'_'.$request->file('photo')->getClientOriginalName();
                // $name = 'photo_'.$user_id.'_'.$request->file('photo')->hashName();
                $file_name = $name . "." . $request->file('photo')->extension();
                $link = $request->file('photo')->storeAs('photo', $file_name, 'public');

                $user->photo_path = 'storage/'.$link;
                $user->photo = env('APP_URL').'/'.$user->photo_path;
                $user->save();
                return $this->show($user);
            }
            
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Please provide a valid image. Image types should be: jpeg, jpg or png. It must not be more than 1MB in size'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request, User $user)
    {
        //
        $user->name = $request->name;
        $user->email = $request->email;
        // $user->phone = $request->phone;
        $user->save();

        return response()->json([], 204);
    }
    public function toggleSubsidiaryStatus(Request $request, User $user)
    {
        $value = $request->value; // 'Active' or 'Inactive'
        $user->status = $value;
        $user->save();
        return $this->show($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        // $user->delete();
        // return response()->json([], 204);
    }
}
