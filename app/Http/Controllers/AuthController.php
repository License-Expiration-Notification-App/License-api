<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Jobs\SendQueuedPasswordResetEmailJob;
use App\Models\TwoFactorAuthentication;
use App\Jobs\SendQueued2FACode;

use App\Mail\ConfirmNewRegistration;
use App\Mail\ResetPassword;
use App\Models\Client;
use App\Models\Partner;
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
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
        ]);

        $user = new User([
            'name'  => $request->name,
            'email' => $request->email,
            'confirm_hash' => hash('sha512', $request->email),
        ]);

        if ($user->save()) {
            // SendQueuedPasswordResetEmailJob::dispatch($user, $token);
            Mail::to($user)->send(new ConfirmNewRegistration($user));

            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->plainTextToken;

            return response()->json([
                'message' => 'Successfully created user!',
                'accessToken' => $token,
            ], 200);
        } else {
            return response()->json(['error' => 'Provide proper details']);
        }
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
        $description = "$name logged in to the portal";
        $this->auditTrailEvent($title, $description);

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

        // log this event
        // $description = 'logged out of the portal';
        // $this->auditTrailEvent($request, $description);

        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'success'
        ]);
    }

    public function confirmRegistration(Request $request)
    {
        $hash = $request->code;
        $message = 'Invalid Activation Link';
        if ($hash != '') {

            $confirm_hash = User::where(['confirm_hash' => $hash])->first();
            if ($confirm_hash) {        //hash is confirmed and valid
                if ($confirm_hash->email_verified_at === NULL) {
                    $confirm_hash->email_verified_at = date('Y-m-d H:i:s', strtotime('now'));
                    $confirm_hash->save();
                    return response()->json(['email' => $confirm_hash->email, 'message' => 'Successful'], 200);
                } else {
                    return response()->json(['message' => 'Already Activated'], 405);
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
            PasswordResetToken::firstOrCreate(
                ['email' => $user->email, 'token' => hash('sha512', $token)]
            );

            // SendQueuedPasswordResetEmailJob::dispatch($user, $token);
            Mail::to($user)->send(new ResetPassword($user, $token));
            return response()->json(['message' => 'Reset Code Sent'], 200);
        }

        return response()->json(['message' => 'Email Not Found'], 500);
    }
    public function confirmPasswordResetToken($token)
    {
        $token = hash('sha512', $token);
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
            // $user->password_status = 'custom';
            // $user->password_expires_at = date('Y-m-d H:i:s', strtotime($this->todayDate . ' +90 days'));
            if ($user->save()) {
                PasswordResetToken::where('email', $request->email)->delete();
            }
        }

        return 'success';
    }
}
