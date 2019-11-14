<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('version', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'Costanza, version ' . config('app.version'),
    ],200);
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', 'Auth\LoginController@logout');

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');

    // Stanza routes
    Route::get('stanza', 'StanzaListController@index');
    Route::get('stanza/updates/{count?}', 'StanzaListController@updates');
    Route::get('stanza/{id}/contents', 'StanzaListController@contents');
    Route::get('stanza/{id}', 'StanzaListController@show');

    // EZproxy/Costanza file routes
    Route::get('files', 'FileController@index');
    Route::post('files/new', 'FileController@create');
    Route::post('files/import', 'FileController@import');
    Route::post('files/export', 'FileController@export');
    Route::get('files/{filename}', 'FileController@show');

    // EZproxy/Costanza file entry routes
    Route::get('files/{filename}/entries/{id}', 'EntryController@show');
    Route::match(['put', 'patch'], 'files/{filename}/entries/{id}', 'EntryController@update');
    Route::delete('files/{filename}/entries/{id}', 'EntryController@destroy');
    Route::post('files/{filename}/entries/{id}/rules', 'EntryController@rules');
    Route::post('files/{filename}/entries/{id}', 'EntryController@move');
    Route::post('files/{filename}/entries', 'EntryController@store');
});

// Administrative routes (for account creation, login, password management etc)
Route::group(['middleware' => 'guest:api'], function () {
    Route::post('login', 'Auth\LoginController@login');
    Route::post('register', 'Auth\RegisterController@register');

    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');

    Route::post('email/verify/{user}', 'Auth\VerificationController@verify')->name('verification.verify');
    Route::post('email/resend', 'Auth\VerificationController@resend');

    Route::post('oauth/{driver}', 'Auth\OAuthController@redirectToProvider');
    Route::get('oauth/{driver}/callback', 'Auth\OAuthController@handleProviderCallback')->name('oauth.callback');
});
