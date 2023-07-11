<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/ping', ['as' => 'ping', 'uses' => 'UserController@ping']);

$router->group(['prefix' => 'oauth'], function () use ($router) {
	$router->post('/login', ['as' => 'login', 'uses' => 'AuthController@login']);

	$router->post('/logout', ['as' => 'logout', 'uses' => 'AuthController@logout']);
});

$router->group(['middleware' => 'auth'], function () use ($router) {
	$router->group(['prefix' => 'user'], function () use ($router) {
		$router->get('/get', ['uses' => 'UserController@index']);
	});

	$router->group(['prefix' => 'buildings'], function () use ($router) {
		$router->get('/get', ['uses' => 'BuildingController@index']);
	});

	$router->group(['prefix' => 'services'], function () use ($router) {
		$router->get('/get', ['uses' => 'ServiceController@index']);
		$router->get('/get/building/{buildingId:[0-9]+}', ['uses' => 'ServiceController@getBuildingServices']);

		$router->patch('/set', ['uses' => 'ServiceController@setDim']);
		$router->patch('/set/group', ['uses' => 'ServiceController@setGroupDim']);
	});

	$router->group(['prefix' => 'thermostats'], function () use ($router) {
		$router->get('/get', ['uses' => 'ThermostatController@index']);
		$router->get('/get/{thID:[0-9]+}', ['uses' => 'ThermostatController@findOne']);
		$router->get('/get/building/{buildingId:[0-9]+}', ['uses' => 'ThermostatController@getBuildingThermostats']);

		$router->patch('/update/{thID:[0-9]+}', ['uses' => 'ThermostatController@update']);
	});

	$router->group(['prefix' => 'scenes'], function () use ($router) {
		$router->get('/get', ['uses' => 'SceneController@index']);
		$router->get('/publish/{sceneId:[0-9]+}', ['uses' => 'SceneController@publish']);

		$router->group(['prefix' => 'jobs'], function () use ($router) {
			$router->get('/get', ['uses' => 'SceneJobController@index']);
			$router->get('/get', ['uses' => 'SceneJobController@index']);

			$router->post('/save', ['uses' => 'SceneJobController@store']);
			$router->delete('/{jobId:[0-9]+}', ['uses' => 'SceneJobController@destroy']);
		});
	});

	$router->group(['prefix' => 'moods'], function () use ($router) {
		$router->get('/get', ['uses' => 'MoodController@index']);

		$router->post('/{buildingId:[0-9]+}', ['uses' => 'MoodController@store']);
		$router->patch('/set/{moodId:[0-9]+}', ['uses' => 'MoodController@update']);
		$router->delete('/{moodId:[0-9]+}', ['uses' => 'MoodController@destroy']);
	});

	$router->group(['prefix' => 'jobs'], function () use ($router) {
		$router->get('/get', ['uses' => 'JobController@index']);

		$router->post('/{type:mood|scene}/{jobId:[0-9]+}', ['uses' => 'JobController@store']);
		$router->delete('/{jobId:[0-9]+}', ['uses' => 'JobController@destroy']);
	});

	$router->group(['prefix' => 'mqtt'], function () use ($router) {
		$router->patch('/set', ['uses' => 'UserController@updateMqttInformation']);
	});
});
