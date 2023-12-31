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
$router->get('/statistic/sub-class', 'StatisticController@getSubClass');
$router->get('/statistic/sub-class-all', 'StatisticController@getSubClassAll');
$router->get('/statistic/unit', 'StatisticController@getUnit');
$router->get('/statistic/date', 'StatisticController@getDate');

$router->get('/content/search', 'ContentController@search');
$router->get('/content/detail/{id}', 'ContentController@detail');
$router->get('/content/get-subclass-desc/{sub}', 'ContentController@getSubClassDesc');

$router->get('/user/detail', 'UserController@detail');
$router->get('/user/content', 'UserController@getContent');