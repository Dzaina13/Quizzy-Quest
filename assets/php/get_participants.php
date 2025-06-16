<?php
// assets/php/get_participants.php
session_start();
require_once 'koneksi_db.php';
require_once 'live_room_handler.php';

header('Content-Type: application/json');

$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
  echo json_encode(['error' => 'Session ID required']);
  exit();
}

try {
  $handler = new LiveRoomHandler($koneksi);
  $participants = $handler->getParticipants($session_id);
  
  // Debug log
  error_log("Session ID: " . $session_id);
  error_log("Participants count: " . count($participants));
  error_log("Participants data: " . json_encode($participants));
  
  echo json_encode([
      'success' => true,
      'participants' => $participants,
      'count' => count($participants)
  ]);
  
} catch (Exception $e) {
  error_log("Error in get_participants.php: " . $e->getMessage());
  echo json_encode([
      'error' => 'Failed to get participants',
      'message' => $e->getMessage()
  ]);
}
?>