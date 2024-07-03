<?php
defined('BASEPATH') || exit('No direct script access allowed');


use chriskacerguis\RestServer\RestController;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;

class BaseRestController extends RestController
{
  const HTTP_NO_CONTENT = 204;

  const HTTP_CONFLICT = 409;
  const HTTP_GONE = 410;
  const HTTP_UNPROCESSABLE_ENTITY = 422;

  /**
   * Authorize a request.
   *
   * @return object The decoded token(payload).
   */
  protected function authorize()
  {
    try {
      $this->load->library('jwt_auth');

      // Get the authorization header.
      $authorization_header = $this->input->get_request_header('Authorization', true);

      if (empty($authorization_header)) {
        $this->response([
          'status' => false,
          'error' => 'Authorization header is missing',
        ], self::HTTP_UNAUTHORIZED);
      }

      // Extract the token from the header.
      list($token) = sscanf($authorization_header, 'Bearer %s');

      if (empty($token)) {
        $this->response([
          'status' => false,
          'error' => 'Access token is missing',
        ], self::HTTP_UNAUTHORIZED);
      }

      // Validate the access token using the JWT library.
      return $this->jwt_auth->validate_access_token($token);
    } catch (ExpiredException $e) {
      log_message('error', $e->getMessage());

      $this->response([
        'status' => false,
        'error' => 'Access token has expired',
      ], self::HTTP_UNAUTHORIZED);
    } catch (UnexpectedValueException | BeforeValidException $e) {
      log_message('error', $e->getMessage());

      $this->response([
        'status' => false,
        'error' => 'Invalid access token',
      ], self::HTTP_UNAUTHORIZED);
    } catch (Exception $e) {
      log_message('error', $e->getMessage());

      $this->response([
        'status' => false,
        'error' => 'An unexpected error occurred',
      ], self::HTTP_INTERNAL_ERROR);
    }
  }
}
