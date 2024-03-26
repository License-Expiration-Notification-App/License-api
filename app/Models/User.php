<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions;
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'confirm_hash',
        'api_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    // public function country()
    // {
    //     return $this->belongsTo(Country::class);
    // }
    // public function state()
    // {
    //     return $this->belongsTo(State::class);
    // }
    // public function lga()
    // {
    //     return $this->belongsTo(LocalGovernmentArea::class, 'lga_id', 'id');
    // }
    protected function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }
    /**
     * The roles that belong to the Client
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class);
    }
    public function uploadFile($request, $file_name, $folder)
    {

        $request->file('photo')->storeAs($folder, $file_name, 'public');

        return $photo_name = $folder . '/' . $file_name;
    }
    public function createUser($data)
    {
        $user = User::where('email', $data->email)->first();
        if (!$user) {
            $user = new User();
        }
        $user->name = $data->name;
        $user->email = $data->email;
        // $user->phone = $data->phone;
        $user->password = $data->password;
        $user->role = $data->role;
        $user->confirm_hash = hash('sha256', time() . $data->email);
        $user->save();
        // $this->setUserPasswordRecord($user->id, $data->password);
        return $user;
    }

    private function setUserPasswordRecord($user_id, $password)
    {
        $user_password = new UserPassword();
        $user_password->user_id = $user_id;
        $user_password->password = hash('sha256', $password);
        $user_password->save();
    }


    public function isSuperAdmin()
    {
        // foreach ($this->roles as $role) {
        //     if ($role->isSuperAdmin()) {
        //         return true;
        //     }
        // }
        if ($this->login_as === 'super') {
            return true;
        }
        return false;
    }
    public function isAdmin()
    {
        // foreach ($this->roles as $role) {
        //     if ($role->isAdmin()) {
        //         return true;
        //     }
        // }
        if ($this->login_as === 'admin') {
            return true;
        }
        return false;
    }
    public function haRole($userRole)
    {
        // foreach ($this->roles as $role) {
        //     if ($role->hasRole($userRole)) {
        //         return true;
        //     }
        // }
        if ($this->login_as === $userRole) {
            return true;
        }
        return false;
    }

    // public function routeNotificationFor($channel)
    // {
    //     if ($channel === 'PusherPushNotifications') {
    //         return 'App.Models.User.{id}';
    //     }

    //     $class = str_replace('\\', '.', get_class($this));

    //     return $class . '.' . $this->getKey();
    // }
}
