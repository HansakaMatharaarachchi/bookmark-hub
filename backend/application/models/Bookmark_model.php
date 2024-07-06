<?php
defined('BASEPATH') || exit('No direct script access allowed');

require_once APPPATH . 'models/exceptions.php';

class Bookmark_model extends CI_Model
{

  private $table = 'bookmark';
  private $tag_table = 'tag';
  private $bookmark_tag_table = 'bookmark_tag';

  public function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  /**
   * Get bookmark for a member, by ID.
   *
   * @param int $member_id Member ID.
   * @param int $bookmark_id Bookmark ID.
   * @return array Bookmark data.
   * @throws ValidationException If member ID or bookmark ID is empty.
   * @throws Exception If any error occurs.
   */
  public function get_bookmark_by_id($member_id, $bookmark_id)
  {
    if (empty($member_id)) {
      throw new ValidationException('Member ID is required');
    }

    if (empty($bookmark_id)) {
      throw new ValidationException('Bookmark ID is required');
    }

    $bookmark = $this->db->get_where(
      $this->table,
      [
        'member_id' => $member_id,
        'bookmark_id' => $bookmark_id
      ]
    )
      ->row_array();

    return $bookmark ? $bookmark : null;
  }

  /**
   * Get bookmarks for a member.
   * Optionally filter by title and tags.
   * Optionally limit and offset results.
   *
   * @param int $member_id Member ID.
   * @param array $filters Filters.
   * @param int $limit Limit.
   * @param int $offset Offset.
   * @return array Filtered bookmarks and total bookmarks count.
   * @throws ValidationException If member ID is empty.
   * @throws Exception If any error occurs.
   */
  public function get_bookmarks_by_member_id($member_id, $filters = [], $limit = 10, $offset = 0)
  {
    if (empty($member_id)) {
      throw new ValidationException('Member ID is required');
    }

    // Get bookmarks.
    $this->db->select('*');
    $this->db->from("$this->table b");
    $this->db->where('b.member_id', $member_id);

    // Apply filters.

    // Title filter.
    if (!empty($filters['title'])) {
      $this->db->like('b.title', $this->db->escape_like_str($filters['title']));
    }

    // Tags filter.
    if (!empty($filters['tags']) && is_array($filters['tags'])) {
      $this->db->join("$this->bookmark_tag_table bt", 'b.id = bt.bookmark_id');
      $this->db->join("$this->tag_table t", 'bt.tag_id = t.id');
      $this->db->where_in('t.name', $filters['tags']);
      $this->db->group_by('b.id');
    }

    // Get total number of bookmarks.
    $total_bookmarks_count = $this->db->count_all_results('', false); // false: do not reset query after execution.

    // Order and limit.
    $this->db->order_by('b.created_at', 'DESC');
    $this->db->limit($limit, $offset);

    $bookmarks = $this->db->get()->result_array();

    return [
      'bookmarks' => $bookmarks,
      'total_bookmarks_count' => $total_bookmarks_count
    ];
  }

  /**
   * Get tags for a bookmark.
   *
   * @param int $bookmark_id Bookmark ID.
   * @return array Tags for the bookmark.
   * @throws ValidationException If bookmark ID is empty.
   * @throws Exception If any error occurs.
   */
  public function get_tags_for_bookmark($bookmark_id)
  {
    if (empty($bookmark_id)) {
      throw new ValidationException('Bookmark ID is required');
    }

    $this->db->select('t.tag_id, t.name')
      ->from("$this->tag_table t")
      ->join("$this->bookmark_tag_table bt", 'bt.tag_id = t.tag_id')
      ->where('bt.bookmark_id', $bookmark_id);

    return $this->db->get()->result_array();
  }

  // public function create($member_id, $title, $url, $tag_names)
  // {
  //   $this->validate_member_id($member_id);
  //   $this->validate_title($title);
  //   $this->validate_url($url);

  //   $this->db->trans_start();

  //   try {
  //     // Insert bookmark.
  //     $bookmark_id = $this->insert_bookmark($member_id, $title, $url);

