<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Actions\Passport\PasswordValidationRules;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Archive_order;   
use App\Mail\VerificationCodeMail;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Hash;
use Carbon\Carbon;


class AuthenticationController extends Controller
{
    //
    use PasswordValidationRules;

    public function register(Request $request){

        $validation = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => $this->passwordRules(),
            'password_confirmation' => 'required'
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        $defaultBucket = app('firebase.storage')->getBucket();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => Role::where('name', 'costumer')->first()->id
        ]);

        // create token for user
        $token = $user->createToken('auth_token')->accessToken;

        return response([
            'user' => [
                'number' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name,
                'profile' => isset($user->profile) ? $defaultBucket->object($user->profile)
                ->signedUrl(new \DateTime('48 hour')) : '',
                'token' => $token,
                'verified_at' => $user->email_verified_at
            ] 
        ]);

    }

    public function login(Request $request){

        $validation = Validator::make($request->all(), [
            'email' => [
                'required','exists:users,email'
            ],
            'password' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->messages(), 422);

        }

        $user = User::where('email', $request->email)->first();
            
        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([
                'email' => [ 0 => ''],
                'password' => [0 => 'The password is incorrect'],
            ], 422);

        } else{

                $defaultBucket = app('firebase.storage')->getBucket();
                // create token for user
                $token = $user->createToken('auth_token')->accessToken;

                return response([
                    'user' => [
                        'number' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role->name,
                        'profile' => isset($user->profile) ? $defaultBucket->object($user->profile)
                        ->signedUrl(new \DateTime('48 hour')) : '',
                        'token' => $token,
                        'verified_at' => $user->email_verified_at
                    ]
                ]);
    
            }
        
    }

    public function sendVerificationCode(Request $request){

        $user = $request->user();
        
        $VerifyToken = DB::table('password_reset_tokens')
        ->where('email', $user->email);

        if ($user && !$user->hasVerifiedEmail()) {

            $token = Str::random(200);

            if ($VerifyToken->exists()) {
                
                $VerifyToken->delete();

            }else{

                DB::table('password_reset_tokens')->insert([
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => now()
                ]);

            }

            Mail::to($user->email)->send(new VerificationCodeMail($user, $token, $request->baseUrl, 'verify'));
            
            return response()->json($user->email);

        }

        return response()->json('You have verified email!');

    }

    public function verify(Request $request, $token)
    {
        
        $user = $request->user();
        $VerifyToken = DB::table('password_reset_tokens')
        ->where('email', $user->email);
    
        
        if ($user && $VerifyToken->first()->token == $token && !now()->greaterThan(Carbon::parse($VerifyToken->first()->created_at)->addMinutes(5))) {

            $user->markEmailAsVerified();
            $VerifyToken->delete();

            return response()->json([
                'verified_at' => $user->email_verified_at
            ]);

        }

        return response()->json('Invalid verification link.', 422);

    }


    public function logout(Request $request){
        // dd($request);
        Auth()->user()->token()->revoke();

        return response([
            'message' => 'Logged out succesfully'
        ]);

    }

    public function forgetPassword(Request $request){

        $validation = Validator::make($request->all(), [
            'email' => [
                'required','exists:users,email'
            ]
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        $user = User::where('email', $request->email)->first();
        
        $VerifyToken = DB::table('password_reset_tokens')
        ->where('email', $user->email);

        if ($user) {

            $token = Str::random(200);

            if ($VerifyToken->exists()) {
                
                $VerifyToken->delete();

            }else{

                DB::table('password_reset_tokens')->insert([
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => now()
                ]);

            }

            Mail::to($user->email)->send(new VerificationCodeMail($user, $token, $request->baseUrl, 'reset'));
            
            return response()->json($user->email);

        }

    }

    public function checkLinkIsValide($token){
        
        $res = false;

        $VerifyToken = DB::table('password_reset_tokens')
        ->where('token', $token);

        $user = User::where('email', $VerifyToken->first()->email)->first();
        if ($user && $token && !now()->greaterThan(Carbon::parse($VerifyToken->first()->created_at)->addMinutes(5))) {

            $res = true;

        }

        return response()->json($res);

    }

    public function resetPassword(Request $request, $token){

        $validation = Validator::make($request->all(), [
            'password' => $this->passwordRules(),
            'password_confirmation' => 'required'
        ]);

        if ($validation->fails()) {
        
            return response()->json($validation->messages(), 422);

        }
        
        $VerifyToken = DB::table('password_reset_tokens')
        ->where('token', $token);

        $user = User::where('email', $VerifyToken->first()->email)->first();
        
        if ($user && $VerifyToken->first() && !now()->greaterThan(Carbon::parse($VerifyToken->first()->created_at)->addMinutes(5))) {

            $user->update([
                'password' => $request->password
            ]);

            $VerifyToken->delete();

            return response()->json('password updated successfuly');

        }

        return response()->json('Sometimes wrong, please try again', 422);
        
    }

}
