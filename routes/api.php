<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('satellites', 'IssController@satellites')->name('satellites');

Route::get('satellite/{id?}', 'IssController@satelliteId')->name('satellite.id');
Route::get('coordinates/{lat},{lon}', 'IssController@coordinates')->name('coordinates');
Route::get('distance/{lat},{lon}', 'IssController@getDistance')->name('calculate');
