<?php

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

Route::group([
    'prefix' => 'auth',
], function () {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout')->middleware('auth');
    Route::get('info', 'AuthController@info')->middleware('auth');
});

Route::delete('deleted-books/{id}', 'BookController@forceDestroy')->name('books.force_destroy');
Route::resource('books', 'BookController');

Route::delete('deleted-notes/{id}', 'NoteController@forceDestroy')->name('notes.force_destroy');
Route::resource('notes', 'NoteController')->except('store');
Route::post('books/{book}/notes', 'NoteController@store')->name('notes.store');
