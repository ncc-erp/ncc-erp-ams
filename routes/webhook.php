<?php

use App\Http\Controllers\Api;

Route::group(['prefix' => 'v1/services/app', 'middleware' => ['throttle:' . config('app.api_throttle_per_minute') . ',1']], function () {
    
    Route::group(['prefix' => 'Hrmv2', 'middleware' => 'ip.restriction:hrm'], function () {
        Route::post(
            'CreateUserByHRM',
            [
                Api\Webhooks\HRMUserHookController::class,
                'createUserByHRM'
            ]
        )->name('api.hrm.createUserByHRM');

        Route::post(
            'UpdateUserByHRM',
            [
                Api\Webhooks\HRMUserHookController::class,
                'updateUserByHRM'
            ]
        )->name('api.hrm.updateUserByHRM');

        Route::post(
            'ConfirmUserQuit',
            [
                Api\Webhooks\HRMUserHookController::class,
                'confirmUserQuit'
            ]
        )->name('api.hrm.confirmUserQuit');

        Route::post(
            'ConfirmUserPause',
            [
                Api\Webhooks\HRMUserHookController::class,
                'confirmUserPause'
            ]
        )->name('api.hrm.confirmUserPause');

        Route::post(
            'ConfirmUserMaternityLeave',
            [
                Api\Webhooks\HRMUserHookController::class,
                'confirmUserMaternityLeave'
            ]
        )->name('api.hrm.confirmUserMaternityLeave');

        Route::post(
            'ConfirmUserBackToWork',
            [
                Api\Webhooks\HRMUserHookController::class,
                'confirmUserBackToWork'
            ]
        )->name('api.hrm.confirmUserBackToWork');
    }); // end Hrmv2 routes

    // Public routes
    Route::group(['prefix' => 'Public'], function () {
        Route::get(
            'CheckConnect',
            [
                Api\Webhooks\HRMUserHookController::class,
                'checkConnect'
            ]
        )->name('api.ims.checkConnect');
    }); 

});