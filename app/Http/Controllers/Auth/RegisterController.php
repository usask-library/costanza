<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function registered(Request $request, User $user)
    {
        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();

            return response()->json(['status' => trans('verification.sent')]);
        }

        return response()->json($user);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
            'institution_code' => 'nullable|alpha_num',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        // Ensure the user profile has an institution code.
        $data['institution_code'] = isset($data['institution_code']) ? $data['institution_code'] : uniqid();

        // Ensure a folder exists to store the user's data.  If the institution code is being shared, this folder may exist already
        if (! Storage::disk('users')->exists($data['institution_code'])) {
            Storage::disk('users')->makeDirectory($data['institution_code'], 0775, true);
        }

        // Ensure an EZproxy config exists, using the default as a starting point if one is missing
        // Again, if the institution code is being shared, this may already exist
        if (! Storage::disk('users')->exists($data['institution_code'] . '/config.json')) {
            $ezproxyConfig = Storage::get('stub.json');
            Storage::disk('users')->put($data['institution_code'] . '/config.json', $ezproxyConfig);
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'institution_code' => $data['institution_code'],
        ]);
    }
}
