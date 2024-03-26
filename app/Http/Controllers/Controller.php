<?php

namespace App\Http\Controllers;

use App\Notifications\AuditTrail;
use App\Models\Client;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $user;
    protected $client;
    protected $partner;
    protected $myProjects;
    protected $role;
    protected $roles = [];
    protected $data = [];
    protected $currency = '₦'; //'&#x20A6;';
    protected $this_year;

    public function __construct(Request $httpRequest)
    {
        //->paginate(10);
        $this->middleware(function ($request, $next) {
            return $next($request);
        });
    }
    public function render($data = [])
    {
        $this->data = array_merge($this->data, $data);
        $this->data['currency'] = '₦';

        //print_r($data['class']->class->name);exit;*/
        return response()->json($this->data, 200);
    }
    // public function toggleStudentNonPaymentSuspension(Request $request)
    // {
    //     $student_ids = $request->student_ids;
    //     foreach ($student_ids as $student_id) {
    //         $student = Student::find($student_id);
    //         $status = $student->studentship_status;
    //         if ($status == 1) {
    //             $student->studentship_status = 0;
    //         } else {
    //             $student->studentship_status = 1;
    //         }
    //         $student->save();
    //     }
    //     return 'success';
    // }
    public function setYear()
    {

        $this->this_year = (int) date('Y', strtotime('now'));
    }
    public function getYear()
    {
        $this->setYear();
        return $this->this_year;
    }
    public function setRoles()
    {
        $school_id = $this->getSchool()->id;
        $roles = Role::where('school_id', 0)->orWhere('school_id', $school_id)->get();
        foreach ($roles as $role) {
            $role_permissions = [];
            foreach ($role->permissions as $permission) {
                $role_permissions[] = $permission->id;
            }
            $role->role_permissions = $role_permissions;
        }
        $this->roles = $roles;
    }
    public function getRoles()
    {
        $this->setRoles();
        return $this->roles;
    }
    public function getPermissions()
    {
        $permissions = Permission::orderBy('name')->get();
        return $permissions;
    }
    public function getSoftwareName()
    {
        return env("APP_NAME");
    }

    public function setUser()
    {
        $this->user  = Auth::user();
    }

    public function getUser()
    {
        $this->setUser();

        return $this->user;
    }
    public function setMyProjects()
    {
        $this->myProjects  = $this->getUser()->projects()->with('client', 'certificate', 'standard')->where([/*'year' => $this->getYear()*/])->get();
    }

    public function getMyProjects()
    {
        $this->setMyProjects();

        return $this->myProjects;
    }
    public function setClient()
    {
        $user  = Auth::user();
        $client_user = DB::table('client_user')->where('user_id', $user->id)->first();
        $client_id = $client_user->client_id;
        $this->client = Client::find($client_id);
    }

    public function getClient()
    {
        $this->setClient();

        return $this->client;
    }
    public function setPartner()
    {
        $user  = Auth::user();
        $partner_user = DB::table('partner_user')->where('user_id', $user->id)->first();
        $partner_id = $partner_user->partner_id;
        $this->partner = Partner::find($partner_id);
    }

    public function getPartner()
    {
        $this->setPartner();

        return $this->partner;
    }
    public function getCurrency()
    {
        return $this->currency;
    }


    public function uploadFile($media, $file_name, $folder_key)
    {
        $folder = "clients/" . $folder_key;

        $media->storeAs($folder, $file_name, 'public');

        return $folder . '/' . $file_name;
    }
    public function auditTrailEvent($title, $action, $clients = null)
    {

        // $user = $this->getUser();
        $users = User::whereHas('roles', function ($query) {
            $query->where('name', '=', 'super')
                ->orWhere('name', '=', 'admin'); // this is the role id inside of this callback
        })->get();
        if ($clients != null) {
            $users = $users->merge($clients);
        }
        $notification = new AuditTrail($title, $action);
        return Notification::send($users->unique(), $notification);
    }
}
