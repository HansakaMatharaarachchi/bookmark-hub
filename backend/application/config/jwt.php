<?php

defined('BASEPATH') || exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| JWT Configuration
|--------------------------------------------------------------------------
|
| These are the configuration for JWT token generation and validation.
|
| token_issuer: The issuer of the token.
| algorithm: The algorithm used to encode the token.
|
| access_token: The secret key used to encode the access token.
| access_token_expire: The expiration time of the access token in seconds.
| refresh_token: The secret key used to encode the refresh token.
| refresh_token_threshold: The threshold time to refresh the access token in seconds.
|
| Note: The secret keys should be stored in the environment variables.
|
*/
$config['token_issuer'] = base_url();
$config['algorithm'] = 'HS256';

$config['access_token_key'] = $_ENV['JWT_ACCESS_TOKEN_SECRET'];
$config['access_token_expire'] = 60 * 60; // 1 hour
$config['refresh_token_key'] = $_ENV['JWT_REFRESH_TOKEN_SECRET'];
$config['refresh_token_threshold'] = 60 * 60 * 24 * 7; // 7 days
