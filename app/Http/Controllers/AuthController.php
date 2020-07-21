<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Avatar;
use Storage;

// Models
use App\User;




// Notifications
use App\Notifications\SignupActivate;
use App\Notifications\SignupActivateConfirmation;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] type
     * @return [string] message
     */
    public function Register(Request $request)
    {
        $userLevels = config('QuestApp.UserLevels');
        unset($userLevels['sa']);
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'type' => ['string', Rule::in(array_keys($userLevels))]
        ]);

        $request->type = $request->type ? $request->type : 's';
        $request->name = ucwords($request->name);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'user_id' => sha1('User' . $request->email . Str::random(60)),
            'password' => bcrypt($request->password),
            'activation_token' => sha1(Str::random(10)),
            'role' => $userLevels[$request->type]
        ]);
        $user->save();


        $avatar = Avatar::create($user->name)->getImageObject()->encode('png');
        Storage::put('avatars/' . $user->id . '/avatar.png', (string) $avatar);

        $user->notify(new SignupActivate($user));

        $response = config('QuestApp.JsonResponse.created');
        $response['data']['message'] = 'User created successfully!';

        return ResponseHelper($response);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function Login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        // $credentials['active'] = 1;
        $credentials['deleted_at'] = null;


        if (!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);

        $user = $request->user();

        if($user->active === 0){
            return response()->json([
                'message' => 'Account has been suspended.'
            ], 401);
        }


        $tokenResult = $user->createToken('QuestApp');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function Logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function User(Request $request)
    {
        return response()->json($request->user());
    }

    public function signupActivate($token)
    {
        $user = User::where('activation_token', $token)->first();
        if (!$user) {
            return response()->json([
                'message' => 'This activation token is invalid.'
            ], 404);
        }
        $user->active = true;
        $user->activation_token = '';
        $user->email_verified_at = Carbon::now();
        $user->save();

        $user->notify(new SignupActivateConfirmation($user));

        return response()->json([
            'message' => 'Account has been activated.'
        ]);
    }

    public function GetAvatar(Request $request, $user_sl, $filename)
    {
        $path = storage_path('app' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $user_sl . DIRECTORY_SEPARATOR . $filename);
        if (!File::exists($path)) {
            abort(404);
        }
        $file = File::get($path);
        $type = File::mimeType($path);

        $response = response()->make($file, 200);
        $response->header("Content-Type", $type);
        return $response;
    }
}
