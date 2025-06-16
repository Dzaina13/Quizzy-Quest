<?php
// debug_participants_v2.php
require_once 'assets/php/koneksi_db.php';

$session_id = $_GET['session_id'] ?? 4;

echo "<h2>🔍 DEBUG PARTICIPANTS V2 - Session ID: $session_id</h2>";

// 1. Check users table first
echo "<h3>1. Users Table Check:</h3>";
$users_query = "SELECT user_id, user_name FROM users";
$users_result = mysqli_query($koneksi, $users_query);

if ($users_result) {
  echo "Total users in database: " . mysqli_num_rows($users_result) . "<br>";
  echo "<table border='1' style='border-collapse: collapse;'>";
  echo "<tr><th>User ID</th><th>User Name</th></tr>";
  while ($user = mysqli_fetch_assoc($users_result)) {
      echo "<tr><td>" . $user['user_id'] . "</td><td>" . $user['user_name'] . "</td></tr>";
  }
  echo "</table><br>";
} else {
  echo "❌ Users query failed: " . mysqli_error($koneksi) . "<br>";
}

// 2. Check specific users that participants reference
echo "<h3>2. Specific Users Check (ID 8 & 9):</h3>";
$specific_users = mysqli_query($koneksi, "SELECT user_id, user_name FROM users WHERE user_id IN (8, 9)");
if ($specific_users && mysqli_num_rows($specific_users) > 0) {
  while ($user = mysqli_fetch_assoc($specific_users)) {
      echo "✅ User ID " . $user['user_id'] . ": " . $user['user_name'] . "<br>";
  }
} else {
  echo "❌ Users 8 and 9 NOT FOUND in users table!<br>";
}

// 3. Test JOIN query step by step
echo "<h3>3. JOIN Query Debug:</h3>";

// First, let's try a simple JOIN without WHERE clause
$simple_join = "
  SELECT 
      p.participant_id,
      p.user_id,
      p.session_id,
      p.is_guest,
      p.guest_name,
      u.user_name as registered_username
  FROM participants p
  LEFT JOIN users u ON p.user_id = u.user_id
  LIMIT 10
";

echo "<strong>Simple JOIN test (all participants):</strong><br>";
$simple_result = mysqli_query($koneksi, $simple_join);
if ($simple_result) {
  echo "Rows: " . mysqli_num_rows($simple_result) . "<br>";
  while ($row = mysqli_fetch_assoc($simple_result)) {
      echo "Participant " . $row['participant_id'] . " (Session " . $row['session_id'] . "): ";
      echo "user_id=" . ($row['user_id'] ?: 'NULL') . ", ";
      echo "username=" . ($row['registered_username'] ?: 'NULL') . ", ";
      echo "guest_name=" . ($row['guest_name'] ?: 'NULL') . "<br>";
  }
} else {
  echo "❌ Simple JOIN failed: " . mysqli_error($koneksi) . "<br>";
}

// 4. Now test with session filter
echo "<br><strong>JOIN with session filter:</strong><br>";
$session_join = "
  SELECT 
      p.participant_id,
      p.user_id,
      p.is_guest,
      p.guest_name,
      p.is_host,
      p.connection_status,
      p.join_time,
      u.user_name as registered_username
  FROM participants p
  LEFT JOIN users u ON p.user_id = u.user_id
  WHERE p.session_id = $session_id
  ORDER BY p.is_host DESC, p.join_time ASC
";

