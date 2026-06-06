<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use TCG\Voyager\Events\Routing;
use TCG\Voyager\Events\RoutingAdmin;
use TCG\Voyager\Events\RoutingAdminAfter;
use TCG\Voyager\Events\RoutingAfter;
use TCG\Voyager\Facades\Voyager;

/*
|--------------------------------------------------------------------------
| Voyager Routes
|--------------------------------------------------------------------------
|
| This file is where you may override any of the routes that are included
| with Voyager.
|
*/

Route::group(['as' => 'voyager.'], function () {
    event(new Routing());

    $namespacePrefix = '\\'.config('voyager.controllers.namespace').'\\';

    $authController = $namespacePrefix.'VoyagerAuthController';
    $voyagerController = $namespacePrefix.'VoyagerController';
    $userController = $namespacePrefix.'VoyagerUserController';
    $menuController = $namespacePrefix.'VoyagerMenuController';
    $settingsController = $namespacePrefix.'VoyagerSettingsController';
    $mediaController = $namespacePrefix.'VoyagerMediaController';
    $breadController = $namespacePrefix.'VoyagerBreadController';
    $databaseController = $namespacePrefix.'VoyagerDatabaseController';
    $compassController = $namespacePrefix.'VoyagerCompassController';

    Route::get('login', [$authController, 'login'])->name('login');
    Route::post('login', [$authController, 'postLogin'])->name('postlogin');

    Route::group(['middleware' => 'admin.user'], function () use (
        $namespacePrefix,
        $voyagerController,
        $userController,
        $menuController,
        $settingsController,
        $mediaController,
        $breadController,
        $databaseController,
        $compassController
    ) {
        event(new RoutingAdmin());

        // Main Admin and Logout Route
        Route::get('/', [$voyagerController, 'index'])->name('dashboard');
        Route::post('logout', [$voyagerController, 'logout'])->name('logout');
        Route::post('upload', [$voyagerController, 'upload'])->name('upload');

        Route::get('profile', [$userController, 'profile'])->name('profile');

        try {
            foreach (Voyager::model('DataType')::all() as $dataType) {
                $breadTypeController = $dataType->controller
                                 ? Str::start($dataType->controller, '\\')
                                 : $namespacePrefix.'VoyagerBaseController';

                Route::get($dataType->slug.'/order', [$breadTypeController, 'order'])->name($dataType->slug.'.order');
                Route::post($dataType->slug.'/action', [$breadTypeController, 'action'])->name($dataType->slug.'.action');
                Route::post($dataType->slug.'/order', [$breadTypeController, 'update_order'])->name($dataType->slug.'.update_order');
                Route::get($dataType->slug.'/{id}/restore', [$breadTypeController, 'restore'])->name($dataType->slug.'.restore');
                Route::get($dataType->slug.'/relation', [$breadTypeController, 'relation'])->name($dataType->slug.'.relation');
                Route::post($dataType->slug.'/remove', [$breadTypeController, 'remove_media'])->name($dataType->slug.'.media.remove');
                Route::resource($dataType->slug, $breadTypeController, ['parameters' => [$dataType->slug => 'id']]);
            }
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Custom routes hasn't been configured because: ".$e->getMessage(), 1);
        } catch (\Exception $e) {
            // do nothing, might just be because table not yet migrated.
        }

        // Menu Routes
        Route::group([
            'as'     => 'menus.',
            'prefix' => 'menus/{menu}',
        ], function () use ($menuController) {
            Route::get('builder', [$menuController, 'builder'])->name('builder');
            Route::post('order', [$menuController, 'order_item'])->name('order_item');

            Route::group([
                'as'     => 'item.',
                'prefix' => 'item',
            ], function () use ($menuController) {
                Route::delete('{id}', [$menuController, 'delete_menu'])->name('destroy');
                Route::post('/', [$menuController, 'add_item'])->name('add');
                Route::put('/', [$menuController, 'update_item'])->name('update');
            });
        });

        // Settings
        Route::group([
            'as'     => 'settings.',
            'prefix' => 'settings',
        ], function () use ($settingsController) {
            Route::get('/', [$settingsController, 'index'])->name('index');
            Route::post('/', [$settingsController, 'store'])->name('store');
            Route::put('/', [$settingsController, 'update'])->name('update');
            Route::delete('{id}', [$settingsController, 'delete'])->name('delete');
            Route::get('{id}/move_up', [$settingsController, 'move_up'])->name('move_up');
            Route::get('{id}/move_down', [$settingsController, 'move_down'])->name('move_down');
            Route::put('{id}/delete_value', [$settingsController, 'delete_value'])->name('delete_value');
        });

        // Admin Media
        Route::group([
            'as'     => 'media.',
            'prefix' => 'media',
        ], function () use ($mediaController) {
            Route::get('/', [$mediaController, 'index'])->name('index');
            Route::post('files', [$mediaController, 'files'])->name('files');
            Route::post('new_folder', [$mediaController, 'new_folder'])->name('new_folder');
            Route::post('delete_file_folder', [$mediaController, 'delete'])->name('delete');
            Route::post('move_file', [$mediaController, 'move'])->name('move');
            Route::post('rename_file', [$mediaController, 'rename'])->name('rename');
            Route::post('upload', [$mediaController, 'upload'])->name('upload');
            Route::post('crop', [$mediaController, 'crop'])->name('crop');
        });

        // BREAD Routes
        Route::group([
            'as'     => 'bread.',
            'prefix' => 'bread',
        ], function () use ($breadController) {
            Route::get('/', [$breadController, 'index'])->name('index');
            Route::get('{table}/create', [$breadController, 'create'])->name('create');
            Route::post('/', [$breadController, 'store'])->name('store');
            Route::get('{table}/edit', [$breadController, 'edit'])->name('edit');
            Route::put('{id}', [$breadController, 'update'])->name('update');
            Route::delete('{id}', [$breadController, 'destroy'])->name('delete');
            Route::post('relationship', [$breadController, 'addRelationship'])->name('relationship');
            Route::get('delete_relationship/{id}', [$breadController, 'deleteRelationship'])->name('delete_relationship');
        });

        // Database Routes
        Route::resource('database', $databaseController);

        // Compass Routes
        Route::group([
            'as'     => 'compass.',
            'prefix' => 'compass',
        ], function () use ($compassController) {
            Route::get('/', [$compassController, 'index'])->name('index');
            Route::post('/', [$compassController, 'index'])->name('post');
        });

        event(new RoutingAdminAfter());
    });

    //Asset Routes
    Route::get('voyager-assets', [$voyagerController, 'assets'])->name('voyager_assets');

    event(new RoutingAfter());
});
