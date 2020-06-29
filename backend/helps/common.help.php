<?php

    if ( ! defined('CORRECT_PATH')) exit();

/**********************************************************************************************
 *
 *      common functions
 *
 *      This contains some functions like the following.
 *      encrypt functions - id=>encrypt_id, api_key=>encrypt_api_key, etc
 *      token generator function
 *
**********************************************************************************************/


/**
 *      base64UrlEncode function
 *      This way we can pass the string within URLs without any URL encoding.
 *      @param  string  $text
 *      @return string
 */
function base64UrlEncode($text)
{
    return str_replace(
        ['+', '/', '='],
        ['-', '_', ''],
        base64_encode($text)
    );
}

/**
 *      generate JSON Web Token
 *      @param  mixed   $data
 *      @return string
 */
function generateJWT($data)
{
    // get the local secret key
    $secret = ($GLOBALS['CONFIG']->getConfig('SECRET_KEY')) ? $GLOBALS['CONFIG']->getConfig('SECRET_KEY') : "";

    // Create token header as a JSON string
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    // add expire date
    $data['exp_date'] = time() + TOKEN_EXP_DATE_LIMIT;
    // Create token payload as a JSON string
    $payload = json_encode($data);

    // Encode Header
    $base64UrlHeader = base64UrlEncode($header);
    // Encode Payload
    $base64UrlPayload = base64UrlEncode($payload);

    // Create Signature Hash
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    // Encode Signature to Base64Url String
    $base64UrlSignature = base64UrlEncode($signature);

    // Create JWT
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    return $jwt;
}

/**
 *      validate JSON Web Token
 *      @param  string  $jwt
 *      @return mixed
 */
function validateJWT($jwt)
{
    // get the local secret key
    $secret = ($GLOBALS['CONFIG']->getConfig('SECRET_KEY')) ? $GLOBALS['CONFIG']->getConfig('SECRET_KEY') : "";

    // split the token
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) != 3) return null;
    $header = base64_decode($tokenParts[0]);
    $payload = base64_decode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];

    // build a signature based on the header and payload using the secret
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = base64UrlEncode($signature);

    // verify it matches the signature provided in the token
    if ($base64UrlSignature === $signatureProvided) {
        try {
            $data = json_decode($payload, true);
            // verify expire date
            $data['exp_verify'] = ($data['exp_date'] > time()) ? true : false;
        }
        catch(Exception $e) {
            $data = $payload;
        }
        return $data;
    }
    else return null;
}

/**
 *      generate API Token
 *      @param  int     $len
 *      @return string
 */
function generateAPIToken($len=32)
{
    return bin2hex(random_bytes($len));
}

/**
 *      validate name
 *      @param  string  $str
 *      @return bool
 */
function validateName($str)
{
    return (preg_match("/^[a-zA-Z ]*$/", $str));
}

/**
 *      validate email
 *      @param  string  $email
 *      @return bool
 */
function validateEmail($email)
{
    return (filter_var($email, FILTER_VALIDATE_EMAIL));
}

/**
 *      validate URL
 *      @param  string  $website
 *      @return bool
 */
function validateUrl($website)
{
    return (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $website));
}

/**
 *      validate password
 *      @param  string  $password
 *      @return bool
 */
function validatePassword($password)
{
    // Validate password strength
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);
    return ($uppercase && $lowercase && $number && $specialChars && strlen($password) >= 8);
}