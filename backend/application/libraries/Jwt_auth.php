<?php
defined('BASEPATH') || exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;

class Jwt_auth
{
  private $ci;

  public function __construct()
  {
    $this->ci = &get_instance();
    $this->ci->load->config('jwt', true);
  }

  /**
   * Generate an access token.
   *
   * @param array $customClaims Custom claims to be added to the token.
   * @return string The generated access token.
   */
  public function generate_access_token($customClaims = [])
  {
    $token_key = $this->ci->config->item('access_token_key', 'jwt');
    $token_issuer = $this->ci->config->item('token_issuer', 'jwt');
    $token_expire = $this->ci->config->item('access_token_expire', 'jwt');
    $algorithm = $this->ci->config->item('algorithm', 'jwt');

    $issued_at = time();
    $expiration_time = $issued_at + $token_expire;

    $token = array(
      ...$customClaims,
      'iss' => $token_issuer,
      'iat' => $issued_at,
      'exp' => $expiration_time,
    );

    return JWT::encode($token, $token_key, $algorithm);
  }

  /**
   * Validate the access token.
   *
   * @param string $token The access token to be validated.
   * @return object The decoded token.
   * @throws Exception If the token is invalid.
   */
  public function validate_access_token($token)
  {
    $decoded = $this->decode_access_token($token);
    $this->verify_token_claims($decoded);

    return $decoded;
  }

  /**
   * Decode the access token.
   *
   * @param string $token The access token to be decoded.
   * @return object The decoded token.
   * @throws Exception If the token is invalid.
   */
  private function decode_access_token($token)
  {
    $token_key = $this->ci->config->item('access_token_key', 'jwt');
    $algorithm = $this->ci->config->item('algorithm', 'jwt');

    return JWT::decode($token, new Key($token_key, $algorithm));
  }

  /**
   * Generate a refresh token.
   *
   * @param array $data The data to be added to the token.
   * @return array The generated refresh token and its expiration time.
   * @throws Exception If the token is invalid.
   */
  public function generate_refresh_token($data)
  {
    $token_key = $this->ci->config->item('refresh_token_key', 'jwt');
    $token_issuer = $this->ci->config->item('token_issuer', 'jwt');
    $refresh_threshold = $this->ci->config->item('refresh_token_threshold', 'jwt');
    $algorithm = $this->ci->config->item('algorithm', 'jwt');

    $issued_at = time();
    $expiration_time = $issued_at + $refresh_threshold;

    $token_payload = array(
      'iss' => $token_issuer,
      ...$data,
      'iat' => $issued_at,
      'exp' => $expiration_time,
    );

    $token = JWT::encode($token_payload, $token_key, $algorithm);

    return (object) [
      'token' => $token,
      'expire_at' => $expiration_time,
    ];
  }

  /**
   * Validate the refresh token.
   *
   * @param string $token The refresh token to be validated.
   * @return object The decoded token.
   * @throws Exception If the token is invalid.
   */
  public function validate_refresh_token($token)
  {
    $decoded = $this->decode_refresh_token($token);
    $this->verify_token_claims($decoded);

    return $decoded;
  }

  /**
   * Decode the refresh token.
   *
   * @param string $token The refresh token to be decoded.
   * @return object The decoded token.
   * @throws Exception If the token is invalid.
   */
  private function decode_refresh_token($token)
  {
    $token_key = $this->ci->config->item('refresh_token_key', 'jwt');
    $algorithm = $this->ci->config->item('algorithm', 'jwt');

    return JWT::decode($token, new Key($token_key, $algorithm));
  }

  /**
   * Verify the token claims.
   *
   * @param object $decoded_token The decoded token.
   * @throws UnexpectedValueException If the token issuer is invalid.
   * @throws BeforeValidException If the token is not valid yet.
   * @throws ExpiredException If the token is expired.
   * @throws Exception If the token is invalid.
   * @return void
   */
  private function verify_token_claims($decoded_token)
  {
    $token_issuer = $this->ci->config->item('token_issuer', 'jwt');

    // Check if the token issuer is valid.
    if ($decoded_token->iss !== $token_issuer) {
      throw new UnexpectedValueException('Invalid token issuer');
    }

    // Check if the token is not before the issued time.
    if ($decoded_token->iat > time()) {
      throw new BeforeValidException('Token is not valid yet');
    }

    // Check if the token is expired.
    if ($decoded_token->exp < time()) {
      throw new ExpiredException('Token has expired');
    }
  }
}
