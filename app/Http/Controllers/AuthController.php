<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Mail\ResetPassword;
use App\Models\PasswordResetToken;
use App\Models\UserPassword;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    protected $macAddr;
    protected $todayDate;
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->macAddr = request()->ip();
        $this->todayDate = date('Y-m-d', strtotime('now'));
        // $this->middleware('guest')->except('logout');
        // $this->username = $this->findUsername();
    }
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function register(Request $request)
    {
        $actor = $this->getUser();
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
        ]);
        $user_object = new User();
        $request['role'] = 'staff';
        $response = $user_object->createUser($request);

        if ($response['message'] == 'success') {
            $user = $response['user'];
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;
            $title = "New Registration";
            //log this event
            $description = "<strong>$user->name</strong> was registered by&nbsp;";
            $this->auditTrailEvent($title, $description, $actor->id);

            return response()->json([
                'message' => 'Successfully created user!',
                'accessToken' => $token,
            ], 200);
        }
        return response()->json(['error' => $response['message']], 500);
    }
    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     */
    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');
        $request->validate([
            // 'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        // $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid Credentials'
            ], 401);
        }

        $user = $request->user();

        // if ($user->email_verified_at === NULL) {
        //     return response()->json(['message' => 'Account Activation Needed'], 403);
        // }
        // if ($user->password_status === 'default') {
        //   $message = 'change_password';
        //   $title = 'You need to change your password from the default';
        //   return response()->json(compact('title', 'message', 'user'), 200);
        // }

        // $password_expires_at = date('Y-m-d', strtotime($user->password_expires_at));
        // if ($this->todayDate >= $password_expires_at || $password_expires_at === NULL) {
        //   $message = 'password_due_for_change';
        //   $title = 'Your password is due for a change.';
        //   return response()->json(compact('title', 'message', 'user'), 200);
        // }

        $clients = $user->clients;
        if ($clients != '[]' && isset($clients[0])) {
            $client = $clients[0];
            if ($client->is_active === 0) {
                return response()->json(['message' => 'Your account has been suspended. Kindly contact the administrator'], 403);
            }
        }
        return $this->generateAuthorizationKey($user);
    }
    private function generateAuthorizationKey($user)
    {
        
        $name = $user->name . ' (' . $user->email . ')';
        $title = "Log in action";
        //log this event
        $description = "<strong>$name</strong> logged in to the portal";
        $this->auditTrailEvent($title, $description, $user->id, 'Authentication', 'add', null, false);

        $user_resource = new UserResource($user);
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;
        // return response()->json([
        //     'user_data' => $user_resource
        // ])->header('Authorization', $token);
        return response()->json(['data' => $user_resource, 'tk' => $token], 200)->header('Authorization', $token);
    }
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function fetchUser()
    {
        return new UserResource(Auth::user());
        // return response()->json($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    // public function logout(Request $request)
    // {
    //     $request->user()->tokens()->delete();

    //     return response()->json([
    //         'message' => 'Successfully logged out'
    //     ]);
    // }
    public function logout(Request $request)
    {
        // return $request;
        // $this->guard()->logout();

        // $request->session()->invalidate();
        // $request->user()->tokens()->delete();
        $actor = $request->user();
        // log this event
        $title = "Logout Action Registration";
        $description = "<strong>$actor->name</strong> logged out of the portal";
        $this->auditTrailEvent($title, $description, $actor->id, 'Authentication', 'edit', null, false);

        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function confirmRegistration(Request $request)
    {
        $hash = $request->token;
        $message = 'Invalid Activation Link';
        if ($hash != '') {

            $confirm_hash = User::where(['confirm_hash' => $hash])->first();
            if ($confirm_hash) {        //hash is confirmed and valid
                
                if ($confirm_hash->email_verified_at == NULL) {
                    $confirm_hash->email_verified_at = date('Y-m-d H:i:s', strtotime('now'));
                    $confirm_hash->save();
                    
                    return response()->json(['email' => $confirm_hash->email, 'message' => 'Successful'], 200);
                } else if($confirm_hash->password == NULL){
                    return response()->json(['email' => $confirm_hash->email, 'status' => 'Pending'], 200);
                }else {
                    return response()->json(['message' => 'Already Activated', 'status' => 'Active'], 500);
                }
                //return view('auth.registration_confirmed', compact('message'));


            }
        }


        return response()->json(['message' => 'Invalid Link'], 500);
    }
    public function recoverPassword(Request $request)
    {

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $token = randomNumber();
            PasswordResetToken::updateOrInsert(['email' => $user->email],
                ['token' => hash('sha512', $token)]
            );

            // SendQueuedPasswordResetEmailJob::dispatch($user, $token);
            Mail::to($user)->send(new ResetPassword($user, $token));
            return response()->json(['message' => 'Reset Code Sent'], 200);
        }

        return response()->json(['message' => 'Email Not Found'], 500);
    }
    public function confirmPasswordResetToken($code)
    {
        $token = hash('sha512', $code);
        $user_token = PasswordResetToken::where('token', $token)->first();
        if ($user_token) {
            return response()->json(['email' => $user_token->email], 200);
        }
        return response()->json(['message' => 'Invalid Reset Token'], 500);
    }
    public function resetPassword(Request $request)
    {
        if (isset($request->include_old_password)) {


            $credentials = $request->only('email', 'password');
            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'You need to remember your old password'
                ], 401);
            }
        }


        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = $request->new_password;
            $user->save();
            // $user->password_status = 'custom';
            // $user->password_expires_at = date('Y-m-d H:i:s', strtotime($this->todayDate . ' +90 days'));
            if ($user->save()) {
                PasswordResetToken::where('email', $request->email)->delete();
            }
            $title = "Password updated";
            $description = "<strong>$user->name</strong> password was updated";
            $this->auditTrailEvent($title, $description, $user->id, 'Authentication', 'edit', null, false);
            
            return 'success';
        }

        return 'User Not Found';
    }
}
