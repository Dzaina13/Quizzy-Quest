<?php
// assets/php/live_room_handler.php - FINAL CORRECT VERSION

class LiveRoomHandler {
  private $koneksi;
  
  public function __construct($koneksi) {
      $this->koneksi = $koneksi;
  }
  
  public function getParticipants($session_id) {
      // Use correct column name: username (not user_name)
      $query = "
          SELECT 
              p.participant_id,
              p.user_id,
              p.is_guest,
              p.guest_name,
              p.is_host,
              p.connection_status,
              p.join_time,
              u.username as registered_username
          FROM participants p
          LEFT JOIN users u ON p.user_id = u.user_id
          WHERE p.session_id = ? AND p.connection_status = 'connected'
          ORDER BY p.is_host DESC, p.join_time ASC
      ";
      
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "i", $session_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      $participants = [];
      $seen_users = []; // Track to prevent duplicates
      
      while ($row = mysqli_fetch_assoc($result)) {
          // Create unique key for deduplication
          $unique_key = $row['user_id'] ? 'user_' . $row['user_id'] : 'guest_' . $row['guest_name'];
          
          // Skip if already seen
          if (isset($seen_users[$unique_key])) {
              continue;
          }
          $seen_users[$unique_key] = true;
          
          // Determine display name
          $display_name = '';
          if ($row['is_guest'] == 1) {
              $display_name = $row['guest_name'] ?: 'Guest User';
          } else {
              $display_name = $row['registered_username'] ?: 'Unknown User';
          }
          
          // Skip invalid entries
          if (empty($display_name) || $display_name === 'Unknown User') {
              continue;
          }
          
          $participants[] = [
              'participant_id' => $row['participant_id'],
              'user_id' => $row['user_id'],
              'display_name' => $display_name,
              'is_host' => (bool)$row['is_host'],
              'is_guest' => (bool)$row['is_guest'],
              'connection_status' => $row['connection_status'],
              'join_time' => $row['join_time']
          ];
      }
      
      mysqli_stmt_close($stmt);
      return $participants;
  }
  
  public function addParticipant($session_id, $user_id = null, $guest_name = null, $is_host = false) {
      // CHECK IF ALREADY EXISTS to prevent duplicates
      if ($user_id) {
          $check_query = "SELECT participant_id FROM participants WHERE session_id = ? AND user_id = ? AND connection_status = 'connected'";
          $check_stmt = mysqli_prepare($this->koneksi, $check_query);
          mysqli_stmt_bind_param($check_stmt, "ii", $session_id, $user_id);
          mysqli_stmt_execute($check_stmt);
          $check_result = mysqli_stmt_get_result($check_stmt);
          
          if (mysqli_num_rows($check_result) > 0) {
              mysqli_stmt_close($check_stmt);
              return false; // Already exists
          }
          mysqli_stmt_close($check_stmt);
      }
      
      $is_guest = empty($user_id) ? 1 : 0;
      
      $query = "
          INSERT INTO participants (session_id, user_id, is_guest, guest_name, is_host, connection_status, join_time)
          VALUES (?, ?, ?, ?, ?, 'connected', NOW())
      ";
      
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "iisii", $session_id, $user_id, $is_guest, $guest_name, $is_host);
      $success = mysqli_stmt_execute($stmt);
      
      if ($success) {
          $participant_id = mysqli_insert_id($this->koneksi);
          mysqli_stmt_close($stmt);
          return $participant_id;
      }
      
      mysqli_stmt_close($stmt);
      return false;
  }
  
  public function isUserHost($session_id, $user_id) {
      // Check from quiz_sessions table with correct column: created_by
      $query = "
          SELECT qs.created_by, p.is_host 
          FROM quiz_sessions qs
          LEFT JOIN participants p ON qs.session_id = p.session_id AND p.user_id = ?
          WHERE qs.session_id = ?
      ";
      
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "ii", $user_id, $session_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $row = mysqli_fetch_assoc($result);
      mysqli_stmt_close($stmt);
      
      // User is host if they created the session OR marked as host in participants
      return ($row && ($row['created_by'] == $user_id || $row['is_host'] == 1));
  }
  
  public function isUserInSession($session_id, $user_id) {
      $query = "SELECT participant_id FROM participants WHERE session_id = ? AND user_id = ? AND connection_status = 'connected'";
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "ii", $session_id, $user_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $exists = mysqli_num_rows($result) > 0;
      mysqli_stmt_close($stmt);
      return $exists;
  }
  
  public function updateConnectionStatus($participant_id, $status) {
      $query = "UPDATE participants SET connection_status = ? WHERE participant_id = ?";
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "si", $status, $participant_id);
      $success = mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      return $success;
  }
  
  public function removeParticipant($participant_id) {
      return $this->updateConnectionStatus($participant_id, 'disconnected');
  }
  
  public function getSessionInfo($session_id) {
      // Use correct column names from quiz_sessions
      $query = "
          SELECT 
              qs.*,
              u.username as host_username
          FROM quiz_sessions qs
          LEFT JOIN users u ON qs.created_by = u.user_id
          WHERE qs.session_id = ?
      ";
      
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "i", $session_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $session = mysqli_fetch_assoc($result);
      mysqli_stmt_close($stmt);
      
      if ($session) {
          return $session;
      }
      
      // Fallback - create dummy session info from participants
      $query = "
          SELECT 
              session_id,
              'Live Quiz Session' as session_name,
              CONCAT('ROOM', session_id) as room_code,
              'waiting' as room_status,
              MIN(CASE WHEN is_host = 1 THEN user_id END) as created_by
          FROM participants 
          WHERE session_id = ? 
          GROUP BY session_id
      ";
      
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "i", $session_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $session = mysqli_fetch_assoc($result);
      mysqli_stmt_close($stmt);
      
      return $session ?: [
          'session_id' => $session_id,
          'session_name' => 'Live Quiz Session',
          'room_code' => 'ROOM' . $session_id,
          'room_status' => 'waiting',
          'created_by' => null
      ];
  }
  
  public function cleanupDuplicates($session_id) {
      // Remove duplicate participants (keep the latest one for each user)
      $query = "
          DELETE p1 FROM participants p1
          INNER JOIN participants p2 
          WHERE p1.session_id = ? 
          AND p2.session_id = ?
          AND p1.participant_id < p2.participant_id
          AND (
              (p1.user_id IS NOT NULL AND p1.user_id = p2.user_id) OR
              (p1.user_id IS NULL AND p1.guest_name = p2.guest_name)
          )
      ";
      
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "ii", $session_id, $session_id);
      $success = mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      
      return $success;
  }
  
  public function getQuizStatus($session_id) {
      // Use correct column name: room_status
      $query = "SELECT room_status FROM quiz_sessions WHERE session_id = ?";
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "i", $session_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $row = mysqli_fetch_assoc($result);
      mysqli_stmt_close($stmt);
      
      return $row ? $row['room_status'] : 'waiting';
  }
  
  public function updateQuizStatus($session_id, $status) {
      // Update room_status in quiz_sessions
      $query = "UPDATE quiz_sessions SET room_status = ? WHERE session_id = ?";
      $stmt = mysqli_prepare($this->koneksi, $query);
      mysqli_stmt_bind_param($stmt, "si", $status, $session_id);
      $success = mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      
      return $success;
  }
}
?>