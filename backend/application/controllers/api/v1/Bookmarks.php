<?php
defined('BASEPATH') || exit('No direct script access allowed');

require_once APPPATH . 'core/BaseRestController.php';

class Bookmarks extends BaseRestController
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('bookmark_model');
  }

  public function index_get()
  {
    try {
      $authenticated_member_id = $this->authorize()->sub;

      $search = $this->get('search', true);
      $tags = $this->get('tags', true);
      $limit = $this->get('limit', true) ? intval($this->get('limit', true)) : 10;
      $offset = $this->get('offset', true) ? intval($this->get('offset', true)) : 0;

      // Prepare filters.
      $filters = [];

      if (!empty($tags)) {
        $filters['tags'] = explode(',', $tags);
      }

      if (!empty($search)) {
        $filters['search'] = $search;
      }

      // Get Filtered Bookmarks.
      [
        'bookmarks' => $filtered_bookmarks,
        'total_bookmarks_count' => $total_bookmarks_count
      ] =
        $this->bookmark_model->get_bookmarks_by_member_id($authenticated_member_id, $filters, $limit, $offset);

      log_message('debug', 'Bookmarks retrieved: ' . json_encode($filtered_bookmarks));

      // Get tags for each filtered bookmark.
      foreach ($filtered_bookmarks as &$bookmark) {
        $bookmark['tags'] = $this->bookmark_model->get_tags_for_bookmark($bookmark['bookmark_id']);
      }

      $this->response([
        'status' => true,
        'data' => [
          'bookmarks' => $filtered_bookmarks,
          'total_bookmarks_count' => $total_bookmarks_count,
          'limit' => $limit,
          'offset' => $offset
        ]
      ]);
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', "Error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => 'Bookmarks retrieval failed'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  public function bookmark_get()
  {
    try {
      $authenticated_member_id = $this->authorize()->sub;

      $bookmark_id = $this->uri->segment(4);

      $bookmark = $this->bookmark_model->get_bookmark_by_id($authenticated_member_id, $bookmark_id);

      if (empty($bookmark)) {
        $this->response([
          'status' => false,
          'message' => 'Bookmark not found'
        ], BaseRestController::HTTP_NOT_FOUND);
      }

      $bookmark['tags'] = $this->bookmark_model->get_tags_for_bookmark($bookmark_id);

      log_message('debug', 'Bookmark retrieved: ' . json_encode($bookmark));

      $this->response([
        'status' => true,
        'data' => $bookmark
      ]);
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', "Error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => 'Bookmark retrieval failed'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  public function index_post()
  {
    try {
      $authenticated_member_id = $this->authorize()->sub;

      $title = $this->post('title', true);
      $url = $this->post('url', true);
      $tag_names = $this->post('tags', true);

      $new_bookmark_id = $this->bookmark_model->add_bookmark(
        $authenticated_member_id,
        $title,
        $url,
        $tag_names
      );

      $new_bookmark = $this->bookmark_model->get_bookmark_by_id($authenticated_member_id, $new_bookmark_id);

      log_message('debug', 'Bookmark created: ' . json_encode($new_bookmark));

      $this->response([
        'status' => true,
        'message' => 'Bookmark created',
        'data' => $new_bookmark
      ], BaseRestController::HTTP_CREATED);
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', "Error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => 'Bookmark creation failed'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  public function index_patch()
  {
    try {
      $authenticated_member_id = $this->authorize()->sub;
      $bookmark_id = $this->uri->segment(4);

      $title = $this->patch('title', true);
      $url = $this->patch('url', true);
      $tag_names = $this->patch('tags', true);

      $this->bookmark_model->patch_bookmark(
        $authenticated_member_id,
        $bookmark_id,
        $title,
        $url,
        $tag_names
      );

      $new_bookmark = $this->bookmark_model->get_bookmark_by_id($authenticated_member_id, $bookmark_id);

      log_message('debug', 'Bookmark updated: ' . json_encode($new_bookmark));

      $this->response([
        'status' => true,
        'message' => 'Bookmark updated',
        'data' => $new_bookmark
      ], BaseRestController::HTTP_OK);
    } catch (NotFoundException $e) {
      log_message('error', "Not found: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_NOT_FOUND);
    } catch (ValidationException $e) {
      log_message('error', "Validation error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => $e->getMessage()
      ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
    } catch (Exception $e) {
      log_message('error', "Error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => 'Bookmark update failed'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }

  public function index_delete()
  {
    try {
      $authenticated_member_id = $this->authorize()->sub;
      $bookmark_id = $this->uri->segment(4);

      $this->bookmark_model->delete_bookmark($authenticated_member_id, $bookmark_id);

      log_message('debug', 'Bookmark deleted: ' . $bookmark_id);

      $this->response([
        'status' => true,
        'message' => 'Bookmark deleted'
      ]);
    } catch (NotFoundException $e) {
      log_message('error', "Not found: {$e->getMessage()}");

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
      log_message('error', "Error: {$e->getMessage()}");

      $this->response([
        'status' => false,
        'message' => 'Bookmark deletion failed'
      ], BaseRestController::HTTP_INTERNAL_ERROR);
    }
  }
}
