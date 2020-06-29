<?php

namespace Models;
use Library\Model as Model;


if ( ! defined('CORRECT_PATH')) exit();


/**
 *      User Model Class
 */
class UserModel extends Model {

    protected static $table = "user";

    protected   $schema = array(
        array('name' => 'id', 'type' => 'int', 'default' => null),
        array('name' => 'email', 'type' => 'varchar', 'default' => null),
        array('name' => 'name', 'type' => 'varchar', 'default' => null),
        array('name' => 'password', 'type' => 'text', 'default' => null),
        array('name' => 'token', 'type' => 'varchar', 'default' => null),
        array('name' => 'created_at', 'type' => 'datetime', 'default' => 'CURRENT_TIMESTAMP'),
    );

}