  //     // Insert tags and associate them with the bookmark.
  //     $tag_ids = $this->get_or_create_tag_ids($member_id, $tag_names);
  //     $this->associate_tags_with_bookmark($bookmark_id, $tag_ids);

  //     $this->db->trans_complete();

  //     return $bookmark_id;
  //   } catch (Exception $e) {
  //     $this->db->trans_rollback();

  //     throw $e;
  //   }
  // }

  // public function update($member_id, $bookmark_id, $title, $url, $tag_names)
  // {
  //   $this->validate_member_id($member_id);
  //   $this->validate_bookmark_id($bookmark_id);

  //   if (isset($title)) {
  //     $this->validate_title($title);
  //   }

  //   if (isset($url)) {
  //     $this->validate_url($url);
  //   }

  //   $this->execute_transaction(function () use ($member_id, $bookmark_id, $title, $url, $tag_names) {
  //     $tag_ids = [];

  //     if (isset($tag_names)) {
  //       $tag_ids = $this->get_or_create_tag_ids($member_id, $tag_names);
  //     }

  //     $this->update_bookmark($member_id, $bookmark_id, $title, $url);
  //     if (!empty($tag_ids)) {
  //       $this->update_bookmark_tags($bookmark_id, $tag_ids);
  //     }
  //     $this->clear_orphaned_tags();
  //   });
  // }

  // public function delete($member_id, $bookmark_id)
  // {
  //   $this->validate_member_id($member_id);
  //   $this->validate_bookmark_id($bookmark_id);

  //   $this->execute_transaction(function () use ($member_id, $bookmark_id) {
  //     $this->db->where('member_id', $member_id)
  //       ->where('bookmark_id', $bookmark_id)
  //       ->delete('bookmark');
  //     $this->clear_orphaned_tags();
  //   });
  // }

  // public function search_by_tags($member_id, $tag_names)
  // {
  //   $this->validate_member_id($member_id);
  //   $this->validate_tag_names($tag_names);

  //   $this->db->select('b.bookmark_id, b.title, b.url')
  //     ->from('bookmark b')
  //     ->join('bookmark_tag bt', 'b.bookmark_id = bt.bookmark_id')
  //     ->join('tags t', 'bt.tag_id = t.tag_id')
  //     ->where('t.member_id', $member_id)
  //     ->where_in('t.name', $tag_names)
  //     ->group_by('b.bookmark_id, b.title, b.url')
  //     ->having('COUNT(t.tag_id) = ' . count($tag_names));

  //   return $this->db->get()->result_array();
  // }

  // private function insert_bookmark($member_id, $title, $url)
  // {
  //   $this->db->insert(
  //     'bookmark',
  //     [
  //       'member_id' => $member_id,
  //       'title' => $title,
  //       'url' => $url
  //     ]
  //   );

  //   return $this->db->insert_id();
  // }

  // private function update_bookmark($member_id, $bookmark_id, $title, $url)
  // {
  //   $this->db->where('member_id', $member_id)
  //     ->where('bookmark_id', $bookmark_id)
  //     ->update('bookmark', ['title' => $title, 'url' => $url]);
  // }

  // private function get_or_create_tag_ids($member_id, $tag_names)
  // {
  //   $this->validate_member_id($member_id);
  //   $this->validate_tag_names($tag_names);

  //   $existing_tag_ids = $this->get_existing_tag_ids($member_id, $tag_names);
  //   $new_tag_names = array_diff($tag_names, array_keys($existing_tag_ids));

  //   if (!empty($new_tag_names)) {
  //     $this->insert_tags($member_id, $new_tag_names);

  //     $new_tag_ids = $this->get_tag_ids_by_names($new_tag_names);

  //     $existing_tag_ids = array_merge($existing_tag_ids, $new_tag_ids);
  //   }

  //   return array_values($existing_tag_ids);
  // }