$session_result = mysqli_query($koneksi, $session_join);
if ($session_result) {
  echo "Query: " . str_replace("\n", " ", $session_join) . "<br>";
  echo "Rows found: " . mysqli_num_rows($session_result) . "<br><br>";
  
  if (mysqli_num_rows($session_result) > 0) {
      echo "<table border='1' style='border-collapse: collapse;'>";
      echo "<tr><th>ID</th><th>User ID</th><th>Is Guest</th><th>Guest Name</th><th>Registered Name</th><th>Final Name</th><th>Status</th></tr>";
      
      while ($row = mysqli_fetch_assoc($session_result)) {
          // Apply the same logic as LiveRoomHandler
          $display_name = '';
          if ($row['is_guest'] == 1) {
              $display_name = $row['guest_name'] ?: 'Guest User';
          } else {
              $display_name = $row['registered_username'] ?: 'Unknown User';
          }
          
          echo "<tr>";
          echo "<td>" . $row['participant_id'] . "</td>";
          echo "<td>" . ($row['user_id'] ?: 'NULL') . "</td>";
          echo "<td>" . $row['is_guest'] . "</td>";
          echo "<td>" . ($row['guest_name'] ?: 'NULL') . "</td>";
          echo "<td>" . ($row['registered_username'] ?: 'NULL') . "</td>";
          echo "<td><strong>" . $display_name . "</strong></td>";
          echo "<td>" . $row['connection_status'] . "</td>";
          echo "</tr>";
      }
      echo "</table>";
  } else {
      echo "❌ No results with session filter!<br>";
  }
} else {
  echo "❌ Session JOIN failed: " . mysqli_error($koneksi) . "<br>";
}

// 5. Manual test - what should the final result be?
echo "<h3>4. Expected Results:</h3>";
echo "Based on the data we have:<br>";
echo "- Participant 6: user_id=8, should show username from users table<br>";
echo "- Participant 7: user_id=8, should show username from users table<br>";
echo "- Participant 9: user_id=9, should show username from users table (NOT 'damar' because is_guest=0)<br>";

// 6. Test the actual LiveRoomHandler
echo "<h3>5. LiveRoomHandler Test:</h3>";
try {
  require_once 'assets/php/live_room_handler.php';
  $handler = new LiveRoomHandler($koneksi);
  
  // Let's also check what getParticipants returns
  $participants = $handler->getParticipants($session_id);
  
  echo "Handler returned " . count($participants) . " participants<br>";
  
  if (!empty($participants)) {
      echo "<pre>" . json_encode($participants, JSON_PRETTY_PRINT) . "</pre>";
  } else {
      echo "❌ Handler returned empty array<br>";
      
      // Let's check if there's an error in the handler
      echo "<br><strong>Let's check what's happening in the handler...</strong><br>";
      
      // Test the exact query from handler
      $handler_query = "
          SELECT 
              p.participant_id,
              p.user_id,
              p.is_guest,
              p.guest_name,
              p.is_host,
              p.connection_status,
              p.join_time,
              u.user_name as registered_username
          FROM participants p
          LEFT JOIN users u ON p.user_id = u.user_id
          WHERE p.session_id = $session_id AND p.connection_status = 'connected'
          ORDER BY p.is_host DESC, p.join_time ASC
      ";
      
      $handler_result = mysqli_query($koneksi, $handler_query);
      if ($handler_result) {
          echo "Handler query rows: " . mysqli_num_rows($handler_result) . "<br>";
          
          $count = 0;
          while ($row = mysqli_fetch_assoc($handler_result)) {
              $count++;
              echo "Row $count: ";
              
              $display_name = '';
              if ($row['is_guest'] == 1) {
                  $display_name = $row['guest_name'] ?: 'Guest User';
              } else {
                  $display_name = $row['registered_username'] ?: 'Unknown User';
              }
              
              echo "display_name='$display_name', ";
              echo "is_guest=" . $row['is_guest'] . ", ";
              echo "guest_name=" . ($row['guest_name'] ?: 'NULL') . ", ";
              echo "registered_username=" . ($row['registered_username'] ?: 'NULL');
              
              // Check if this row would be skipped
              if (empty($display_name) || $display_name === 'Unknown User') {
                  echo " → <strong>SKIPPED!</strong>";
              } else {
                  echo " → <strong>INCLUDED</strong>";
              }
              echo "<br>";
          }
      } else {
          echo "❌ Handler query failed: " . mysqli_error($koneksi) . "<br>";
      }
  }
  
} catch (Exception $e) {
  echo "❌ Handler error: " . $e->getMessage() . "<br>";
}

?>