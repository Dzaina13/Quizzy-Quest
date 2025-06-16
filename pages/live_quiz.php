<?php
session_start();

// Include database connection and live room handler
require_once '../assets/php/koneksi_db.php';
require_once '../assets/php/live_room_handler.php';

// Initialize LiveRoomHandler
$roomHandler = new LiveRoomHandler($conn);

// Get session_id from URL or session
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : (isset($_SESSION['session_id']) ? $_SESSION['session_id'] : null);

if (!$session_id) {
  die("Session ID tidak ditemukan. <a href='../pages/create_room.php'>Buat Room Baru</a>");
}

$_SESSION['session_id'] = $session_id;

// Get current user info
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guest_name = isset($_SESSION['guest_name']) ? $_SESSION['guest_name'] : null;

// Get quiz type from session info (from quizzes table)
$stmt = $conn->prepare("
  SELECT q.quiz_type 
  FROM quiz_sessions qs 
  JOIN quizzes q ON qs.quiz_id = q.quiz_id 
  WHERE qs.session_id = ?
");
$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$quiz_data = $result->fetch_assoc();
$quiz_type = $quiz_data ? $quiz_data['quiz_type'] : 'normal';
$stmt->close();

// Store quiz type in session
$_SESSION['quiz_type'] = $quiz_type;

// Initialize session variables
if (!isset($_SESSION['current_question'])) {
  $_SESSION['current_question'] = 1;
  $_SESSION['score'] = 0;
  $_SESSION['answers'] = [];
  $_SESSION['quiz_started'] = false;
  $_SESSION['participant_id'] = null;
}

// Join room if not already joined
if (!$_SESSION['participant_id']) {
  if ($current_user_id) {
      // Check if user is already in session
      $stmt = $conn->prepare("SELECT participant_id FROM participants WHERE session_id = ? AND user_id = ? AND connection_status = 'connected'");
      $stmt->bind_param("ii", $session_id, $current_user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($row = $result->fetch_assoc()) {
          $_SESSION['participant_id'] = $row['participant_id'];
      } else {
          $is_host = $roomHandler->isUserHost($session_id, $current_user_id);
          $_SESSION['participant_id'] = $roomHandler->addParticipant($session_id, $current_user_id, null, $is_host);
      }
      $stmt->close();
  } else if ($guest_name) {
      $_SESSION['participant_id'] = $roomHandler->addParticipant($session_id, null, $guest_name, false);
  }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['start_quiz']) && $roomHandler->isUserHost($session_id, $current_user_id)) {
      // Only host can start quiz
      $_SESSION['quiz_started'] = true;
      $_SESSION['start_time'] = time();
      $roomHandler->updateQuizStatus($session_id, 'active');
      
  } elseif (isset($_POST['submit_answer'])) {
      $question_id = intval($_POST['question_id']);
      $selected_answer = $_POST['answer'];
      
      // Store the answer
      $_SESSION['answers'][$question_id] = $selected_answer;
      
      // Save answer to appropriate table based on quiz type
      if ($quiz_type === 'rof') {
          // ROF Quiz - save to rof_answers table
          $stmt = $conn->prepare("
              INSERT INTO rof_answers (rof_participant_id, rof_question_id, answer, answered_at) 
              VALUES (?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE answer = VALUES(answer), answered_at = VALUES(answered_at)
          ");
          $stmt->bind_param("iis", $_SESSION['participant_id'], $question_id, $selected_answer);
          $stmt->execute();
          $stmt->close();
          
          // Check if answer is correct for ROF (True/False)
          $stmt = $conn->prepare("SELECT correct_answer FROM rof_questions WHERE rof_question_id = ?");
          $stmt->bind_param("i", $question_id);
          $stmt->execute();
          $result = $stmt->get_result();
          $row = $result->fetch_assoc();
          $correct_answer = $row['correct_answer'];
          
          // Update is_correct in rof_answers
          $is_correct = ($selected_answer === $correct_answer) ? 1 : 0;
          $stmt = $conn->prepare("UPDATE rof_answers SET is_correct = ? WHERE rof_participant_id = ? AND rof_question_id = ?");
          $stmt->bind_param("iii", $is_correct, $_SESSION['participant_id'], $question_id);
          $stmt->execute();
          $stmt->close();
          
      } else {
          // Regular Quiz - save to user_answers table
          $stmt = $conn->prepare("
              INSERT INTO user_answers (participant_id, question_id, chosen_answer, answered_at) 
              VALUES (?, ?, ?, NOW())
              ON DUPLICATE KEY UPDATE chosen_answer = VALUES(chosen_answer), answered_at = VALUES(answered_at)
          ");
          $stmt->bind_param("iis", $_SESSION['participant_id'], $question_id, $selected_answer);
          $stmt->execute();
          $stmt->close();
          
          // Check if answer is correct for regular quiz (A/B/C/D)
          $stmt = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
          $stmt->bind_param("i", $question_id);
          $stmt->execute();
          $result = $stmt->get_result();
          $row = $result->fetch_assoc();
          $correct_answer = $row['correct_answer'];
          
          // Update is_correct in user_answers
          $is_correct = ($selected_answer === $correct_answer) ? 1 : 0;
          $stmt = $conn->prepare("UPDATE user_answers SET is_correct = ? WHERE participant_id = ? AND question_id = ?");
          $stmt->bind_param("iii", $is_correct, $_SESSION['participant_id'], $question_id);
          $stmt->execute();
          $stmt->close();
      }
      
      if ($selected_answer === $correct_answer) {
          $_SESSION['score']++;
      }
      
      $_SESSION['current_question']++;
      
  } elseif (isset($_POST['restart_quiz']) && $roomHandler->isUserHost($session_id, $current_user_id)) {
      // Only host can restart quiz
      $roomHandler->updateQuizStatus($session_id, 'waiting');
      
      // Reset all participants' progress based on quiz type
      if ($quiz_type === 'rof') {
          $stmt = $conn->prepare("DELETE FROM rof_answers WHERE rof_participant_id IN (SELECT participant_id FROM participants WHERE session_id = ?)");
      } else {
          $stmt = $conn->prepare("DELETE FROM user_answers WHERE participant_id IN (SELECT participant_id FROM participants WHERE session_id = ?)");
      }
      $stmt->bind_param("i", $session_id);
      $stmt->execute();
      $stmt->close();
      
      // Reset session
      $_SESSION['current_question'] = 1;
      $_SESSION['score'] = 0;
      $_SESSION['answers'] = [];
      $_SESSION['quiz_started'] = false;
      unset($_SESSION['start_time']);
  }
}

// Get session info and participants
$session_info = $roomHandler->getSessionInfo($session_id);
$participants = $roomHandler->getParticipants($session_id);
$quiz_status = $roomHandler->getQuizStatus($session_id);
$is_host = $roomHandler->isUserHost($session_id, $current_user_id);

// Sync quiz status with room status
if ($quiz_status === 'active' && !$_SESSION['quiz_started']) {
  $_SESSION['quiz_started'] = true;
  if (!isset($_SESSION['start_time'])) {
      $_SESSION['start_time'] = time();
  }
}

// Get total number of questions based on quiz type
if ($quiz_type === 'rof') {
  $result = $conn->query("SELECT COUNT(*) as total FROM rof_questions");
} else {
  $result = $conn->query("SELECT COUNT(*) as total FROM questions");
}
$row = $result->fetch_assoc();
$total_questions = $row['total'];

// Check if quiz is completed
$quiz_completed = $_SESSION['current_question'] > $total_questions;

// Get current question if quiz is not completed
$current_question = null;
if ($_SESSION['quiz_started'] && !$quiz_completed) {
  $current_question_id = $_SESSION['current_question'];
  
  if ($quiz_type === 'rof') {
      $stmt = $conn->prepare("SELECT * FROM rof_questions WHERE rof_question_id = ?");
  } else {
      $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
  }
  
  $stmt->bind_param("i", $current_question_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $current_question = $result->fetch_assoc();
  $stmt->close();
}

// Calculate time elapsed
$time_elapsed = 0;
if (isset($_SESSION['start_time'])) {
  $time_elapsed = time() - $_SESSION['start_time'];
}

// Get leaderboard based on quiz type
$leaderboard = [];
if ($_SESSION['quiz_started'] || $quiz_completed) {
  if ($quiz_type === 'rof') {
      $stmt = $conn->prepare("
          SELECT 
              p.participant_id,
              CASE 
                  WHEN p.is_guest = 1 THEN p.guest_name 
                  ELSE u.username 
              END as display_name,
              COUNT(CASE WHEN ra.is_correct = 1 THEN 1 END) as score,
              COUNT(ra.rof_answer_id) as answered_questions,
              p.is_host
          FROM participants p
          LEFT JOIN users u ON p.user_id = u.user_id
          LEFT JOIN rof_answers ra ON p.participant_id = ra.rof_participant_id
          WHERE p.session_id = ? AND p.connection_status = 'connected'
          GROUP BY p.participant_id
          ORDER BY score DESC, answered_questions DESC
      ");
  } else {
      $stmt = $conn->prepare("
          SELECT 
              p.participant_id,
              CASE 
                  WHEN p.is_guest = 1 THEN p.guest_name 
                  ELSE u.username 
              END as display_name,
              COUNT(CASE WHEN ua.is_correct = 1 THEN 1 END) as score,
              COUNT(ua.answer_id) as answered_questions,
              p.is_host
          FROM participants p
          LEFT JOIN users u ON p.user_id = u.user_id
          LEFT JOIN user_answers ua ON p.participant_id = ua.participant_id
          WHERE p.session_id = ? AND p.connection_status = 'connected'
          GROUP BY p.participant_id
          ORDER BY score DESC, answered_questions DESC
      ");
  }
  
  $stmt->bind_param("i", $session_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
      $leaderboard[] = $row;
  }
  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Quiz <?= ucfirst($quiz_type) ?> - <?= htmlspecialchars($session_info['session_name']) ?></title>
  
  <!-- CSS Files -->
  <link rel="stylesheet" href="../assets/css/live_quiz.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
      .room-info {
          background: <?= $quiz_type === 'rof' ? 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' ?>;
          color: white;
          padding: 1rem;
          border-radius: 12px;
          margin-bottom: 1rem;
          text-align: center;
      }
      
      .quiz-type-badge {
          background: rgba(255,255,255,0.2);
          padding: 0.25rem 0.75rem;
          border-radius: 20px;
          font-size: 0.875rem;
          margin-left: 0.5rem;
      }
      
      .participants-panel {
          background: white;
          border-radius: 12px;
          padding: 1rem;
          margin-bottom: 1rem;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      }
      
      .participant-list {
          display: flex;
          flex-wrap: wrap;
          gap: 0.5rem;
          margin-top: 0.5rem;
      }
      
      .participant-badge {
          background: #f3f4f6;
          padding: 0.25rem 0.75rem;
          border-radius: 20px;
          font-size: 0.875rem;
          border: 2px solid transparent;
      }
      
      .participant-badge.host {
          background: #fef3c7;
          border-color: #f59e0b;
          color: #92400e;
      }
      
      .participant-badge.current-user {
          background: #dbeafe;
          border-color: #3b82f6;
          color: #1e40af;
      }
      
      .leaderboard {
          background: white;
          border-radius: 12px;
          padding: 1rem;
          margin-top: 1rem;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      }
      
      .leaderboard-item {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 0.5rem;
          border-bottom: 1px solid #e5e7eb;
      }
      
      .leaderboard-item:last-child {
          border-bottom: none;
      }
      
      .leaderboard-rank {
          font-weight: bold;
          color: #6b7280;
          margin-right: 0.5rem;
      }
      
      .leaderboard-score {
          background: #10b981;
          color: white;
          padding: 0.25rem 0.5rem;
          border-radius: 12px;
          font-size: 0.875rem;
          font-weight: bold;
      }
      
      .host-controls {
          background: #fef3c7;
          border: 2px solid #f59e0b;
          border-radius: 12px;
          padding: 1rem;
          margin-bottom: 1rem;
          text-align: center;
      }
      
      .waiting-message {
          background: #e0f2fe;
          border: 2px solid #0288d1;
          border-radius: 12px;
          padding: 1rem;
          text-align: center;
          color: #01579b;
      }
      
      /* ROF specific styles */
      .rof-answers-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 1rem;
          margin: 1.5rem 0;
      }
      
      .rof-answer-option {
          background: white;
          border: 3px solid #e5e7eb;
          border-radius: 12px;
          padding: 1.5rem;
          cursor: pointer;
          transition: all 0.3s ease;
          text-align: center;
          position: relative;
      }
      
      .rof-answer-option:hover {
          border-color: #3b82f6;
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
      }
      
      .rof-answer-option input[type="radio"] {
          display: none;
      }
      
      .rof-answer-option input[type="radio"]:checked + .rof-answer-content {
          background: #3b82f6;
          color: white;
      }
      
      .rof-answer-content {
          border-radius: 8px;
          padding: 1rem;
          transition: all 0.3s ease;
      }
      
      .rof-answer-text {
          font-size: 1.25rem;
          font-weight: 600;
      }
      
      .rof-answer-icon {
          font-size: 2rem;
          margin-bottom: 0.5rem;
          display: block;
      }
      
      .loading-dots {
          display: inline-flex;
          gap: 0.25rem;
          margin-top: 0.5rem;
      }
      
      .loading-dots span {
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: #3b82f6;
          animation: loading-bounce 1.4s infinite ease-in-out both;
      }
      
      .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
      .loading-dots span:nth-child(2) { animation-delay: -0.16s; }
      .loading-dots span:nth-child(3) { animation-delay: 0s; }
      
      @keyframes loading-bounce {
          0%, 80%, 100% { 
              transform: scale(0);
          } 40% { 
              transform: scale(1);
          }
      }
  </style>
</head>
<body>
  <div class="container">
      <!-- Room Info -->
      <div class="room-info">
          <h2>🏠 <?= htmlspecialchars($session_info['session_name']) ?>
              <span class="quiz-type-badge">
                  <?= $quiz_type === 'rof' ? '✓✗ True/False' : '🧠 Multiple Choice' ?>
              </span>
          </h2>
          <p><strong>Room Code:</strong> <?= htmlspecialchars($session_info['room_code']) ?></p>
          <p><strong>Status:</strong> 
              <span class="badge <?= $quiz_status === 'active' ? 'badge-success' : 'badge-warning' ?>">
                  <?= ucfirst($quiz_status) ?>
              </span>
          </p>
      </div>

      <!-- Participants Panel -->
      <div class="participants-panel">
          <h3>👥 Participants (<?= count($participants) ?>)</h3>
          <div class="participant-list">
              <?php foreach ($participants as $participant): ?>
                  <span class="participant-badge <?= $participant['is_host'] ? 'host' : '' ?> <?= $participant['participant_id'] == $_SESSION['participant_id'] ? 'current-user' : '' ?>">
                      <?= $participant['is_host'] ? '👑 ' : '' ?>
                      <?= htmlspecialchars($participant['display_name']) ?>
                  </span>
              <?php endforeach; ?>
          </div>
      </div>

      <header class="quiz-header">
          <h1 class="quiz-title">
              <?= $quiz_type === 'rof' ? '✓✗ Live True/False Quiz' : '🧠 Live Multiple Choice Quiz' ?>
          </h1>
          <?php if ($_SESSION['quiz_started'] && !$quiz_completed): ?>
              <div class="quiz-info">
                  <div class="progress-container">
                      <div class="progress-bar">
                          <div class="progress-fill" style="width: <?= (($_SESSION['current_question'] - 1) / $total_questions) * 100 ?>%"></div>
                      </div>
                      <span class="progress-text">Soal <?= $_SESSION['current_question'] - 1 ?> dari <?= $total_questions ?></span>
                  </div>
                  <div class="timer">
                      <span class="timer-icon">⏱️</span>
                      <span id="timer"><?= gmdate("i:s", $time_elapsed) ?></span>
                  </div>
              </div>
          <?php endif; ?>
      </header>

      <main class="quiz-content">
          <?php if (!$_SESSION['quiz_started'] && $quiz_status !== 'active'): ?>
              <!-- Waiting Screen -->
              <div class="start-screen fade-in">
                  <?php if ($is_host): ?>
                      <div class="host-controls">
                          <h3>🎮 Host Controls</h3>
                          <p>Kamu adalah host room ini. Klik tombol di bawah untuk memulai quiz <?= $quiz_type === 'rof' ? 'True/False' : 'Multiple Choice' ?>!</p>
                          <form method="POST" class="start-form">
                              <button type="submit" name="start_quiz" class="btn btn-primary btn-large pulse-animation">
                                  🚀 Mulai Quiz untuk Semua
                              </button>
                          </form>
                      </div>
                  <?php else: ?>
                      <div class="waiting-message">
                          <h3>⏳ Menunggu Host</h3>
                          <p>Menunggu host untuk memulai quiz <?= $quiz_type === 'rof' ? 'True/False' : 'Multiple Choice' ?>...</p>
                          <div class="loading-dots">
                              <span></span><span></span><span></span>
                          </div>
                      </div>
                  <?php endif; ?>
                  
                  <div class="welcome-card">
                      <h2><?= $quiz_type === 'rof' ? 'Live True/False Quiz!' : 'Live Multiple Choice Quiz!' ?> 🎯</h2>
                      <div class="quiz-stats">
                          <div class="stat-item">
                              <span class="stat-number"><?= $total_questions ?></span>
                              <span class="stat-label">Total Soal</span>
                          </div>
                          <div class="stat-item">
                              <span class="stat-number"><?= count($participants) ?></span>
                              <span class="stat-label">Players</span>
                          </div>
                          <div class="stat-item">
                              <span class="stat-number">100</span>
                              <span class="stat-label">Poin Max</span>
                          </div>
                      </div>
                  </div>
              </div>

          <?php elseif ($quiz_completed): ?>
              <!-- Results Screen -->
              <div class="results-screen fade-in">
                  <div class="results-card">
                      <div class="score-display">
                          <h2>Quiz Selesai! 🎉</h2>
                          <div class="final-score scale-in">
                              <span class="score-number"><?= $_SESSION['score'] ?></span>
                              <span class="score-total">/ <?= $total_questions ?></span>
                          </div>
                          <div class="score-percentage">
                              <?= round(($_SESSION['score'] / $total_questions) * 100) ?>% Benar
                          </div>
                      </div>
                      
                      <div class="performance-stats slide-up">
                          <div class="stat-row">
                              <span class="stat-label">⏱️ Waktu Total:</span>
                              <span class="stat-value"><?= gmdate("i:s", $time_elapsed) ?></span>
                          </div>
                          <div class="stat-row">
                              <span class="stat-label">✅ Jawaban Benar:</span>
                              <span class="stat-value"><?= $_SESSION['score'] ?></span>
                          </div>
                          <div class="stat-row">
                              <span class="stat-label">❌ Jawaban Salah:</span>
                              <span class="stat-value"><?= $total_questions - $_SESSION['score'] ?></span>
                          </div>
                      </div>

                      <?php if ($is_host): ?>
                          <form method="POST" class="restart-form">
                              <button type="submit" name="restart_quiz" class="btn btn-primary btn-large">
                                  🔄 Restart Quiz untuk Semua
                              </button>
                          </form>
                      <?php endif; ?>
                  </div>
              </div>

          <?php else: ?>
              <!-- Question Screen -->
              <div class="question-screen slide-in">
                  <?php if ($current_question): ?>
                      <div class="question-card">
                          <div class="question-header">
                              <span class="question-number">Soal #<?= $_SESSION['current_question'] ?></span>
                              <span class="question-category">
                                  <?= $quiz_type === 'rof' ? '✓✗ True/False' : '🧠 Multiple Choice' ?>
                              </span>
                          </div>
                          
                          <h3 class="question-text">
                              <?= htmlspecialchars($current_question[$quiz_type === 'rof' ? 'rof_question_text' : 'question']) ?>
                          </h3>
                          
                          <form method="POST" class="answer-form" id="answerForm">
                              <input type="hidden" name="question_id" value="<?= $current_question[$quiz_type === 'rof' ? 'rof_question_id' : 'id'] ?>">
                              
                              <?php if ($quiz_type === 'rof'): ?>
                                  <!-- True/False Options -->
                                  <div class="rof-answers-grid">
                                      <label class="rof-answer-option" data-option="T">
                                          <input type="radio" name="answer" value="T" required>
                                          <div class="rof-answer-content">
                                              <span class="rof-answer-icon">✅</span>
                                              <span class="rof-answer-text">TRUE</span>
                                          </div>
                                      </label>
                                      
                                      <label class="rof-answer-option" data-option="F">
                                          <input type="radio" name="answer" value="F" required>
                                          <div class="rof-answer-content">
                                              <span class="rof-answer-icon">❌</span>
                                              <span class="rof-answer-text">FALSE</span>
                                          </div>
                                      </label>
                                  </div>
                              <?php else: ?>
                                  <!-- Multiple Choice Options -->
                                  <div class="answers-grid">
                                      <label class="answer-option" data-option="A">
                                          <input type="radio" name="answer" value="A" required>
                                          <div class="answer-content">
                                              <span class="answer-letter">A</span>
                                              <span class="answer-text"><?= htmlspecialchars($current_question['option_a']) ?></span>
                                          </div>
                                      </label>
                                      
                                      <label class="answer-option" data-option="B">
                                          <input type="radio" name="answer" value="B" required>
                                          <div class="answer-content">
                                              <span class="answer-letter">B</span>
                                              <span class="answer-text"><?= htmlspecialchars($current_question['option_b']) ?></span>
                                          </div>
                                      </label>
                                      
                                      <label class="answer-option" data-option="C">
                                          <input type="radio" name="answer" value="C" required>
                                          <div class="answer-content">
                                              <span class="answer-letter">C</span>
                                              <span class="answer-text"><?= htmlspecialchars($current_question['option_c']) ?></span>
                                          </div>
                                      </label>
                                      
                                      <label class="answer-option" data-option="D">
                                          <input type="radio" name="answer" value="D" required>
                                          <div class="answer-content">
                                              <span class="answer-letter">D</span>
                                              <span class="answer-text"><?= htmlspecialchars($current_question['option_d']) ?></span>
                                          </div>
                                      </label>
                                  </div>
                              <?php endif; ?>
                              
                              <button type="submit" name="submit_answer" class="btn btn-primary btn-large" id="submitBtn">
                                  <?= $_SESSION['current_question'] == $total_questions ? '🏁 Selesai' : '➡️ Lanjut' ?>
                              </button>
                          </form>
                      </div>
                  <?php else: ?>
                      <div class="error-message fade-in">
                          <h3>❌ Soal tidak ditemukan</h3>
                          <p>Terjadi kesalahan dalam memuat soal.</p>
                      </div>
                  <?php endif; ?>
              </div>
          <?php endif; ?>
      </main>
      <!-- Leaderboard -->
      <?php if (!empty($leaderboard)): ?>
          <div class="leaderboard">
              <h3>🏆 Live Leaderboard</h3>
              <?php foreach ($leaderboard as $index => $participant): ?>
                  <div class="leaderboard-item <?= $participant['participant_id'] == $_SESSION['participant_id'] ? 'current-user' : '' ?>">
                      <div style="display: flex; align-items: center;">
                          <span class="leaderboard-rank">
                              <?php 
                                  if ($index == 0) echo "🥇";
                                  elseif ($index == 1) echo "🥈"; 
                                  elseif ($index == 2) echo "🥉";
                                  else echo "#" . ($index + 1);
                              ?>
                          </span>
                          <span>
                              <?= $participant['is_host'] ? '👑 ' : '' ?>
                              <?= htmlspecialchars($participant['display_name']) ?>
                              <?= $participant['participant_id'] == $_SESSION['participant_id'] ? ' (You)' : '' ?>
                          </span>
                      </div>
                      <div style="display: flex; align-items: center; gap: 0.5rem;">
                          <span style="font-size: 0.875rem; color: #6b7280;">
                              <?= $participant['answered_questions'] ?>/<?= $total_questions ?>
                          </span>
                          <span class="leaderboard-score"><?= $participant['score'] ?></span>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>

      <!-- Navigation -->
      <div class="navigation-buttons">
          <a href="../pages/dashboard.php" class="btn btn-secondary">
              🏠 Kembali ke Dashboard
          </a>
          <a href="../pages/create_room.php" class="btn btn-outline">
              ➕ Buat Room Baru
          </a>
      </div>
  </div>

  <!-- Auto-refresh script for real-time updates -->
  <script>
      let refreshInterval;
      let timerInterval;
      let startTime = <?= isset($_SESSION['start_time']) ? $_SESSION['start_time'] : 'null' ?>;
      
      // Timer function
      function updateTimer() {
          if (startTime) {
              const now = Math.floor(Date.now() / 1000);
              const elapsed = now - startTime;
              const minutes = Math.floor(elapsed / 60);
              const seconds = elapsed % 60;
              const timerElement = document.getElementById('timer');
              if (timerElement) {
                  timerElement.textContent = 
                      String(minutes).padStart(2, '0') + ':' + 
                      String(seconds).padStart(2, '0');
              }
          }
      }

      // Auto-refresh function
      function startAutoRefresh() {
          refreshInterval = setInterval(() => {
              // Only refresh if quiz hasn't started or is waiting for host
              const isWaiting = <?= (!$_SESSION['quiz_started'] && $quiz_status !== 'active') ? 'true' : 'false' ?>;
              const isCompleted = <?= $quiz_completed ? 'true' : 'false' ?>;
              
              if (isWaiting || isCompleted) {
                  // Refresh page to get latest status
                  window.location.reload();
              }
          }, 3000); // Refresh every 3 seconds
      }

      // Start timer if quiz is active
      if (startTime) {
          timerInterval = setInterval(updateTimer, 1000);
          updateTimer(); // Initial call
      }

      // Start auto-refresh
      startAutoRefresh();

      // Answer selection handling
      document.addEventListener('DOMContentLoaded', function() {
          const answerOptions = document.querySelectorAll('.answer-option, .rof-answer-option');
          const submitBtn = document.getElementById('submitBtn');
          
          answerOptions.forEach(option => {
              option.addEventListener('click', function() {
                  // Remove active class from all options
                  answerOptions.forEach(opt => opt.classList.remove('selected'));
                  
                  // Add active class to clicked option
                  this.classList.add('selected');
                  
                  // Check the radio button
                  const radio = this.querySelector('input[type="radio"]');
                  if (radio) {
                      radio.checked = true;
                  }
                  
                  // Enable submit button
                  if (submitBtn) {
                      submitBtn.disabled = false;
                      submitBtn.classList.add('btn-ready');
                  }
              });
          });

          // Form submission handling
          const answerForm = document.getElementById('answerForm');
          if (answerForm) {
              answerForm.addEventListener('submit', function(e) {
                  if (submitBtn) {
                      submitBtn.disabled = true;
                      submitBtn.innerHTML = '<span class="loading-spinner"></span> Mengirim...';
                  }
              });
          }

          // Keyboard shortcuts
          document.addEventListener('keydown', function(e) {
              const quizType = '<?= $quiz_type ?>';
              
              if (quizType === 'rof') {
                  // True/False shortcuts
                  if (e.key === 't' || e.key === 'T') {
                      const trueOption = document.querySelector('input[value="T"]');
                      if (trueOption) {
                          trueOption.click();
                          trueOption.closest('.rof-answer-option').click();
                      }
                  } else if (e.key === 'f' || e.key === 'F') {
                      const falseOption = document.querySelector('input[value="F"]');
                      if (falseOption) {
                          falseOption.click();
                          falseOption.closest('.rof-answer-option').click();
                      }
                  }
              } else {
                  // Multiple choice shortcuts
                  if (['a', 'b', 'c', 'd'].includes(e.key.toLowerCase())) {
                      const option = document.querySelector(`input[value="${e.key.toUpperCase()}"]`);
                      if (option) {
                          option.click();
                          option.closest('.answer-option').click();
                      }
                  }
              }
              
              // Enter to submit
              if (e.key === 'Enter' && submitBtn && !submitBtn.disabled) {
                  answerForm.submit();
              }
          });
      });

      // Cleanup intervals when page unloads
      window.addEventListener('beforeunload', function() {
          if (refreshInterval) clearInterval(refreshInterval);
          if (timerInterval) clearInterval(timerInterval);
      });

      // Show connection status
      let connectionStatus = 'connected';
      
      function updateConnectionStatus(status) {
          connectionStatus = status;
          const statusIndicator = document.querySelector('.connection-status');
          if (statusIndicator) {
              statusIndicator.className = `connection-status ${status}`;
              statusIndicator.textContent = status === 'connected' ? '🟢 Connected' : '🔴 Disconnected';
          }
      }

      // Check connection periodically
      setInterval(() => {
          fetch(window.location.href, { method: 'HEAD' })
              .then(() => updateConnectionStatus('connected'))
              .catch(() => updateConnectionStatus('disconnected'));
      }, 10000);

      // Add some visual feedback
      document.addEventListener('DOMContentLoaded', function() {
          // Add entrance animations
          const elements = document.querySelectorAll('.fade-in, .slide-in, .scale-in, .slide-up');
          elements.forEach((el, index) => {
              el.style.animationDelay = `${index * 0.1}s`;
          });
          
          // Add pulse animation to waiting elements
          const waitingElements = document.querySelectorAll('.loading-dots, .pulse-animation');
          waitingElements.forEach(el => {
              el.classList.add('animate-pulse');
          });
      });
  </script>

  <style>
      /* Additional CSS for better UX */
      .selected {
          border-color: #3b82f6 !important;
          background: #eff6ff !important;
          transform: translateY(-2px);
          box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
      }
      
      .selected .answer-content,
      .selected .rof-answer-content {
          background: #3b82f6 !important;
          color: white !important;
      }
      
      .btn-ready {
          background: #10b981 !important;
          transform: scale(1.05);
      }
      
      .loading-spinner {
          display: inline-block;
          width: 16px;
          height: 16px;
          border: 2px solid #ffffff;
          border-radius: 50%;
          border-top-color: transparent;
          animation: spin 1s ease-in-out infinite;
      }
      
      @keyframes spin {
          to { transform: rotate(360deg); }
      }
      
      .connection-status {
          position: fixed;
          top: 10px;
          right: 10px;
          padding: 0.5rem 1rem;
          border-radius: 20px;
          font-size: 0.875rem;
          font-weight: 500;
          z-index: 1000;
      }
      
      .connection-status.connected {
          background: #d1fae5;
          color: #065f46;
          border: 1px solid #10b981;
      }
      
      .connection-status.disconnected {
          background: #fee2e2;
          color: #991b1b;
          border: 1px solid #ef4444;
      }
      
      .animate-pulse {
          animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
      }
      
      @keyframes pulse {
          0%, 100% {
              opacity: 1;
          }
          50% {
              opacity: .5;
          }
      }
      
      /* Responsive improvements */
      @media (max-width: 768px) {
          .rof-answers-grid {
              grid-template-columns: 1fr;
              gap: 0.75rem;
          }
          
          .answers-grid {
              grid-template-columns: 1fr;
              gap: 0.75rem;
          }
          
          .quiz-stats {
              flex-direction: column;
              gap: 1rem;
          }
          
          .participant-list {
              justify-content: center;
          }
          
          .leaderboard-item {
              flex-direction: column;
              align-items: flex-start;
              gap: 0.5rem;
          }
      }
      
      /* Enhanced animations */
      .fade-in {
          animation: fadeIn 0.6s ease-out forwards;
      }
      
      .slide-in {
          animation: slideIn 0.6s ease-out forwards;
      }
      
      .scale-in {
          animation: scaleIn 0.6s ease-out forwards;
      }
      
      .slide-up {
          animation: slideUp 0.6s ease-out forwards;
      }
      
      @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
      }
      
      @keyframes slideIn {
          from { 
              opacity: 0; 
              transform: translateX(-30px); 
          }
          to { 
              opacity: 1; 
              transform: translateX(0); 
          }
      }
      
      @keyframes scaleIn {
          from { 
              opacity: 0; 
              transform: scale(0.9); 
          }
          to { 
              opacity: 1; 
              transform: scale(1); 
          }
      }
      
      @keyframes slideUp {
          from { 
              opacity: 0; 
              transform: translateY(20px); 
          }
          to { 
              opacity: 1; 
              transform: translateY(0); 
          }
      }
      
      /* Leaderboard current user highlight */
      .leaderboard-item.current-user {
          background: #eff6ff;
          border: 2px solid #3b82f6;
          border-radius: 8px;
          font-weight: 600;
      }
      
      /* Navigation buttons styling */
      .navigation-buttons {
          display: flex;
          gap: 1rem;
          justify-content: center;
          margin-top: 2rem;
          padding-top: 2rem;
          border-top: 1px solid #e5e7eb;
      }
      
      .navigation-buttons .btn {
          min-width: 150px;
      }
  </style>

  <!-- Connection status indicator -->
  <div class="connection-status connected">
      🟢 Connected
  </div>
</body>
</html>