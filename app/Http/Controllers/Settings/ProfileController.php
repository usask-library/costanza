<?php

namespace App\Http\Controllers\Settings;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Update the user's profile information.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'institution_code' => 'nullable|alpha_num',
        ]);

        // If the institution_code is empty, generate a new one
        if (empty($request->get('institution_code'))) {
            $request->merge(['institution_code' => uniqid()]);
        }

        // If the institution code changed, move the user's data directory
        Log::debug('Comparing ' . $user->institution_code . ' to ' . $request->get('institution_code'));
        if (($user->institution_code != $request->get('institution_code')) && Storage::disk('users')->exists($user->institution_code)) {
            Storage::disk('users')->move($user->institution_code, $request->get('institution_code'));
        }

        return tap($user)->update($request->only('name', 'email', 'institution_code'));
    }
}
