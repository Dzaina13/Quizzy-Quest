<?php
// assets/php/view_quiz_data.php
require_once 'koneksi_db.php';

class ViewQuizData {
  private $koneksi;
  
  public function __construct($koneksi) {
      $this->koneksi = $koneksi;
  }
  
  /**
   * Get quizzes with filters and stats
   */
  public function getQuizzes($category = 'all', $search = '') {
      $where_conditions = [];
      
      // Category filter
      if ($category !== 'all') {
          if ($category === 'quiz') {
              $where_conditions[] = "q.quiz_type = 'normal'";
          } elseif ($category === 'rof') {
              $where_conditions[] = "q.quiz_type = 'rof'";
          } elseif ($category === 'decision_maker') {
              $where_conditions[] = "q.quiz_type = 'decision_maker'";
          }
      }
      
      // Search filter
      if (!empty($search)) {
          $search_param = mysqli_real_escape_string($this->koneksi, $search);
          $where_conditions[] = "(q.title LIKE '%$search_param%' OR q.description LIKE '%$search_param%')";
      }
      
      $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
      
      // Main query
      $query = "
          SELECT 
              q.quiz_id,
              q.title,
              q.description,
              q.quiz_type,
              q.created_at,
              u.username as creator,
              COUNT(DISTINCT qs.session_id) as total_sessions,
              COUNT(DISTINCT p.participant_id) as total_participants,
              COUNT(DISTINCT questions.question_id) as total_questions
          FROM quizzes q
          LEFT JOIN users u ON q.created_by = u.user_id
          LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id
          LEFT JOIN participants p ON qs.session_id = p.session_id
          LEFT JOIN questions ON q.quiz_id = questions.quiz_id
          $where_clause
          GROUP BY q.quiz_id, q.title, q.description, q.quiz_type, q.created_at, u.username
          ORDER BY q.created_at DESC
      ";
      
      $result = mysqli_query($this->koneksi, $query);
      if (!$result) {
          throw new Exception("Query failed: " . mysqli_error($this->koneksi));
      }
      
      $quizzes = [];
      while ($row = mysqli_fetch_assoc($result)) {
          $quizzes[] = $row;
      }
      
      return $quizzes;
  }
  
  /**
   * Get category counts
   */
  public function getCategoryCounts() {
      $count_query = "
          SELECT 
              quiz_type,
              COUNT(*) as count
          FROM quizzes 
          GROUP BY quiz_type
      ";
      
      $count_result = mysqli_query($this->koneksi, $count_query);
      if (!$count_result) {
          throw new Exception("Count query failed: " . mysqli_error($this->koneksi));
      }
      
      $counts = [];
      while ($row = mysqli_fetch_assoc($count_result)) {
          $counts[$row['quiz_type']] = $row['count'];
      }
      
      return [
          'total' => array_sum($counts),
          'normal' => $counts['normal'] ?? 0,
          'rof' => $counts['rof'] ?? 0,
          'decision_maker' => $counts['decision_maker'] ?? 0
      ];
  }
  
  /**
   * Check if quiz has questions (for live room validation)
   */
  public function hasQuestions($quiz_id) {
      $stmt = mysqli_prepare($this->koneksi, "SELECT COUNT(*) as count FROM questions WHERE quiz_id = ?");
      mysqli_stmt_bind_param($stmt, "i", $quiz_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $row = mysqli_fetch_assoc($result);
      
      return $row['count'] > 0;
  }
}
?>