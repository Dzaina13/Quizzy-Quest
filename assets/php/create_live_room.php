<?php
// assets/php/create_live_room.php
session_start();
require_once 'koneksi_db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Please login first']);
  exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$quiz_id = $input['quiz_id'] ?? null;

if (!$quiz_id) {
  echo json_encode(['success' => false, 'message' => 'Quiz ID required']);
  exit();
}

$user_id = $_SESSION['user_id'];

try {
  // ✅ TAMBAHAN: Validasi quiz dan pertanyaan
  $stmt_check = mysqli_prepare($koneksi, "
      SELECT 
    q.quiz_id,
    q.quiz_type,
    CASE 
        WHEN q.quiz_type = 'rof' THEN (
            SELECT COUNT(*) FROM rof_questions rq WHERE rq.rof_quiz_id = q.quiz_id
        )
        WHEN q.quiz_type = 'decision_maker' THEN (
            SELECT COUNT(*) FROM decision_maker_questions dq WHERE dq.quiz_id = q.quiz_id
        )
        ELSE (
            SELECT COUNT(*) FROM questions qq WHERE qq.quiz_id = q.quiz_id
        )
    END AS question_count
FROM quizzes q
WHERE q.quiz_id = ?
  ");
  mysqli_stmt_bind_param($stmt_check, "i", $quiz_id);
  mysqli_stmt_execute($stmt_check);
  $result_check = mysqli_stmt_get_result($stmt_check);
  $quiz = mysqli_fetch_assoc($result_check);
  
  if (!$quiz) {
      throw new Exception('Quiz not found');
  }
  
  if ($quiz['question_count'] == 0) {
      throw new Exception('Quiz must have at least one question to create live room');
  }

  // ✅ IMPROVED: Generate unique room code
  do {
      $room_code = strtoupper(substr(md5(uniqid()), 0, 6));
      $check_stmt = mysqli_prepare($koneksi, "SELECT session_id FROM quiz_sessions WHERE room_code = ?");
      mysqli_stmt_bind_param($check_stmt, "s", $room_code);
      mysqli_stmt_execute($check_stmt);
      $exists = mysqli_stmt_get_result($check_stmt)->num_rows > 0;
  } while ($exists);
  
  // Create session
  $stmt = mysqli_prepare($koneksi, "
      INSERT INTO quiz_sessions (quiz_id, session_name, room_code, created_by, is_live_room, room_status) 
      VALUES (?, 'Live Quiz', ?, ?, 1, 'waiting')
  ");
  mysqli_stmt_bind_param($stmt, "isi", $quiz_id, $room_code, $user_id);
  mysqli_stmt_execute($stmt);
  
  $session_id = mysqli_insert_id($koneksi);
  
  // Add host as participant
  $stmt2 = mysqli_prepare($koneksi, "
      INSERT INTO participants (session_id, user_id, is_host) 
      VALUES (?, ?, 1)
  ");
  mysqli_stmt_bind_param($stmt2, "ii", $session_id, $user_id);
  mysqli_stmt_execute($stmt2);
  
  // Initialize state
  $stmt4 = mysqli_prepare($koneksi, "
      INSERT INTO live_session_state (session_id, total_questions) 
      VALUES (?, ?)
  ");
  mysqli_stmt_bind_param($stmt4, "ii", $session_id, $quiz['question_count']);
  mysqli_stmt_execute($stmt4);
  
  // ✅ IMPROVED: Better response
  echo json_encode([
      'success' => true,
      'session_id' => $session_id,
      'room_code' => $room_code,
      'total_questions' => $quiz['question_count'],
      'message' => 'Live room created successfully'
  ]);
  
} catch (Exception $e) {
  echo json_encode([
      'success' => false, 
      'message' => $e->getMessage()
  ]);
}
?>