<?php

namespace Controllers;
use Library\Controller;
use Library\RestController;
use Library\Log;
use Models\UserModel;
use Models\PostModel;
use Models\PostCategoryModel;
use Controllers\UserController;


if ( ! defined('CORRECT_PATH')) exit();


/**
 *      Home Controller Class
 */
class HomeController extends Controller {

    /**
     *      GET /
     */
    public function index()
    {
        $this->_getView()
            ->setData('body_class_name', 'home-body')
            ->setData('flagTopImage', true)
            ->setPage('home/index');
    }

}