  // private function get_existing_tag_ids($member_id, $tag_names)
  // {
  //   $this->db->select('tag_id, name')
  //     ->from('tag')
  //     ->where('member_id', $member_id)
  //     ->where_in('name', $tag_names);

  //   $existing_tags = $this->db->get()->result_array();
  //   return array_column($existing_tags, 'tag_id', 'name');
  // }

  // private function insert_tags($member_id, $tag_names)
  // {
  //   $tags = array_map(function ($name) use ($member_id) {
  //     return ['name' => $name, 'member_id' => $member_id];
  //   }, $tag_names);

  //   $this->db->insert_batch('tag', $tags);
  // }

  // private function get_tag_ids_by_names($tag_names)
  // {
  //   $this->db->select('tag_id, name')
  //     ->from('tag')
  //     ->where_in('name', $tag_names);

  //   $tags = $this->db->get()->result_array();

  //   return array_column($tags, 'tag_id', 'name');
  // }

  // private function associate_tags_with_bookmark($bookmark_id, $tag_ids)
  // {
  //   $bookmark_tag_data = array_map(function ($tag_id) use ($bookmark_id) {
  //     return ['bookmark_id' => $bookmark_id, 'tag_id' => $tag_id];
  //   }, $tag_ids);

  //   $this->db->insert_batch('bookmark_tag', $bookmark_tag_data);
  // }

  // private function update_bookmark_tags($bookmark_id, $tag_ids)
  // {
  //   $this->db->where('bookmark_id', $bookmark_id)
  //     ->delete('bookmark_tag');

  //   $this->associate_tags_with_bookmark($bookmark_id, $tag_ids);
  // }

  // private function clear_orphaned_tags()
  // {
  //   $this->db->where('tag_id NOT IN (SELECT tag_id FROM bookmark_tag)', null, false)
  //     ->delete('tag');
  // }

  // private function validate_member_id($member_id)
  // {
  //   if (empty($member_id)) {
  //     throw new ValidationException('Member ID is required');
  //   }
  // }

  // private function validate_bookmark_id($bookmark_id)
  // {
  //   if (empty($bookmark_id)) {
  //     throw new ValidationException('Bookmark ID is required');
  //   }
  // }

  // private function validate_title($title)
  // {
  //   if (!is_string($title) || empty(trim($title)) || strlen($title) > 150) {
  //     throw new ValidationException('Title must be a non-empty string of 150 characters or less');
  //   }
  // }

  // private function validate_url($url)
  // {
  //   // This is a more strict URL validation.
  //   // https://www.php.net/manual/en/filter.filters.validate.php
  //   // return filter_var($url, FILTER_VALIDATE_URL);

  //   // For User Friendliness, Only Check URL Length for Now.
  //   if (!is_string($url) || empty(trim($url)) || strlen($url) > 2083) {
  //     throw new ValidationException('URL must be a valid URL');
  //   }
  // }

  // private function validate_tag_names($tag_names)
  // {
  //   if (!is_array($tag_names) || empty($tag_names)) {
  //     throw new ValidationException('Tag names must be a non-empty array');
  //   }

  //   foreach ($tag_names as $name) {
  //     if (!$this->is_valid_tag_name($name)) {
  //       throw new ValidationException('Tag names must be non-empty strings of 50 characters or less without leading or trailing spaces');
  //     }
  //   }

  //   if (count($tag_names) !== count(array_unique($tag_names))) {
  //     throw new ValidationException('Duplicate tag names are not allowed');
  //   }
  // }

  // private function is_valid_tag_name($name)
  // {
  //   return is_string($name) && !empty(trim($name)) && trim($name) === $name && strlen($name) <= 50;
  // }

  // // TODO: Refactor this into base model class.
  // private function execute_transaction(callable $callback)
  // {
  //   $this->db->trans_start();
  //   try {
  //     $result = $callback();
  //     $this->db->trans_complete();
  //     return $result;
  //   } catch (Exception $e) {
  //     $this->db->trans_rollback();
  //     throw $e;
  //   }
  // }
}
