<?php
defined('BASEPATH') || exit('No direct script access allowed');

require_once APPPATH . 'core/BaseRestController.php';

class Members extends BaseRestController
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('member_model');
  }

  public function index_post()
  {
    try {
      $email = $this->post('email', true);
      $nickname = $this->post('nickname', true);
      $password = $this->post('password', true);

      $new_member_id = $this->member_model->create($email, $nickname, $password);
      log_message('debug', 'New member created: ' . $new_member_id);

      $new_member = $this->member_model->get_by_id($new_member_id);

      $this->response([
        'status' => true,
        'message' => 'Member created successfully.',
        'data' => $new_member
      ], BaseRestController::HTTP_CREATED);
    } catch (DuplicateException $e) {
      log_message('error', 'Duplicate member: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_CONFLICT);
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', 'Failed to create member: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => 'Failed to create member.'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  public function me_get()
  {
    try {
      $authenticated_member_id = $this->authorize()->sub;

      $authenticated_member = $this->member_model->get_by_id($authenticated_member_id);

      if (!empty($authenticated_member)) {
        log_message('debug', 'Member found: ' . $authenticated_member->member_id);

        $this->response([
          'status' => true,
          'message' => 'Member found.',
          'data' => $authenticated_member
        ], BaseRestController::HTTP_OK);
      } else {
        log_message('error', 'Member not found: ' . $authenticated_member_id);

        $this->response([
          'status' => false,
          'message' => 'Member not found.'
        ], BaseRestController::HTTP_NOT_FOUND);
      }
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', 'Failed to get member: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => 'Failed to get member.'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  public function me_delete()
  {
    try {
      $authenticated_member_id = $this->authorize()->sub;

      $this->member_model->delete_by_id($authenticated_member_id);
      log_message('debug', 'Member deleted: ' . $authenticated_member_id);

      $this->response(null, BaseRestController::HTTP_NO_CONTENT);
    } catch (NotFoundException $e) {
      log_message('error', 'Member not found: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_GONE);
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', 'Failed to delete member: ' . $e->getMessage());

      $this->response([
        'status' => false,
        'message' => 'Failed to delete member.'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }
}
