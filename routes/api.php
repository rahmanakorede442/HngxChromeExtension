<?php

use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::get('/video', function (){
//     return 'Hi there, Hello Worldsdfd';
// });


Route::post('/video',[VideoController::class,'store'])->name('video.store');
Route::get('/video',[VideoController::class,'index'])->name('video.index');
Route::get('/video/{id}',[VideoController::class,'transcribe'])->name('video.transcribe');
Route::delete('/video/{id}',[VideoController::class,'destroy'])->name('video.destroy');
