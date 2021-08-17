<?php

use Illuminate\Support\Facades\Route;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('reg-validation/{userId}/{packageId}', 'W3Engineers\FaceRekognition\RegistrationVideoController@showForm')->name('reg-validation');

    Route::get('withdraw-validation/{userId}', 'W3Engineers\FaceRekognition\WithdrawVideoController@showForm')->name('withdraw-validation');
});

//Route::get('reg-validation/{userId}/{packageId}', 'W3Engineers\FaceRekognition\RegistrationVideoController@showForm')->name('reg-validation');
//Route::get('withdraw-validation/{userId}', 'W3Engineers\FaceRekognition\WithdrawVideoController@showForm')->name('withdraw-validation');

Route::post('reg-validation/{userId}/{packageId}', 'W3Engineers\FaceRekognition\RegistrationVideoController@storeFaces');

Route::get('status-submit/{userId}/{faceId}', 'W3Engineers\FaceRekognition\RegistrationVideoController@statusSubmit')->name('status-submit');
Route::get('attempt-error/{userId}', 'W3Engineers\FaceRekognition\RegistrationVideoController@attemptsError')->name('attempt-error');
Route::post('exist-status-submit', 'W3Engineers\FaceRekognition\RegistrationVideoController@existStatusSubmit')->name('exist-status-submit');

Route::post('reg-progress', 'W3Engineers\FaceRekognition\RegistrationVideoController@getProgess')->name('reg-progress');
//Route::get('collection', 'W3Engineers\FaceRekognition\RegistrationVideoController@collectionOperation')->name('collection');

Route::get('kyc-reject/{userId}/{faceId}/{isFaceIdRemove}', 'User\KycUtilController@rejectKYC')->name('kyc.reject');

Route::post('withdraw-validation/{userId}', 'W3Engineers\FaceRekognition\WithdrawVideoController@storeFaces');

Route::get('status-withdraw-submit/{userId}', 'W3Engineers\FaceRekognition\WithdrawVideoController@statusSubmit')->name('status-withdraw-submit');
