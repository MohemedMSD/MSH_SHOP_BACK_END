<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Actions\Passport\PasswordValidationRules;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Crypt;
use Hash;

class UpdateProfileInformation extends Controller
{
    //
    use PasswordValidationRules;

    public function update(Request $request){

        if (isset($request->name)) {
            
            $validation = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'image' => 'mimes:png,jpg,webp,jpeg|max:1024',
                'adress' => 'required',
                'adress.city' => 'required|string|max:100',
                'adress.line' => 'required|string|max:255',
                'adress.state' => 'required|string|max:100',
                'adress.postal_code' => 'required|string|max:10',
                'adress.country' => 'required|string|max:255',
            ]);
    
            if ($validation->fails()) {
                
                return response()->json($validation->messages(), 422);
    
            }

            $profile = $request->user()->profile ? $request->user()->profile : null;
            
            $defaultBucket = app('firebase.storage')->getBucket();

            if ($request->file('profile') != null) {
                
                $file = $request->file('profile');

                if ($profile && $defaultBucket->object($profile)->exists()) {
                    $defaultBucket->object($profile)->delete();
                }

                if ($defaultBucket->object('profile/' . $file->getClientOriginalName())->exists()) {
                    $profile = 'profile/' . uniqid() . '_' . $file->getClientOriginalName();
                }else{
                    $profile = 'profile/' . $file->getClientOriginalName();
                }

                // Open the uploaded image
                $image = Image::make($file);

                // Compress the image with a desired quality (e.g., 60)
                $image->resize(100, 100, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $defaultBucket->upload($image->stream()->detach(), [
                    'name' => $profile,
                ]);

            }

            $request->user()->update([
                'name' => $request->name,
                'profile' => $profile,
                'adress' => $request->adress
            ]);
        }

        $user = $request->user();

        if (isset($request->email)) {

            $validation = Validator::make($request->all(), [
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    'unique:users,email,'.$request->user()->id,
                ],
                'password' => 'required'
            ]);
    
            if ($validation->fails()) {
                
                return response()->json($validation->messages(), 422);
    
            }

            if (isset($user) && Hash::check($request->password, $user->password)) {
                
                $user->update([
                    'email' => $request->email,
                ]);

            }else{

                return response()->json([
                    'password' => 'The password is incorrect'
                ], 422);

            }
            
        }

        if (isset($request->email) || isset($request->name)) {
    
            return response()->json([
                'number' => Crypt::encrypt($user->id),
                'name' => $user->name,
                'email' => $user->email,
                'verified_at' => $user->verified_at,
                'role' => $user->role->name,
                'profile' => isset($user->profile) ? $defaultBucket->object($user->profile)
                ->signedUrl(new \DateTime('48 hour')) : '',
                'token' => $user->token()->id
            ]);

        }
    }

    public function updateUserPassword(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ]);

        if ($validation->fails()) {
            
            return response()->json($validation->messages(), 422);

        }

        if (!Hash::check($request->current_password, $request->user()->password) ) {
            return response()->json([
                'current_password' => 'The Password is incorrect'
            ], 422);
        }

        $request->user()->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        return response()->json(200);
    }

}
