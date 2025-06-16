<?php
// pages/join_room.php
session_start();
require_once '../assets/php/koneksi_db.php';

// Check if user is logged in - FIXED: menggunakan user_name
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_name']);
$current_username = $is_logged_in ? $_SESSION['user_name'] : '';

// Handle form submission
if ($_POST) {
  $room_code = strtoupper(trim($_POST['room_code']));
  
  if ($is_logged_in) {
      $username = $_SESSION['user_name']; // FIXED: user_name bukan username
      $user_id = $_SESSION['user_id'];
      $is_guest = 0;
  } else {
      $username = trim($_POST['username']);
      $user_id = null;
      $is_guest = 1;
  }
  
  if (empty($room_code) || (!$is_logged_in && empty($username))) {
      $error = "Room code" . (!$is_logged_in ? " dan username" : "") . " harus diisi!";
  } else {
      // Check if room exists and is active
      $stmt = mysqli_prepare($koneksi, "
          SELECT qs.session_id, qs.room_status, q.title, q.quiz_type,
                 COUNT(p.participant_id) as current_participants
          FROM quiz_sessions qs 
          JOIN quizzes q ON qs.quiz_id = q.quiz_id 
          LEFT JOIN participants p ON qs.session_id = p.session_id
          WHERE qs.room_code = ? AND qs.is_live_room = 1
          GROUP BY qs.session_id
      ");
      mysqli_stmt_bind_param($stmt, "s", $room_code);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      $room = mysqli_fetch_assoc($result);
      
      if (!$room) {
          $error = "Room code tidak ditemukan atau sudah berakhir!";
      } elseif ($room['room_status'] === 'finished') {
          $error = "Room sudah berakhir!";
      } else {
          // Check if participant already exists in this room
          if ($is_logged_in) {
              // For logged in users, check by user_id
              $stmt2 = mysqli_prepare($koneksi, "
                  SELECT participant_id FROM participants 
                  WHERE session_id = ? AND user_id = ?
              ");
              mysqli_stmt_bind_param($stmt2, "ii", $room['session_id'], $user_id);
          } else {
              // For guests, check by guest_name
              $stmt2 = mysqli_prepare($koneksi, "
                  SELECT participant_id FROM participants 
                  WHERE session_id = ? AND guest_name = ? AND is_guest = 1
              ");
              mysqli_stmt_bind_param($stmt2, "is", $room['session_id'], $username);
          }
          
          mysqli_stmt_execute($stmt2);
$existing = mysqli_stmt_get_result($stmt2);

if (mysqli_num_rows($existing) > 0) {
    // Already joined - set session and redirect directly
    $participant_data = mysqli_fetch_assoc($existing);
    
    $_SESSION['participant_id'] = $participant_data['participant_id'];
    $_SESSION['session_id'] = $room['session_id'];
    $_SESSION['participant_username'] = $username;
    $_SESSION['is_host'] = false;
    $_SESSION['is_guest'] = $is_guest;
    
    // Redirect langsung ke waiting room
    header("Location: live_waiting.php?session_id=" . $room['session_id']);
    exit();
} else {
    // Join the room - FIXED: menggunakan guest_name
    $stmt3 = mysqli_prepare($koneksi, "
        INSERT INTO participants (session_id, user_id, is_guest, guest_name, is_host, connection_status, join_time) 
        VALUES (?, ?, ?, ?, 0, 'connected', NOW())
    ");
    mysqli_stmt_bind_param($stmt3, "iiis", $room['session_id'], $user_id, $is_guest, $username);
    
    if (mysqli_stmt_execute($stmt3)) {
        $_SESSION['participant_id'] = mysqli_insert_id($koneksi);
        $_SESSION['session_id'] = $room['session_id'];
        $_SESSION['participant_username'] = $username;
        $_SESSION['is_host'] = false;
        $_SESSION['is_guest'] = $is_guest;
        
        header("Location: live_waiting.php?session_id=" . $room['session_id']);
        exit();
    } else {
        $error = "Gagal bergabung ke room!";
    }
}

      }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Join Live Room - QuizApp</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
      .gradient-bg {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }
      .glass-effect {
          backdrop-filter: blur(20px);
          background: rgba(255, 255, 255, 0.1);
          border: 1px solid rgba(255, 255, 255, 0.2);
      }
      .floating-animation {
          animation: float 6s ease-in-out infinite;
      }
      @keyframes float {
          0%, 100% { transform: translateY(0px); }
          50% { transform: translateY(-20px); }
      }
      .pulse-ring {
          animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
      }
      @keyframes pulse-ring {
          0% { transform: scale(0.8); opacity: 1; }
          80%, 100% { transform: scale(1.2); opacity: 0; }
      }
  </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
  <!-- Background Elements -->
  <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-white opacity-10 rounded-full floating-animation"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-white opacity-5 rounded-full floating-animation" style="animation-delay: -3s;"></div>
      <div class="absolute top-1/2 left-1/4 w-32 h-32 bg-white opacity-10 rounded-full floating-animation" style="animation-delay: -1s;"></div>
  </div>

  <div class="relative z-10 w-full max-w-md">
      <!-- Header -->
      <div class="text-center mb-8">
          <div class="relative inline-block mb-6">
              <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-2xl">
                  <i class="fas fa-broadcast-tower text-3xl text-purple-600"></i>
              </div>
              <div class="absolute inset-0 w-20 h-20 bg-red-500 rounded-full pulse-ring"></div>
          </div>
          <h1 class="text-4xl font-bold text-white mb-2">🔴 Join Live Room</h1>
          <p class="text-white/80 text-lg">Masukkan kode room untuk bergabung</p>
      </div>

      <!-- Debug Info (temporary) -->
      <div class="glass-effect rounded-xl p-4 mb-6 border border-blue-500/30">
          <div class="text-white text-sm">
              <p><strong>Debug Info:</strong></p>
              <p>Login Status: <?= $is_logged_in ? 'Logged In' : 'Guest' ?></p>
              <?php if ($is_logged_in): ?>
                  <p>User ID: <?= $_SESSION['user_id'] ?? 'Not set' ?></p>
                  <p>Username: <?= $_SESSION['user_name'] ?? 'Not set' ?></p>
              <?php endif; ?>
          </div>
      </div>

      <!-- User Status Info -->
      <?php if ($is_logged_in): ?>
          <div class="glass-effect rounded-xl p-4 mb-6 border border-green-500/30">
              <div class="flex items-center text-white">
                  <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3">
                      <i class="fas fa-user text-white"></i>
                  </div>
                  <div>
                      <p class="font-semibold">Selamat datang, <?= htmlspecialchars($current_username) ?>!</p>
                      <p class="text-white/70 text-sm">Anda login sebagai user terdaftar</p>
                  </div>
              </div>
          </div>
      <?php else: ?>
          <div class="glass-effect rounded-xl p-4 mb-6 border border-yellow-500/30">
              <div class="flex items-center text-white">
                  <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center mr-3">
                      <i class="fas fa-user-secret text-white"></i>
                  </div>
                  <div>
                      <p class="font-semibold">Mode Guest</p>
                      <p class="text-white/70 text-sm">Anda akan bergabung sebagai guest</p>
                  </div>
              </div>
          </div>
      <?php endif; ?>

      <!-- Main Card -->
      <div class="glass-effect rounded-2xl p-8 shadow-2xl">
          <?php if (isset($error)): ?>
              <div class="bg-red-500/20 border border-red-500/30 text-red-100 px-4 py-3 rounded-lg mb-6 flex items-center">
                  <i class="fas fa-exclamation-triangle mr-3"></i>
                  <span><?= htmlspecialchars($error) ?></span>
              </div>
          <?php endif; ?>

          <form method="POST" class="space-y-6">
              <!-- Room Code Input -->
              <div>
                  <label class="block text-white/90 text-sm font-semibold mb-3">
                      <i class="fas fa-key mr-2"></i>
                      Room Code
                  </label>
                  <div class="relative">
                      <input 
                          type="text" 
                          name="room_code" 
                          placeholder="Masukkan 6 digit kode room"
                          value="<?= htmlspecialchars($_POST['room_code'] ?? '') ?>"
                          class="w-full px-4 py-4 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition-all duration-200 text-center text-2xl font-bold tracking-widest uppercase"
                          maxlength="6"
                          required
                          oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                      >
                      <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                          <i class="fas fa-qrcode text-white/60"></i>
                      </div>
                  </div>
                  <p class="text-white/60 text-xs mt-2">Kode terdiri dari 6 karakter (huruf & angka)</p>
              </div>

              <!-- Username Input (only for guests) -->
              <?php if (!$is_logged_in): ?>
                  <div>
                      <label class="block text-white/90 text-sm font-semibold mb-3">
                          <i class="fas fa-user-secret mr-2"></i>
                          Username Guest
                      </label>
                      <div class="relative">
                          <input 
                              type="text" 
                              name="username" 
                              placeholder="Masukkan nama Anda"
                              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                              class="w-full px-4 py-4 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent transition-all duration-200"
                              required
                              maxlength="50"
                          >
                          <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                              <i class="fas fa-id-badge text-white/60"></i>
                          </div>
                      </div>
                      <p class="text-white/60 text-xs mt-2">Nama yang akan ditampilkan di room (sebagai guest)</p>
                  </div>
              <?php else: ?>
                  <!-- Display current username for logged in users -->
                  <div>
                      <label class="block text-white/90 text-sm font-semibold mb-3">
                          <i class="fas fa-user mr-2"></i>
                          Username Anda
                      </label>
                      <div class="relative">
                          <div class="w-full px-4 py-4 bg-green-500/20 border border-green-500/30 rounded-xl text-white flex items-center">
                              <i class="fas fa-check-circle text-green-400 mr-3"></i>
                              <span class="font-semibold"><?= htmlspecialchars($current_username) ?></span>
                              <span class="ml-auto text-green-400 text-xs bg-green-500/20 px-2 py-1 rounded-full">Verified</span>
                          </div>
                      </div>
                      <p class="text-white/60 text-xs mt-2">Username dari akun terdaftar Anda</p>
                  </div>
              <?php endif; ?>

              <!-- Submit Button -->
              <button 
                  type="submit" 
                  class="w-full bg-gradient-to-r from-green-500 to-blue-600 text-white py-4 px-6 rounded-xl font-bold text-lg hover:from-green-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center group"
              >
                  <i class="fas fa-sign-in-alt mr-3 group-hover:translate-x-1 transition-transform duration-200"></i>
                  <span>Bergabung ke Room</span>
              </button>
          </form>

          <!-- Divider -->
          <div class="flex items-center my-8">
              <div class="flex-1 border-t border-white/20"></div>
              <span class="px-4 text-white/60 text-sm">atau</span>
              <div class="flex-1 border-t border-white/20"></div>
          </div>

          <!-- Quick Actions -->
          <div class="space-y-3">
              <?php if ($is_logged_in): ?>
                  <a href="../dashboard.php" class="w-full bg-white/10 hover:bg-white/20 text-white py-3 px-4 rounded-xl font-medium transition-all duration-200 flex items-center justify-center border border-white/20">
                      <i class="fas fa-home mr-3"></i>
                      Kembali ke Dashboard
                  </a>
              <?php else: ?>
                  <a href="../login.php" class="w-full bg-blue-500/20 hover:bg-blue-500/30 text-white py-3 px-4 rounded-xl font-medium transition-all duration-200 flex items-center justify-center border border-blue-500/30">
                      <i class="fas fa-sign-in-alt mr-3"></i>
                      Login untuk Akses Penuh
                  </a>
                  <a href="../register.php" class="w-full bg-purple-500/20 hover:bg-purple-500/30 text-white py-3 px-4 rounded-xl font-medium transition-all duration-200 flex items-center justify-center border border-purple-500/30">
                      <i class="fas fa-user-plus mr-3"></i>
                      Daftar Akun Baru
                  </a>
              <?php endif; ?>
              
              <button onclick="showHowToJoin()" class="w-full bg-white/10 hover:bg-white/20 text-white py-3 px-4 rounded-xl font-medium transition-all duration-200 flex items-center justify-center border border-white/20">
                  <i class="fas fa-question-circle mr-3"></i>
                  Cara Bergabung ke Room
              </button>
          </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-8">
          <p class="text-white/60 text-sm">
              <?php if (!$is_logged_in): ?>
                  Sudah punya akun? 
                  <a href="../login.php" class="text-white hover:underline font-semibold">Login di sini</a>
              <?php else: ?>
                  Butuh bantuan? 
                  <a href="#" class="text-white hover:underline font-semibold">Hubungi Admin</a>
              <?php endif; ?>
          </p>
      </div>
  </div>

  <!-- How to Join Modal -->
  <div id="howToJoinModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
          <div class="text-center mb-6">
              <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="fas fa-info text-white text-2xl"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-900">Cara Bergabung ke Live Room</h3>
          </div>
          
          <div class="space-y-4 text-sm text-gray-600">
              <div class="flex items-start">
                  <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">1</div>
                  <p>Dapatkan <strong>Room Code</strong> dari host/admin (6 karakter)</p>
              </div>
              <div class="flex items-start">
                  <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">2</div>
                  <p>Masukkan Room Code di form di atas</p>
              </div>
              <div class="flex items-start">
                  <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">3</div>
                  <p><?php if ($is_logged_in): ?>Username Anda akan otomatis terisi<?php else: ?>Masukkan username sebagai guest<?php endif; ?></p>
              </div>
              <div class="flex items-start">
                  <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 mt-0.5">4</div>
                  <p>Klik "Bergabung ke Room" dan tunggu quiz dimulai!</p>
              </div>
          </div>
          
          <div class="mt-6 pt-4 border-t border-gray-200">
              <button onclick="hideHowToJoin()" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200">
                  Mengerti!
              </button>
          </div>
      </div>
  </div>

  <script>
      function showHowToJoin() {
          document.getElementById('howToJoinModal').classList.remove('hidden');
      }
      
      function hideHowToJoin() {
          document.getElementById('howToJoinModal').classList.add('hidden');
      }
      
      // Auto format room code input
      document.querySelector('input[name="room_code"]').addEventListener('input', function(e) {
          let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
          if (value.length > 6) value = value.substring(0, 6);
          e.target.value = value;
      });
  </script>
</body>
</html>