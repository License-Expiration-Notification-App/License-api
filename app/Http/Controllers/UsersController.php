<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use App\Models\School;
use App\Models\Staff;
use App\Models\State;
use App\Models\Student;
use App\Models\StudentsInClass;
use Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
class UsersController extends Controller
{
    public function index(Request $request)
    {
        $searchParams = $request->all();
        $userQuery = User::query();
        $keyword = Arr::get($searchParams, 'search', '');
        $status = Arr::get($searchParams, 'status', '');
        $date_created = Arr::get($searchParams, 'date_created', '');
        $sort_by = Arr::get($searchParams, 'sort_by', 'name');
        $sort_direction = Arr::get($searchParams, 'sort_direction', 'ASC');

        if (!empty($keyword)) {
            $userQuery->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('email', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (!empty($date_created)) {
            $userQuery->where('created_at',  'LIKE', '%' . date('Y-m-d',strtotime($date_created)) . '%');
        }
        if (!empty($status)) {
            $userQuery->where('status',  $status);
        }
        if ($sort_by == '') {
            $sort_by = 'name';
        }
        if ($sort_direction == '') {
            $sort_direction = 'ASC';
        }
        $users = $userQuery->where('role', 'staff')->orderBy($sort_by, $sort_direction)->paginate(10);
        return response()->json(compact('users'), 200);
    }
    public function auditTrail(Request $request)
    {
        $user = $this->getUser();
        // $school = $this->getSchool();
        // $sess_id = $this->getSession()->id;
        $notifications = $user->notifications()->where('data', 'LIKE', '%Audit Trail%')->orderBy('created_at', 'DESC')->paginate(50)->groupBy('type');
        $unread_notifications = $user->unreadNotifications()->count();
        return response()->json(compact('notifications', 'unread_notifications'), 200);
    }
    public function markNotificationAsRead(Request $request)
    {
        $user = $this->getUser();
        $user->unreadNotifications->markAsRead();
        return $this->userNotifications($request);
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
     * @return \Illuminate\Http\Response
     */
    public function uploadPhoto(Request $request)
    {
        $this->validate($request, [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:1024',
        ]);
        $user_id = $request->user_id;
        $user = User::find($user_id);
        if ($request->file('photo') != null && $request->file('photo')->isValid()) {
            if ($user->photo_path !== 'storage/photo/default.png') {

                Storage::disk('public')->delete(str_replace('storage/', '', $user->photo_path));
            }

            $name = 'photo_'.$user_id.'_'.$request->file('photo')->hashName();
            // $file_name = $name . "." . $request->file('file_uploaded')->extension();
            $link = $request->file('photo')->storeAs('photo', $name, 'public');

            $user->photo_path = 'storage/'.$link;
            $user->photo = env('APP_URL').'/'.$user->photo_path;
            $user->save();
            return $this->show($user);
        }
        return response()->json(['message' => 'Please provide a valid image. Image types should be: jpeg, jpg and png. It must not be more than 1MB in size'], 500);
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
