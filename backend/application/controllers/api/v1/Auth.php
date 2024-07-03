<?php
defined('BASEPATH') || exit('No direct script access allowed');

require_once APPPATH . 'core/BaseRestController.php';

use Firebase\JWT\ExpiredException;

class Auth extends BaseRestController
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('member_model');

    $this->load->helper('cookie');
  }

  public function login_post()
  {
    try {
      $email = $this->post('email', true);
      $password = $this->post('password', true);

      $member = $this->member_model->get_by_email($email);

      if (!$member) {
        $this->response([
          'status' => false,
          'message' => 'Member not found'
        ], BaseRestController::HTTP_NOT_FOUND);
      }

      if (!$this->member_model->verify_password_by_email($email, $password)) {
        $this->response([
          'status' => false,
          'message' => 'Invalid password'
        ], BaseRestController::HTTP_UNAUTHORIZED);
      }

      // Generate access token.
      $access_token = $this->generate_access_token($member->member_id);

      // Generate and set refresh token.
      $this->generate_and_set_refresh_token($member->member_id);

      $this->response([
        'status' => true,
        'message' => 'Login successful',
        'data' => [
          'access_token' => $access_token,
        ]
      ], BaseRestController::HTTP_OK);
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', 'Failed to login user: ' . $email);

      $this->response([
        'status' => false,
        'message' => 'Failed to login'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  public function refresh_post()
  {
    try {
      $refresh_token = get_cookie('jrt');

      if (!$refresh_token) {
        $this->response([
          'status' => false,
          'message' => 'Refresh token not found'
        ], BaseRestController::HTTP_UNAUTHORIZED);
      }

      // Validate refresh token.
      $refresh_token_data = $this->jwt_auth->validate_refresh_token($refresh_token);

      // Generate new access token.
      $new_access_token = $this->generate_access_token($refresh_token_data->sub);

      // Rotate refresh token.
      $this->generate_and_set_refresh_token($refresh_token_data->sub);

      $this->response([
        'status' => true,
        'message' => 'Token refreshed',
        'data' => [
          'access_token' => $new_access_token,
        ]
      ], BaseRestController::HTTP_OK);
    } catch (ExpiredException $e) {
      log_message('error', 'Refresh token has expired: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => 'Refresh token has expired'
      ], BaseRestController::HTTP_UNAUTHORIZED);
    } catch (Exception $e) {
      log_message('error', 'Failed to refresh token: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => 'Failed to refresh token',
      ], BaseRestController::HTTP_UNAUTHORIZED);
    }
  }

  public function logout_post()
  {
    try {
      delete_cookie('jrt');

      $this->response([
        'status' => true,
        'message' => 'Logout successful',
      ], BaseRestController::HTTP_OK);
    } catch (Exception $e) {
      log_message('error', 'Failed to logout: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => 'Failed to logout',
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  /**
   * Generate access token.
   *
   * @param string $member_id
   * @return string Access token
   */
  private function generate_access_token($member_id)
  {
    return $this->jwt_auth->generate_access_token([
      'sub' => $member_id,
    ]);
  }

  /**
   * Generate and set refresh token as a cookie.
   *
   * @param string $member_id
   */
  private function generate_and_set_refresh_token($member_id)
  {
    [
      'token' => $refresh_token,
      'expiration' => $expiration_time
    ] = $this->jwt_auth->generate_refresh_token([
      'sub' => $member_id,
    ]);

    /*
      ! This is a workaround for CHIPS to work.
      ! codeigniter 3 cookies does not support SameSite=None; Partitioned attribute.
      https://github.com/php/php-src/issues/12646
    */
    setcookie('jrt', $refresh_token, [
      'path' => '/',
      'expires' => $expiration_time,
      'httponly' => true,
      'secure' => true,
      'samesite' => 'None; Partitioned'
    ]);
  }
}
