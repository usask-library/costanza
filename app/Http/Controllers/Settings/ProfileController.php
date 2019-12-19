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

        // If the institution code changed...
        if ($user->institution_code != $request->get('institution_code')) {
            // Check if a folder matching the new institution code already exists
            if (Storage::disk('users')->exists($request->get('institution_code'))) {
                // It does, meaning this user is will start sharing a workspace with an existing user
                // ToDo: Decide on the best course of action for the old folder -- abandon, merge, delete
                // Current course of action is to just abandon the old folder, as this is non-destructive
                // Storage::disk('users')->deleteDirectory($user->institution_code);
            } else {
                // It does not, meaning this user simply changed their institution ID
                // Move their current files to the new location (i.e. rename the folder)
                Storage::disk('users')->move($user->institution_code, $request->get('institution_code'));
            }

            // Update the institution code on the account
            return tap($user)->update($request->only('name', 'email', 'institution_code'));
        }

        return $user;
    }
}
