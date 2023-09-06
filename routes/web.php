<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/statistic/class', 'StatisticController@getClass');
$router->get('/statistic/sub-class/{class}', 'StatisticController@getSubClass');
$router->get('/statistic/unit', 'StatisticController@getUnit');
$router->get('/statistic/date', 'StatisticController@getDate');

$router->get('/content/search', 'ContentController@search');
$router->get('/content/detail/{id}', 'ContentController@detail');