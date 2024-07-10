<?php
defined('BASEPATH') || exit('No direct script access allowed');

require_once APPPATH . 'models/exceptions.php';

class Member_model extends CI_Model
{
  private $table = 'member';

  public function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  public function create($email, $nickname, $password)
  {
    if (!$this->is_valid_email($email)) {
      throw new ValidationException('Email is required and must be a valid email address');
    }

    if (!$this->is_email_unused($email)) {
      throw new DuplicateException('Email address is already registered');
    }

    if (!$this->is_valid_nickname($nickname)) {
      throw new ValidationException(
        'Nickname is required and must be less than 50 characters, alphanumeric, underscores, and spaces'
      );
    }

    if (!$this->is_strong_password($password)) {
      throw new ValidationException(
        'Password must be at least 8 characters long, with a mix of uppercase, lowercase, numbers, ' .
          'and special characters'
      );
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $data = array(
      'nickname' => $nickname,
      'email' => $email,
      'password_hash' => $password_hash,
    );

    $this->db->insert($this->table, $data);
    return $this->db->insert_id();
  }


  public function get_by_id($member_id)
  {
    if (!$member_id) {
      throw new ValidationException('Member_id is required');
    }
    $this->db->select('member_id, nickname, created_at');
    $this->db->where('member_id', $member_id);
    $query = $this->db->get($this->table);

    return $query->row();
  }

  public function get_by_email($email)
  {
    if (!$email) {
      throw new ValidationException('Email is required');
    }
    $this->db->select('member_id, nickname, created_at');
    $this->db->where('email', $email);
    $query = $this->db->get($this->table);

    return $query->row();
  }

  public function verify_password_by_email($email, $password)
  {
    if (!$email || !$password) {
      throw new ValidationException('Email and password are required');
    }

    $password_hash = $this->get_password_hash_by_email($email);

    return password_verify($password, $password_hash);
  }

  public function delete_by_id($member_id)
  {
    if (!$member_id) {
      throw new ValidationException('Member_id is required');
    }

    $this->db->where('member_id', $member_id);
    $this->db->delete($this->table);

    if ($this->db->affected_rows() === 0) {
      throw new NotFoundException('Member not found');
    }
  }

  private function is_email_unused($email)
  {
    $this->db->where('email', $email);
    $query = $this->db->get($this->table);

    return $query->num_rows() === 0;
  }

  private function get_password_hash_by_email($email)
  {
    $this->db->select('password_hash');
    $this->db->where('email', $email);
    $query = $this->db->get($this->table);
    $result = $query->row();

    return $result ? $result->password_hash : null;
  }

  private function is_valid_nickname($nickname)
  {
    // 1-50 characters, alphanumeric, underscores, and spaces.
    $pattern = '/^(?=.*[a-zA-Z0-9])[a-zA-Z0-9_ ]{1,50}$/';
    return $nickname && is_string($nickname) && preg_match($pattern, $nickname);
  }

  private function is_valid_email($email)
  {
    return $email && is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  private function is_strong_password($password)
  {
    // min 8 characters, with a mix of uppercase, lowercase, numbers, and special characters.
    $pattern = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=])(?=\S+$).{8,}$/';
    return $password && is_string($password) && preg_match($pattern, $password);
  }
}
