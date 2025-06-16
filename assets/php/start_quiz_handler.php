<?php
// assets/php/start_quiz_handler.php

require_once 'koneksi_db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['session_id']) || !isset($input['action'])) {
  echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
  exit;
}

$session_id = (int)$input['session_id'];
$action = $input['action'];

try {
  if ($action === 'start') {
      // Check if session exists and is in waiting status
      $check_query = "SELECT session_id, room_status, start_time FROM quiz_sessions WHERE session_id = ?";
      $check_stmt = mysqli_prepare($koneksi, $check_query);
      mysqli_stmt_bind_param($check_stmt, "i", $session_id);
      mysqli_stmt_execute($check_stmt);
      $result = mysqli_stmt_get_result($check_stmt);
      
      if ($row = mysqli_fetch_assoc($result)) {
          if ($row['room_status'] !== 'waiting') {
              echo json_encode([
                  'success' => false, 
                  'message' => 'Quiz is not in waiting status'
              ]);
              exit;
          }
          
          if ($row['start_time'] !== null) {
              echo json_encode([
                  'success' => false, 
                  'message' => 'Quiz has already been started'
              ]);
              exit;
          }
          
          // Start the quiz - update both start_time and room_status
          $update_query = "UPDATE quiz_sessions SET start_time = NOW(), room_status = 'active' WHERE session_id = ?";
          $update_stmt = mysqli_prepare($koneksi, $update_query);
          mysqli_stmt_bind_param($update_stmt, "i", $session_id);
          
          if (mysqli_stmt_execute($update_stmt)) {
              echo json_encode([
                  'success' => true, 
                  'message' => 'Quiz started successfully',
                  'start_time' => date('Y-m-d H:i:s'),
                  'room_status' => 'active'
              ]);
          } else {
              throw new Exception('Failed to start quiz: ' . mysqli_error($koneksi));
          }
      } else {
          echo json_encode([
              'success' => false, 
              'message' => 'Session not found'
          ]);
      }
      
  } elseif ($action === 'end') {
      // End the quiz
      $update_query = "UPDATE quiz_sessions SET end_time = NOW(), room_status = 'ended' WHERE session_id = ? AND room_status = 'active'";
      $update_stmt = mysqli_prepare($koneksi, $update_query);
      mysqli_stmt_bind_param($update_stmt, "i", $session_id);
      
      if (mysqli_stmt_execute($update_stmt)) {
          if (mysqli_stmt_affected_rows($update_stmt) > 0) {
              echo json_encode([
                  'success' => true, 
                  'message' => 'Quiz ended successfully',
                  'end_time' => date('Y-m-d H:i:s'),
                  'room_status' => 'ended'
              ]);
          } else {
              echo json_encode([
                  'success' => false, 
                  'message' => 'Quiz is not active or already ended'
              ]);
          }
      } else {
          throw new Exception('Failed to end quiz: ' . mysqli_error($koneksi));
      }
      
  } elseif ($action === 'status') {
      // Get current status
      $status_query = "SELECT session_id, session_name, room_code, start_time, end_time, room_status FROM quiz_sessions WHERE session_id = ?";
      $status_stmt = mysqli_prepare($koneksi, $status_query);
      mysqli_stmt_bind_param($status_stmt, "i", $session_id);
      mysqli_stmt_execute($status_stmt);
      $result = mysqli_stmt_get_result($status_stmt);
      
      if ($row = mysqli_fetch_assoc($result)) {
          echo json_encode([
              'success' => true,
              'data' => $row
          ]);
      } else {
          echo json_encode([
              'success' => false, 
              'message' => 'Session not found'
          ]);
      }
      
  } else {
      echo json_encode([
          'success' => false, 
          'message' => 'Invalid action'
      ]);
  }

} catch (Exception $e) {
  echo json_encode([
      'success' => false, 
      'message' => 'Error: ' . $e->getMessage()
  ]);
}

mysqli_close($koneksi);
?>