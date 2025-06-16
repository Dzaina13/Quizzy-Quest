<?php
// assets/php/check_quiz_status.php - FINAL CORRECT VERSION

header('Content-Type: application/json');

// Include database connection
require_once 'koneksi_db.php';

if (!isset($_GET['session_id'])) {
  echo json_encode(['error' => 'Session ID required']);
  exit;
}

$session_id = (int)$_GET['session_id'];

try {
  // Use correct column name: room_status
  $query = "SELECT room_status as status FROM quiz_sessions WHERE session_id = ?";
  $stmt = mysqli_prepare($koneksi, $query);
  mysqli_stmt_bind_param($stmt, "i", $session_id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($result);
  
  $status = 'waiting'; // default
  if ($row) {
      $status = $row['status'];
  }
  
  mysqli_stmt_close($stmt);
  
  // Get participant count
  $count_query = "SELECT COUNT(*) as count FROM participants WHERE session_id = ? AND connection_status = 'connected'";
  $count_stmt = mysqli_prepare($koneksi, $count_query);
  mysqli_stmt_bind_param($count_stmt, "i", $session_id);
  mysqli_stmt_execute($count_stmt);
  $count_result = mysqli_stmt_get_result($count_stmt);
  $count_row = mysqli_fetch_assoc($count_result);
  $participant_count = $count_row ? $count_row['count'] : 0;
  mysqli_stmt_close($count_stmt);
  
  echo json_encode([
      'status' => $status,
      'participant_count' => $participant_count,
      'session_id' => $session_id
  ]);
  
} catch (Exception $e) {
  echo json_encode([
      'error' => 'Database error: ' . $e->getMessage(),
      'status' => 'waiting',
      'participant_count' => 0
  ]);
}
?>