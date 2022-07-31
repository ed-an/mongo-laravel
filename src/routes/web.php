<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', 'PostController@home')->middleware(['auth'])->name('home');
Route::get('/posts/create','PostController@createForm')->middleware(['auth'])->name('post.form');
Route::post('/posts/create','PostController@save')->middleware(['auth'])->name('post.save');

Route::get('/posts/{id}/edit',  'PostController@editForm')->middleware(['auth'])->name('post.edit.form');
Route::post('/posts/delete',  'PostController@delete')->middleware(['auth'])->name('post.delete');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';
