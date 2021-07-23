<?php

use Illuminate\Support\Facades\Route;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
Route::group(['middleware' => ['web', 'auth']], function () {
    Route::get('reg-validation/{userId}/{packageId}', 'F2SFund\FaceRekognition\RegistrationVideoController@showForm')->name('reg-validation');

    Route::get('withdraw-validation/{userId}', 'F2SFund\FaceRekognition\WithdrawVideoController@showForm')->name('withdraw-validation');
});

//Route::get('reg-validation/{userId}/{packageId}', 'F2SFund\FaceRekognition\RegistrationVideoController@showForm')->name('reg-validation');
//Route::get('withdraw-validation/{userId}', 'F2SFund\FaceRekognition\WithdrawVideoController@showForm')->name('withdraw-validation');

Route::post('reg-validation/{userId}/{packageId}', 'F2SFund\FaceRekognition\RegistrationVideoController@storeFaces');

Route::get('status-submit/{userId}/{faceId}', 'F2SFund\FaceRekognition\RegistrationVideoController@statusSubmit')->name('status-submit');
Route::get('attempt-error/{userId}', 'F2SFund\FaceRekognition\RegistrationVideoController@attemptsError')->name('attempt-error');
Route::post('exist-status-submit', 'F2SFund\FaceRekognition\RegistrationVideoController@existStatusSubmit')->name('exist-status-submit');

Route::post('reg-progress', 'F2SFund\FaceRekognition\RegistrationVideoController@getProgess')->name('reg-progress');
//Route::get('collection', 'F2SFund\FaceRekognition\RegistrationVideoController@collectionOperation')->name('collection');

Route::get('kyc-reject/{userId}/{faceId}/{isFaceIdRemove}', 'User\KycUtilController@rejectKYC')->name('kyc.reject');

Route::post('withdraw-validation/{userId}', 'F2SFund\FaceRekognition\WithdrawVideoController@storeFaces');

Route::get('status-withdraw-submit/{userId}', 'F2SFund\FaceRekognition\WithdrawVideoController@statusSubmit')->name('status-withdraw-submit');
