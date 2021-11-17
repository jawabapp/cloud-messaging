<?php

use Illuminate\Support\Facades\Route;

Route::get('', 'NotificationController@index')->name('jawab.notifications.index');
Route::get('compose/{notification?}', 'NotificationController@compose')->name('jawab.notifications.compose');
Route::post('send', 'NotificationController@send')->name('jawab.notifications.send');
Route::get('show/{notification}', 'NotificationController@show')->name('jawab.notifications.show');
Route::get('delete/{notification}', 'NotificationController@delete')->name('jawab.notifications.delete');
Route::get('report', 'NotificationController@report')->name('jawab.notifications.report');
Route::get('download-cohort', 'NotificationController@downloadCohort')->name('jawab.notifications.download-cohort');

Route::group(['prefix' => 'api'], function () {
    Route::post('target-audience', 'ApiController@targetAudience');
});
