<?php

    if ( ! defined('CORRECT_PATH')) exit();

/**********************************************************************************************
 *
 *      router
 *
 *      define Router class object
 *      routing...
 *
**********************************************************************************************/


use Library\Route as Route;


$router = Route::getInstance();


$router->get('', 'Home@index');
$router->get('/login', 'User@login');
$router->get('/signup', 'User@signup');
$router->get('/logout', 'User@logout');
$router->get('/forgot-password', 'User@forgotPassword');
$router->post('/login', 'User@signin');
$router->post('/signup', 'User@register');
$router->get('/test/{id}/{type}', 'Home@test', 'User@isLogin');
$router->rest('post', 'Post', 'User@isLogin');

// TODO
// API router