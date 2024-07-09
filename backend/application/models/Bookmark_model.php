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
    $this->validate_member_id($member_id);
    $this->validate_bookmark_id($bookmark_id);

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
    $this->validate_member_id($member_id);

    // Initialize filters
    $search = isset($filters['search']) ? $filters['search'] : '';
    $tags = isset($filters['tags']) ? $filters['tags'] : [];

    // Get bookmarks.
    $this->db->select('b.*');
    $this->db->from("$this->table b");
    $this->db->where('b.member_id', $member_id);

    // Apply search filter
    if (!empty($search)) {
      $this->db->like('b.title', $this->db->escape_like_str($search));
    }

    // Apply tags filter
    if (!empty($tags)) {
      $this->db->join($this->bookmark_tag_table . ' bt', 'b.bookmark_id = bt.bookmark_id');
      $this->db->join($this->tag_table . ' t', 'bt.tag_id = t.tag_id');
      $this->db->where_in('t.name', $tags);
      $this->db->group_by('b.bookmark_id');
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
  /**
   * Add a bookmark.
   * Optionally add tags.
   * Returns the bookmark ID.
   * @param int $member_id Member ID.
   * @param string $title Title.
   * @param string $url URL.
   * @param array $tag_names Tag names.
   * @return int Bookmark ID.
   * @throws ValidationException If member ID, title, or URL is empty.
   * @throws Exception If any error occurs.
   */
  public function add_bookmark($member_id, $title, $url, $tag_names)
  {
    try {
      $this->validate_member_id($member_id);
      $this->validate_title($title);
      $this->validate_url($url);

      $this->db->trans_start();
      // Insert bookmark.
      $this->db->insert(
        'bookmark',
        [
          'member_id' => $member_id,
          'title' => $title,
          'url' => $url
        ]
      );

      $bookmark_id =  $this->db->insert_id();

      // Insert tags and associate them with the bookmark.
      $tag_ids = $this->get_or_create_tag_ids($member_id, $tag_names);
      $this->associate_tags_with_bookmark($bookmark_id, $tag_ids);

      $this->db->trans_complete();

      return $bookmark_id;
    } catch (Exception $e) {
      $this->db->trans_rollback();

      throw $e;
    }
  }

  /**
   * Patches a bookmark.
   * Optionally update title, URL, and tags.
   * @param int $member_id Member ID.
   * @param int $bookmark_id Bookmark ID.
   * @param string $title Title.
   * @param string $url URL.
   * @param array $tag_names Tag names.
   * @return void Nothing.
   * @throws ValidationException If member ID or bookmark ID is empty.
   * @throws Exception If any error occurs.
   */
  public function patch_bookmark($member_id, $bookmark_id, $title, $url, $tag_names)
  {
    try {
      $this->validate_member_id($member_id);
      $this->validate_bookmark_id($bookmark_id);

      $bookmark = $this->get_bookmark_by_id($member_id, $bookmark_id);

      if (!$bookmark) {
        throw new NotFoundException('Bookmark not found');
      }

      $bookmark_data = [];

      if (isset($title)) {
        $this->validate_title($title);
        $bookmark_data['title'] = $title;
      }

      if (isset($url)) {
        $this->validate_url($url);
        $bookmark_data['url'] = $url;
      }

      $this->db->trans_start();

      $tag_ids = [];

      if (isset($tag_names)) {
        $tag_ids = $this->get_or_create_tag_ids($member_id, $tag_names);
      }

      if (!empty($bookmark_data)) {
        $this->db->where('member_id', $member_id)
          ->where('bookmark_id', $bookmark_id)
          ->update('bookmark', $bookmark_data);
      }

      if (!empty($tag_ids)) {
        $this->update_bookmark_tags($bookmark_id, $tag_ids);
      }

      $this->clear_orphaned_tags();

      $this->db->trans_complete();
    } catch (Exception $e) {
      $this->db->trans_rollback();

      throw $e;
    }
  }

  public function delete_bookmark($member_id, $bookmark_id)
  {
    try {
      $this->db->trans_start();

      $this->validate_member_id($member_id);
      $this->validate_bookmark_id($bookmark_id);

      $this->db->where('member_id', $member_id)
        ->where('bookmark_id', $bookmark_id)
        ->delete('bookmark');

      if ($this->db->affected_rows() === 0) {
        throw new NotFoundException('Bookmark not found');
      }

      $this->clear_orphaned_tags();

      $this->db->trans_complete();
    } catch (Exception $e) {
      $this->db->trans_rollback();

      throw $e;
    }
  }

  /**
   * Get tag IDs for the given tag names.
   * If a tag does not exist, it will be created.
   * Returns an array of tag IDs.
   *
   * @param int $member_id Member ID.
   * @param array $tag_names Tag names.
   * @return array Tag IDs.
   * @throws ValidationException If member ID or tag names are empty.
   * @throws Exception If any error occurs.
   */
  private function get_or_create_tag_ids($member_id, $tag_names)
  {
    $this->validate_member_id($member_id);
    $this->validate_tag_names($tag_names);

    $existing_tag_ids = $this->get_existing_tag_ids($member_id, $tag_names);
    $new_tag_names = array_diff($tag_names, array_keys($existing_tag_ids));

    if (!empty($new_tag_names)) {
      $this->insert_tags($member_id, $new_tag_names);

      $new_tag_ids = $this->get_tag_ids_by_names($new_tag_names);

      $existing_tag_ids = array_merge($existing_tag_ids, $new_tag_ids);
    }

    return array_values($existing_tag_ids);
  }

  /**
   * Get existing tag IDs for the given member and tag names.
   * Returns an associative array of tag IDs keyed by tag names.
   * If a tag does not exist, it will not be included in the result.
   *
   * @param int $member_id Member ID.
   * @param array $tag_names Tag names.
   * @return array Tag IDs keyed by tag names.
   * @throws Exception If any error occurs.
   */
  private function get_existing_tag_ids($member_id, $tag_names)
  {
    $this->db->select('tag_id, name')
      ->from('tag')
      ->where('member_id', $member_id)
      ->where_in('name', $tag_names);

    $existing_tags = $this->db->get()->result_array();
    return array_column($existing_tags, 'tag_id', 'name');
  }

  /**
   * Insert tags.
   * Tags must be unique by name.
   * @param int $member_id Member ID.
   * @param array $tag_names Tag names.
   * @return void Nothing.
   * @throws Exception If any error occurs.
   */
  private function insert_tags($member_id, $tag_names)
  {
    $tags = array_map(function ($name) use ($member_id) {
      return ['name' => $name, 'member_id' => $member_id];
    }, $tag_names);

    $this->db->insert_batch('tag', $tags);
  }

  /**
   * Get tag IDs by tag names.
   * Returns an associative array of tag IDs keyed by tag names.
   * @param array $tag_names Tag names.
   * @return array Tag IDs keyed by tag names.
   * @throws Exception If any error occurs.
   */
  private function get_tag_ids_by_names($tag_names)
  {
    $this->db->select('tag_id, name')
      ->from('tag')
      ->where_in('name', $tag_names);

    $tags = $this->db->get()->result_array();

    return array_column($tags, 'tag_id', 'name');
  }

  /**
   * Associate tags with a bookmark.
   * @param int $bookmark_id Bookmark ID.
   * @param array $tag_ids Tag IDs.
   * @return void Nothing.
   * @throws Exception If any error occurs.
   */
  private function associate_tags_with_bookmark($bookmark_id, $tag_ids)
  {
    $bookmark_tag_data = array_map(function ($tag_id) use ($bookmark_id) {
      return ['bookmark_id' => $bookmark_id, 'tag_id' => $tag_id];
    }, $tag_ids);

    $this->db->insert_batch('bookmark_tag', $bookmark_tag_data);
  }

  /**
   * Update bookmark tags.
   * Returns nothing.
   * @param int $bookmark_id Bookmark ID.
   * @param array $tag_ids Tag IDs.
   * @throws Exception If any error occurs.
   */
  private function update_bookmark_tags($bookmark_id, $tag_ids)
  {
    $this->db->where('bookmark_id', $bookmark_id)
      ->delete('bookmark_tag');

    $this->associate_tags_with_bookmark($bookmark_id, $tag_ids);
  }

  /**
   * Clear orphaned tags.
   * Remove tags that are not associated with any bookmark.
   * @return void Nothing.
   * @throws Exception If any error occurs.
   */
  private function clear_orphaned_tags()
  {
    $this->db->where('tag_id NOT IN (SELECT tag_id FROM bookmark_tag)')
      ->delete('tag');
  }

  /**
   * Validate member ID.
   * @param int $member_id Member ID.
   * @return void Nothing.
   * @throws ValidationException If member ID is empty.
   */
  private function validate_member_id($member_id)
  {
    if (empty($member_id)) {
      throw new ValidationException('Member ID is required');
    }
  }

  /**
   * Validate bookmark ID.
   * @param int $bookmark_id Bookmark ID.
   * @return void Nothing.
   * @throws ValidationException If bookmark ID is empty.
   */
  private function validate_bookmark_id($bookmark_id)
  {
    if (empty($bookmark_id)) {
      throw new ValidationException('Bookmark ID is required');
    }
  }

  /**
   * Validate title.
   * @param string $title Title.
   * @return void Nothing.
   * @throws ValidationException If title is not valid.
   */
  private function validate_title($title)
  {
    if (!is_string($title) || empty(trim($title)) || strlen($title) > 150) {
      throw new ValidationException('Title must be a non-empty string of 150 characters or less');
    }
  }

  /**
   * Validate URL.
   * @param string $url URL.
   * @return void Nothing.
   * @throws ValidationException If URL is not valid.
   * @see https://www.php.net/manual/en/filter.filters.validate.php
   */
  private function validate_url($url)
  {
    // This is a more strict URL validation.
    // return filter_var($url, FILTER_VALIDATE_URL);

    // For User Friendliness, Only Check URL Length for Now.
    if (!is_string($url) || empty(trim($url)) || strlen($url) > 2083) {
      throw new ValidationException('URL must be a valid URL');
    }
  }

  /**
   * Validate tag names.
   * @param array $tag_names Tag names.
   * @return void Nothing.
   * @throws ValidationException If tag names are not valid.
   */
  private function validate_tag_names($tag_names)
  {
    if (!is_array($tag_names) || empty($tag_names)) {
      throw new ValidationException('Tag names must be a non-empty array');
    }

    foreach ($tag_names as $name) {
      if (!$this->is_valid_tag_name($name)) {
        throw new ValidationException('Tag names must be non-empty strings of 50 characters or less without leading or trailing spaces');
      }
    }

    if (count($tag_names) !== count(array_unique($tag_names))) {
      throw new ValidationException('Duplicate tag names are not allowed');
    }
  }

  /**
   * Check if a tag name is valid.
   * @param string $name Tag name.
   * @return bool True if valid, false otherwise.
   */
  private function is_valid_tag_name($name)
  {
    return is_string($name) && !empty(trim($name)) && trim($name) === $name && strlen($name) <= 50;
  }
}
