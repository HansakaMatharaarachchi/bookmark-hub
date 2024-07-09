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

  // public function index_post()
  // {
  //   $authenticated_member_id = $this->authorize()->sub;

  //   try {
  //     $title = $this->post('title', true);
  //     $url = $this->post('url', true);
  //     $tag_names = $this->post('tags', true);

  //     $new_bookmark_id = $this->bookmark_model->create($authenticated_member_id, $title, $url, $tag_names);

  //     $new_bookmark = $this->bookmark_model->get_by_id($new_bookmark_id);

  //     $this->response([
  //       'status' => true,
  //       'message' => 'Bookmark created',
  //       'data' => $new_bookmark
  //     ], BaseRestController::HTTP_CREATED);
  //   } catch (ValidationException $e) {
  //     log_message('error', "Validation error: {$e->getMessage()}");

  //     $this->response([
  //       'status' => false,
  //       'message' => $e->getMessage()
  //     ], BaseRestController::HTTP_UNPROCESSABLE_ENTITY);
  //   } catch (Exception $e) {
  //     log_message('error', "Error: {$e->getMessage()}");

  //     $this->response([
  //       'status' => false,
  //       'message' => 'Bookmark creation failed'
  //     ], BaseRestController::HTTP_INTERNAL_ERROR);
  //   }
  // }
}
