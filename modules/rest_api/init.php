<?php defined('SYSPATH') or die('No direct script access.');

// API routing
$api_config = Kohana::config('api');

Kohana::$log->add('debug', '('.implode('|', $api_config->models).')');

// find single instance of a model
// ie: event/5
//     tag/123
Route::set('api', 'v'.$api_config->version.'/<controller>(/<id>)',
    array(
        'controller' => '('.implode('|', $api_config->models).')',
        'id' => '\d*'
    ))
    ->defaults(array(
        'action' => 'index'
    ));

// default controller/action routing for API
// ie: event/index
//     tag/create/5
//     image/delete/170
//     photo_gallery/update/10
Route::set('default_api', 'v'.$api_config->version.'/<controller>(/<action>(/<id>))')
	->defaults(array(
		'action'     => 'index',
	));
