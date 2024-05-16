<?php

namespace App\Http\Resources;

use App\Models\ActivatedModule;
use App\Models\AvailableModule;
use App\Models\Client;
use App\Models\Partner;
use App\Models\SSession;
use App\Models\Term;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        // $modules = [];
        $main_admin = false;
        $client_id = '';
        if ($this->role === 'client') {
            $client_user = DB::table('client_user')->where('user_id', $this->id)->first();
            $client_id = $client_user->client_id;
            $client = Client::find($client_id);
            if($client->main_admin === $this->id){
                $main_admin = true;
            }
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'password_status' => $this->password_status,
            'notifications' => [],
            'role' => $this->role,
            'client_id' => $client_id,
            // 'activity_logs' => $this->notifications()->orderBy('created_at', 'DESC')->get(),
            // 'roles' => array_map(
            //     function ($role) {
            //         return $role['name'];
            //     },
            //     $this->roles->toArray()
            // ),
            'is_client_main_admin' => $main_admin,
            'permissions' => array_map(
                function ($permission) {
                    return $permission['name'];
                },
                $this->allPermissions()->toArray()
            ),
            'status' => $this->status,
            // 'logo' => $this->logo,
            // 'navbar_bg' => $this->navbar_bg,
            // 'sidebar_bg' => $this->sidebar_bg,

        ];
    }
}
