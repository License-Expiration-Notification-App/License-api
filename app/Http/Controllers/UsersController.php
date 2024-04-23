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
use Laracasts\Flash\Flash;

class UsersController extends Controller
{
    public function fetchPartnerUsers()
    {
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', '=', 'partner');
        })->orWhere('role', 'partner')->get();
        return response()->json(compact('users'), 200);
    }
    public function fetchStaff()
    {
        $user = $this->getUser();
        $staff = [];
        if ($user->haRole('partner')) {
            $partner = $this->getPartner();
            $staff = $partner->users()->with('roles', 'permissions')->get();
        }
        if ($user->isSuperAdmin()) {

            $staff = User::with('roles', 'permissions')->where('role', 'staff')->get();
        }
        return response()->json(compact('staff'), 200);
    }
    public function userNotifications(Request $request)
    {
        $user = $this->getUser();
        // $school = $this->getSchool();
        // $sess_id = $this->getSession()->id;
        $notifications = $user->notifications()->orderBy('created_at', 'DESC')->paginate($request->limit);
        $unread_notifications = $user->unreadNotifications()->count();
        return response()->json(compact('notifications', 'unread_notifications'), 200);
    }
    public function markNotificationAsRead(Request $request)
    {
        $user = $this->getUser();
        $user->unreadNotifications->markAsRead();
        return $this->userNotifications($request);
    }
    public function changePassword()
    {
        $user = $this->getUser();
        $user->password_status = 'default';
        $user->save();
        return redirect()->route('dashboard');
    }
    public function adminResetUserPassword(Request $request)
    {
        $user = User::find($request->user_id);
        $user->password = 'password';
        $user->password_status = 'default';
        $user->save();
    }
    public function resetPassword(Request $request, User $user)
    {
        $confirm_password = $request->confirm_password;
        $new_password = $request->new_password;

        if ($new_password === $confirm_password) {
            $user->password = $new_password;
            $user->password_status = 'custom';

            if ($user->save()) {
                return response()->json(['message' => 'success'], 200);
            }
        }
        return response()->json([
            'message' => 'Password does not match'
        ], 401);
    }

    public function approveUser(Request $request, User $user)
    {
        $user->is_confirmed = '1';
        $user->save();
        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_obj = new User();
        $response = $user_obj->createUser($request);
        if ($response['message'] == 'success') {
            $user = $response['user'];
            $user->roles()->sync($request->role_id);
        }
        return response()->json(['error' => $response['message']], 500);
    }




    public function show(User $user)
    {
        return response()->json(compact('user'), 200);
    }

    public function editPhoto(Request $request)
    {

        if (isset($request->user_id) && $request->user_id != '') {
            $user_id = $request->user_id;
            $edit_user = User::find($user_id);
        } else {
            $edit_user = $this->getUser();
        }

        return $this->render('core::users.edit_photo', compact('edit_user'));
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

            $name = 'photo_'.$user_id.'_'.$request->file('photo')->hashName();
            // $file_name = $name . "." . $request->file('file_uploaded')->extension();
            $link = $request->file('photo')->storeAs('photo', $name, 'public');

            $user->photo = $link;
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
