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
        // $partner = '';
        // if ($this->role === 'client') {
        //     $client_user = DB::table('client_user')->where('user_id', $this->id)->first();
        //     $client_id = $client_user->client_id;
        //     $client = Client::find($client_id);
        //     $partner_id = $client->partner_id;
        //     $activated_modules = ActivatedModule::where('partner_id', $partner_id)->where('client_ids', 'LIKE', '%' . $client_id . '%')->get();
        //     foreach ($activated_modules as $activated_module) {

        //         $modules[] = $activated_module->availableModule->slug;
        //     }
        //     $partner = Partner::find($partner_id);
        // }
        // if ($this->haRole('partner')) {
        //     $partner_user = DB::table('partner_user')->where('user_id', $this->id)->first();
        //     $partner_id = $partner_user->partner_id;
        //     $partner = Partner::find($partner_id);
        //     $activated_modules = $partner->activatedModules;
        //     foreach ($activated_modules as $activated_module) {

        //         $modules[] = $activated_module->availableModule->slug;
        //     }
        // }
        // if ($this->haRole('super') || $this->haRole('admin')) {
        //     $modules = AvailableModule::pluck('slug');
        // }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password_status' => $this->password_status,
            'notifications' => [],
            // 'activity_logs' => $this->notifications()->orderBy('created_at', 'DESC')->get(),
            'roles' => array_map(
                function ($role) {
                    return $role['name'];
                },
                $this->roles->toArray()
            ),
            // 'role' => 'admin',
            'permissions' => array_map(
                function ($permission) {
                    return $permission['name'];
                },
                $this->allPermissions()->toArray()
            ),
            // 'photo' => $this->photo,
            // 'logo' => $this->logo,
            // 'navbar_bg' => $this->navbar_bg,
            // 'sidebar_bg' => $this->sidebar_bg,

        ];
    }
}
