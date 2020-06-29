<?php

namespace Controllers;
use Library\Controller as Controller;
use Library\Log as Log;
use Models\UserModel;


if ( ! defined('CORRECT_PATH')) exit();


/**
 *      User Controller Class
 */
class UserController extends Controller {

    /**
     *      middleware to check login
     *      @return bool
     */
    public function isLogin()
    {
        return ($this->getUserFromSessionOrHeader() ? true : false);
    }

    /**
     *      generate API Token from user
     *      @param  UserModel   $user
     *      @return bool
     */
    public function generateAPITokenFromUser($user)
    {
        $api_token = generateJWT(array(
            'id' => $user->getAttribute('id'),
            'email' => $user->getAttribute('email'),
            'token' => $user->getAttribute('token'),
        ));
        $this->_getSession()->authorized = $api_token;
        return $api_token;
    }

    /**
     *      get user from session
     *      @return array
     */
    public function getUserFromSessionOrHeader()
    {
        if ($this->_getSession()->authorized) {
            // check session
            $jwt = $this->_getSession()->authorized;
        }
        else if ($this->_getRequest()->getHeader('Authorization')) {
            // check header
            $jwt = $this->_getRequest()->getHeader('Authorization');
            // bearer token
            if (preg_match('/Bearer\s(\S+)/', $jwt, $matches)) {
                $jwt = $matches[1];
            }
        }

        if ($jwt) {
            $json = validateJWT($jwt);
            if ($json) {
                if (array_key_exists('id', $json) 
                    && array_key_exists('email', $json) 
                    && array_key_exists('token', $json)
                    && (array_key_exists('exp_verify', $json) && $json['exp_verify'])) {
                        $user = UserModel::find($json['id']);
                        // check user email & token
                        if ($user->isExist()) {
                            if ($user->getAttribute('email') == $json['email']
                                && $user->getAttribute('token') == $json['token']) {
                                    // success
                                    return $user;
                            }
                        }
                }
            }
        }
        return null;
    }

    /**
     *      GET /login
     */
    public function login()
    {
        $this->_setErrorCodesEnabled(array(
            'ERR1001', 'ERR1005',  'ERR1006',
        ));
        $this->_getView()
            ->setData('body_class_name', 'login-body')
            ->setLayout('basic')
            ->setData('title', 'Log In')
            ->setPage('user/login');
    }

    /**
     *      GET /signup
     */
    public function signup()
    {
        $this->_setErrorCodesEnabled(array(
            'ERR1001', 'ERR1002', 'ERR1003', 'ERR1004'
        ));
        $this->_getView()
            ->setData('body_class_name', 'login-body')
            ->setLayout('basic')
            ->setData('title', 'Sign Up')
            ->setPage('user/signup');
    }

    /**
     *      POST /login
     */
    public function signin()
    {
        $email = $this->_getRequest()->getParameter('email', 'POST');
        $password = $this->_getRequest()->getParameter('password', 'POST');
        $remember = $this->_getRequest()->getParameter('check-remember', 'POST');
        Log::addLog("email is " . $email . ", password is " . $password . ", remember is " . $remember);

        // validate
        if(!validateEmail($email)) {
            $this->_getResponse()->redirect('/login?error=ERR1001');
        }
        else {
            // find user
            $user = UserModel::where("`email` LIKE '".$email."'")->get();
            if (!$user->isExist()) {
                $this->_getResponse()->redirect('/login?error=ERR1005');
            }
            else {
                // check password
                $hashed_password = $user->getAttribute('password');
                if (!password_verify($password, $hashed_password)) {
                    $this->_getResponse()->redirect('/login?error=ERR1006');
                }
                else {
                    // session
                    $api_token = $this->generateAPITokenFromUser($user);
                    // If the password inputs matched the hashed password in the database
                    $this->_getResponse()->redirect('/');
                }
            }
        } 
    }

    /**
     *      POST /signup
     */
    public function register()
    {
        $email = $this->_getRequest()->getParameter('email', 'POST');
        $password = $this->_getRequest()->getParameter('password', 'POST');
        $rePassword = $this->_getRequest()->getParameter('re-password', 'POST');

        // validate
        if (!validateEmail($email)) {
            $this->_getResponse()->redirect('/signup?error=ERR1001');
        }
        else if ($password != $rePassword) {
            $this->_getResponse()->redirect('/signup?error=ERR1002');
        }
        else if (!validatePassword($password)) {
            $this->_getResponse()->redirect('/signup?error=ERR1003');
        }
        else {
            // create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $token = generateAPIToken();
            $user = UserModel::create(array(
                'email' => $email,
                'name' => "",
                'password' => $hashed_password,
                'token' => $token,
            ));
            if (!$user->save()) {
                $this->_getResponse()->redirect('/signup?error=ERR1004');
            }
            else {
                // session
                $api_token = $this->generateAPITokenFromUser($user);
                // success register
                $this->_getResponse()->redirect('/');
            }
        }
    }

    /**
     *      GET /logout
     */
    public function logout()
    {
        // session
        $this->_getSession()->destroy();
        $this->_getResponse()
            ->redirect('/');
    }

    /**
     *      GET /forgot-password
     */
    public function forgotPassword()
    {
        // TODO
        $this->_getResponse()
            ->redirect('/');
    }

}