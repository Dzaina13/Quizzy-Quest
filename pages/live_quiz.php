<?php
session_start();
require_once '../assets/php/koneksi_db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Debug function
function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] DEBUG: $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    error_log($logMessage);
    
    // Also output to console if in development
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo "<script>console.log(" . json_encode($logMessage) . ");</script>";
    }
}

debugLog("=== LIVE QUIZ START ===");

if (!isset($koneksi) || $koneksi->connect_error) {
    debugLog("Database connection failed", $koneksi->connect_error ?? 'Unknown error');
    die("Database connection failed. Please check your connection settings.");
}

require_once '../assets/php/live_room_handler.php';
$roomHandler = new LiveRoomHandler($koneksi);

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : (isset($_SESSION['session_id']) ? $_SESSION['session_id'] : null);

debugLog("Session ID received", ['session_id' => $session_id, 'from_get' => isset($_GET['session_id']), 'from_session' => isset($_SESSION['session_id'])]);

if (!$session_id) {
    debugLog("Session ID not found - redirecting");
    die("Session ID tidak ditemukan. <a href='../pages/create_room.php'>Buat Room Baru</a>");
}

$_SESSION['session_id'] = $session_id;

$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guest_name = isset($_SESSION['guest_name']) ? $_SESSION['guest_name'] : null;

debugLog("User info", [
    'user_id' => $current_user_id,
    'guest_name' => $guest_name,
    'session_data' => [
        'quiz_init_' . $session_id => isset($_SESSION['quiz_init_' . $session_id]),
        'participant_id' => $_SESSION['participant_id'] ?? 'not_set',
        'current_question' => $_SESSION['current_question'] ?? 'not_set'
    ]
]);

// Get quiz info first
$stmt = $koneksi->prepare("
    SELECT qs.*, q.quiz_type, q.title, q.quiz_id
    FROM quiz_sessions qs 
    JOIN quizzes q ON qs.quiz_id = q.quiz_id 
    WHERE qs.session_id = ?
");

if (!$stmt) {
    debugLog("Database prepare error", $koneksi->error);
    die("Database error: " . $koneksi->error);
}

$stmt->bind_param("i", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$session_data = $result->fetch_assoc();

if (!$session_data) {
    debugLog("Session not found in database", ['session_id' => $session_id]);
    die("Session tidak ditemukan. <a href='../pages/dashboard.php'>Kembali ke Dashboard</a>");
}

$quiz_type = $session_data['quiz_type'];
$quiz_title = $session_data['title'];
$quiz_id = $session_data['quiz_id'];
$quiz_status = $session_data['room_status'];
$stmt->close();

debugLog("Quiz info loaded", [
    'quiz_type' => $quiz_type,
    'quiz_title' => $quiz_title,
    'quiz_id' => $quiz_id,
    'quiz_status' => $quiz_status
]);

$_SESSION['quiz_type'] = $quiz_type;

// Initialize session variables ONLY if not already set for this specific session
$session_key = 'quiz_init_' . $session_id;
if (!isset($_SESSION[$session_key])) {
    debugLog("Initializing session variables for new session");
    $_SESSION['current_question'] = 1;
    $_SESSION['score'] = 0;
    $_SESSION['answers'] = [];
    $_SESSION['quiz_started'] = false;
    $_SESSION['participant_id'] = null;
    $_SESSION['quiz_completed'] = false;
    $_SESSION['final_score_calculated'] = false;
    $_SESSION['submitted_answers'] = [];
    $_SESSION[$session_key] = true;
} else {
    debugLog("Session already initialized", [
        'current_question' => $_SESSION['current_question'],
        'score' => $_SESSION['score'],
        'participant_id' => $_SESSION['participant_id'],
        'quiz_started' => $_SESSION['quiz_started'],
        'quiz_completed' => $_SESSION['quiz_completed']
    ]);
}

// ENHANCED: Join room with better error handling
if (!$_SESSION['participant_id']) {
    debugLog("Participant ID not found - attempting to join room");
    
    if ($current_user_id) {
        debugLog("Attempting to join as registered user", ['user_id' => $current_user_id]);
        
        // Check if user already exists as participant
        $stmt = $koneksi->prepare("SELECT participant_id FROM participants WHERE session_id = ? AND user_id = ? AND connection_status = 'connected'");
        if (!$stmt) {
            debugLog("Database prepare error for user check", $koneksi->error);
            die("Database error: " . $koneksi->error);
        }
        
        $stmt->bind_param("ii", $session_id, $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $_SESSION['participant_id'] = $row['participant_id'];
            debugLog("Found existing user participant", ['participant_id' => $_SESSION['participant_id']]);
        } else {
            debugLog("Creating new user participant");
            $is_host = $roomHandler->isUserHost($session_id, $current_user_id);
            $participant_id = $roomHandler->addParticipant($session_id, $current_user_id, null, $is_host);
            
            if ($participant_id) {
                $_SESSION['participant_id'] = $participant_id;
                debugLog("Successfully created user participant", [
                    'participant_id' => $participant_id,
                    'is_host' => $is_host
                ]);
            } else {
                debugLog("FAILED to create user participant", [
                    'session_id' => $session_id,
                    'user_id' => $current_user_id,
                    'is_host' => $is_host
                ]);
                die("Gagal bergabung ke room. <a href='../pages/join_room.php?session_id=" . $session_id . "'>Coba lagi</a>");
            }
        }
        $stmt->close();
        
    } else if ($guest_name) {
        debugLog("Attempting to join as guest", ['guest_name' => $guest_name]);
        
        // Check if guest already exists
        $stmt = $koneksi->prepare("SELECT participant_id FROM participants WHERE session_id = ? AND guest_name = ? AND connection_status = 'connected'");
        if (!$stmt) {
            debugLog("Database prepare error for guest check", $koneksi->error);
            die("Database error: " . $koneksi->error);
        }
        
        $stmt->bind_param("is", $session_id, $guest_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $_SESSION['participant_id'] = $row['participant_id'];
            debugLog("Found existing guest participant", ['participant_id' => $_SESSION['participant_id']]);
        } else {
            debugLog("Creating new guest participant");
            $participant_id = $roomHandler->addParticipant($session_id, null, $guest_name, false);
            
            if ($participant_id) {
                $_SESSION['participant_id'] = $participant_id;
                debugLog("Successfully created guest participant", ['participant_id' => $participant_id]);
            } else {
                debugLog("FAILED to create guest participant", [
                    'session_id' => $session_id,
                    'guest_name' => $guest_name
                ]);
                die("Gagal bergabung ke room. <a href='../pages/join_room.php?session_id=" . $session_id . "'>Coba lagi</a>");
            }
        }
        $stmt->close();
        
    } else {
        debugLog("No user_id or guest_name - redirecting to join");
        die("Anda belum login atau join sebagai guest. <a href='../pages/join_room.php?session_id=" . $session_id . "'>Join Room</a>");
    }
} else {
    debugLog("Participant ID already exists", ['participant_id' => $_SESSION['participant_id']]);
}

// CRITICAL: Final validation
if (!$_SESSION['participant_id']) {
    debugLog("CRITICAL ERROR: participant_id is still null after all attempts");
    die("Gagal bergabung ke room. Silakan <a href='../pages/join_room.php?session_id=" . $session_id . "'>coba lagi</a>");
}

$is_host = $roomHandler->isUserHost($session_id, $current_user_id);
debugLog("Host status determined", ['is_host' => $is_host]);

// Handle AJAX answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_answer'])) {
    debugLog("=== AJAX ANSWER SUBMISSION START ===");
    header('Content-Type: application/json');
    
    // CRITICAL: Validate participant_id before proceeding
    if (!$_SESSION['participant_id']) {
        debugLog("ERROR: Attempting to submit answer with null participant_id");
        echo json_encode(['status' => 'error', 'message' => 'Participant ID tidak valid']);
        exit;
    }
    
    $question_id = intval($_POST['question_id']);
    $selected_answer = $_POST['answer'];
    $participant_id = $_SESSION['participant_id'];
    
    debugLog("Answer submission data", [
        'participant_id' => $participant_id,
        'question_id' => $question_id,
        'selected_answer' => $selected_answer,
        'quiz_type' => $quiz_type
    ]);
    
    // Prevent duplicate submissions
    $answer_key = $quiz_type . '_' . $question_id;
    if (in_array($answer_key, $_SESSION['submitted_answers'])) {
        debugLog("Duplicate answer submission detected", ['answer_key' => $answer_key]);
        echo json_encode(['status' => 'already_answered']);
        exit;
    }
    
    // Get correct answer based on quiz type
    $correct_answer = '';
    $is_correct = false;
    $existing = null;
    
    if ($quiz_type === 'rof') {
        debugLog("Processing ROF answer");
        
        // Get correct answer for ROF
        $stmt = $koneksi->prepare("SELECT correct_answer FROM rof_questions WHERE rof_question_id = ?");
        if (!$stmt) {
            debugLog("Database prepare error for ROF question", $koneksi->error);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }
        
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $correct_answer = $row['correct_answer'];
        $stmt->close();
        
        debugLog("ROF correct answer retrieved", ['correct_answer' => $correct_answer]);
        
        $is_correct = ($selected_answer === $correct_answer);
        
        // Check if already answered
        $stmt = $koneksi->prepare("SELECT rof_answer_id FROM rof_answers WHERE participant_id = ? AND rof_question_id = ?");
        if (!$stmt) {
            debugLog("Database prepare error for ROF answer check", $koneksi->error);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }
        
        $stmt->bind_param("ii", $participant_id, $question_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$existing) {
            debugLog("Inserting new ROF answer");
            
            // Insert answer
            $stmt = $koneksi->prepare("
                INSERT INTO rof_answers (participant_id, rof_question_id, answer, is_correct, answered_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            if (!$stmt) {
                debugLog("Database prepare error for ROF answer insert", $koneksi->error);
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
                exit;
            }
            
            $stmt->bind_param("iisi", $participant_id, $question_id, $selected_answer, $is_correct);
            if (!$stmt->execute()) {
                debugLog("Database execute error for ROF answer insert", $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save answer']);
                exit;
            }
            $stmt->close();
            debugLog("ROF answer inserted successfully");
        } else {
            debugLog("ROF answer already exists", ['existing_answer_id' => $existing['answer_id']]);
        }
        
    } elseif ($quiz_type === 'decision_maker') {
        debugLog("Processing Decision Maker answer");
        
        // Get correct answer for Decision Maker
        $stmt = $koneksi->prepare("SELECT correct_answer FROM decision_maker_questions WHERE question_id = ?");
        if (!$stmt) {
            debugLog("Database prepare error for Decision Maker question", $koneksi->error);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }
        
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $correct_answer = $row['correct_answer'];
        $stmt->close();
        
        debugLog("Decision Maker correct answer retrieved", ['correct_answer' => $correct_answer]);
        
        $is_correct = ($selected_answer === $correct_answer);
        
        // Check if already answered
        $stmt = $koneksi->prepare("SELECT answer_id FROM user_answers WHERE participant_id = ? AND question_id = ?");
        if (!$stmt) {
            debugLog("Database prepare error for Decision Maker answer check", $koneksi->error);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }
        
        $stmt->bind_param("ii", $participant_id, $question_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$existing) {
            debugLog("Inserting new Decision Maker answer");
            
            $stmt = $koneksi->prepare("
                INSERT INTO user_answers (participant_id, question_id, chosen_answer, is_correct, answered_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            if (!$stmt) {
                debugLog("Database prepare error for Decision Maker answer insert", $koneksi->error);
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
                exit;
            }
            
            $stmt->bind_param("iisi", $participant_id, $question_id, $selected_answer, $is_correct);
            if (!$stmt->execute()) {
                debugLog("Database execute error for Decision Maker answer insert", $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save answer']);
                exit;
            }
            $stmt->close();
            debugLog("Decision Maker answer inserted successfully");
        } else {
            debugLog("Decision Maker answer already exists", ['existing_answer_id' => $existing['answer_id']]);
        }
        
    } else {
        debugLog("Processing Regular Quiz answer");
        
        // Get correct answer for Regular Quiz
        $stmt = $koneksi->prepare("SELECT correct_answer FROM questions WHERE question_id = ?");
        if (!$stmt) {
            debugLog("Database prepare error for Regular question", $koneksi->error);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }
        
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $correct_answer = $row['correct_answer'];
        $stmt->close();
        
        debugLog("Regular Quiz correct answer retrieved", ['correct_answer' => $correct_answer]);
        
        $is_correct = ($selected_answer === $correct_answer);
        
        // Check if already answered
        $stmt = $koneksi->prepare("SELECT answer_id FROM user_answers WHERE participant_id = ? AND question_id = ?");
        if (!$stmt) {
            debugLog("Database prepare error for Regular answer check", $koneksi->error);
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }
        
        $stmt->bind_param("ii", $participant_id, $question_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$existing) {
            debugLog("Inserting new Regular Quiz answer");
            
            $stmt = $koneksi->prepare("
                INSERT INTO user_answers (participant_id, question_id, chosen_answer, is_correct, answered_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            if (!$stmt) {
                debugLog("Database prepare error for Regular answer insert", $koneksi->error);
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
                exit;
            }
            
            $stmt->bind_param("iisi", $participant_id, $question_id, $selected_answer, $is_correct);
            if (!$stmt->execute()) {
                debugLog("Database execute error for Regular answer insert", $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save answer']);
                exit;
            }
            $stmt->close();
            debugLog("Regular Quiz answer inserted successfully");
        } else {
            debugLog("Regular Quiz answer already exists", ['existing_answer_id' => $existing['answer_id']]);
        }
    }
    
    // Update score only if not already counted
    if (!$existing && $is_correct) {
        $_SESSION['score']++;
        debugLog("Score updated", ['new_score' => $_SESSION['score']]);
    }
    
    // Store answer in session
    $_SESSION['answers'][$question_id] = [
        'selected' => $selected_answer,
        'correct' => $correct_answer,
        'is_correct' => $is_correct
    ];
    
    // Mark as submitted
    $_SESSION['submitted_answers'][] = $answer_key;
    
    // Move to next question
    $_SESSION['current_question']++;
    
    debugLog("Session updated", [
        'current_question' => $_SESSION['current_question'],
        'score' => $_SESSION['score'],
        'submitted_answers_count' => count($_SESSION['submitted_answers'])
    ]);
    
    // Get total questions to check if quiz is completed
    $total_questions = 0;
    if ($quiz_type === 'rof') {
        $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM rof_questions WHERE rof_quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
    } elseif ($quiz_type === 'decision_maker') {
        $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM decision_maker_questions WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
    } else {
        $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM questions WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_questions = $row['total'];
    $stmt->close();
    
    $quiz_completed = $_SESSION['current_question'] > $total_questions;
    if ($quiz_completed) {
        $_SESSION['quiz_completed'] = true;
        debugLog("Quiz completed", ['final_score' => $_SESSION['score'], 'total_questions' => $total_questions]);
    }
    
    $response = [
        'status' => 'success',
        'is_correct' => $is_correct,
        'correct_answer' => $correct_answer,
        'selected_answer' => $selected_answer,
        'quiz_completed' => $quiz_completed,
        'current_score' => $_SESSION['score'],
        'total_questions' => $total_questions
    ];
    
    debugLog("Sending AJAX response", $response);
    echo json_encode($response);
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog("=== FORM SUBMISSION ===", $_POST);
    
    if (isset($_POST['start_quiz']) && $is_host) {
        debugLog("Host starting quiz");
        $_SESSION['quiz_started'] = true;
        $_SESSION['start_time'] = time();
        
        $stmt = $koneksi->prepare("UPDATE quiz_sessions SET room_status = 'active' WHERE session_id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $stmt->close();
        
        debugLog("Quiz started successfully", ['start_time' => $_SESSION['start_time']]);
        
    } elseif (isset($_POST['end_quiz']) && $is_host) {
        debugLog("Host ending quiz");
        
        $stmt = $koneksi->prepare("UPDATE quiz_sessions SET room_status = 'ended', end_time = NOW() WHERE session_id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $stmt->close();
        
        $quiz_status = 'ended';
        debugLog("Quiz ended successfully");
    }
}

// Sync quiz status from database
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $koneksi->prepare("SELECT room_status FROM quiz_sessions WHERE session_id = ?");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $quiz_status = $row['room_status'];
    $stmt->close();
    
    debugLog("Quiz status synced from database", ['quiz_status' => $quiz_status]);
}

if ($quiz_status === 'active' && !$_SESSION['quiz_started']) {
    $_SESSION['quiz_started'] = true;
    if (!isset($_SESSION['start_time'])) {
        $_SESSION['start_time'] = time();
    }
    debugLog("Quiz status changed to active", ['start_time' => $_SESSION['start_time']]);
}

// Get total questions
$total_questions = 0;
if ($quiz_type === 'rof') {
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM rof_questions WHERE rof_quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
} elseif ($quiz_type === 'decision_maker') {
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM decision_maker_questions WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
} else {
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM questions WHERE quiz_id = ?");
    $stmt->bind_param("i", $quiz_id);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_questions = $row['total'];
$stmt->close();

debugLog("Total questions retrieved", ['total_questions' => $total_questions]);

// Check if quiz is completed
$quiz_completed = false;
if (!$is_host) {
    $quiz_completed = $_SESSION['current_question'] > $total_questions;
    if ($quiz_completed && !$_SESSION['quiz_completed']) {
        $_SESSION['quiz_completed'] = true;
        
        debugLog("Quiz completion detected", ['current_question' => $_SESSION['current_question'], 'total_questions' => $total_questions]);
        
        if (!$_SESSION['final_score_calculated']) {
            debugLog("Calculating final score");
            $final_score = 0;
            
            if ($quiz_type === 'rof') {
                $stmt = $koneksi->prepare("SELECT COUNT(*) as score FROM rof_answers WHERE participant_id = ? AND is_correct = 1");
                $stmt->bind_param("i", $_SESSION['participant_id']);
            } elseif ($quiz_type === 'decision_maker') {
                $stmt = $koneksi->prepare("SELECT COUNT(*) as score FROM user_answers WHERE participant_id = ? AND is_correct = 1");
                $stmt->bind_param("i", $_SESSION['participant_id']);
            } else {
                $stmt = $koneksi->prepare("SELECT COUNT(*) as score FROM user_answers WHERE participant_id = ? AND is_correct = 1");
                $stmt->bind_param("i", $_SESSION['participant_id']);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $final_score = $row['score'];
            $stmt->close();
            
            $_SESSION['score'] = $final_score;
            $_SESSION['final_score_calculated'] = true;
            
            debugLog("Final score calculated", ['final_score' => $final_score]);
        }
    }
}

// Get current question
$current_question = null;
if ($_SESSION['quiz_started'] && !$quiz_completed && !$is_host) {
    $current_question_number = $_SESSION['current_question'];
    $offset = $current_question_number - 1;
    
    debugLog("Getting current question", ['question_number' => $current_question_number, 'offset' => $offset]);
    
    if ($quiz_type === 'rof') {
        $stmt = $koneksi->prepare("
            SELECT rq.*, rq.question_text as question_text 
            FROM rof_questions rq 
            WHERE rq.rof_quiz_id = ? 
            ORDER BY rq.rof_question_id 
            LIMIT ?, 1
        ");
        $stmt->bind_param("ii", $quiz_id, $offset);
    } elseif ($quiz_type === 'decision_maker') {
        $stmt = $koneksi->prepare("
            SELECT dm.*, dm.question_text as question_text 
            FROM decision_maker_questions dm 
            WHERE dm.quiz_id = ? 
            ORDER BY dm.question_id 
            LIMIT ?, 1
        ");
        $stmt->bind_param("ii", $quiz_id, $offset);
    } else {
        $stmt = $koneksi->prepare("
            SELECT q.* 
            FROM questions q 
            WHERE q.quiz_id = ? 
            ORDER BY q.question_id 
            LIMIT ?, 1
        ");
        $stmt->bind_param("ii", $quiz_id, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $current_question = $result->fetch_assoc();
    $stmt->close();
    
    debugLog("Current question retrieved", ['question_found' => $current_question !== null]);
}

// Get participants
$participants = $roomHandler->getParticipants($session_id);
debugLog("Participants retrieved", ['participant_count' => count($participants)]);

// Get participant scores for host
$participant_scores = [];
if ($is_host && $_SESSION['quiz_started']) {
    debugLog("Getting participant scores for host");
    
    foreach ($participants as $participant) {
        if (!$participant['is_host']) {
            $participant_id = $participant['participant_id'];
            $score = 0;
            $answered = 0;
            
            if ($quiz_type === 'rof') {
                $stmt = $koneksi->prepare("
                    SELECT COUNT(*) as answered, SUM(is_correct) as score 
                    FROM rof_answers 
                    WHERE participant_id = ?
                ");
                $stmt->bind_param("i", $participant_id);
            } elseif ($quiz_type === 'decision_maker') {
                $stmt = $koneksi->prepare("
                    SELECT COUNT(*) as answered, SUM(is_correct) as score 
                    FROM user_answers 
                    WHERE participant_id = ?
                ");
                $stmt->bind_param("i", $participant_id);
            } else {
                $stmt = $koneksi->prepare("
                    SELECT COUNT(*) as answered, SUM(is_correct) as score 
                    FROM user_answers 
                    WHERE participant_id = ?
                ");
                $stmt->bind_param("i", $participant_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $answered = $row['answered'] ?? 0;
            $score = $row['score'] ?? 0;
            $stmt->close();
            
            $participant_scores[] = [
                'name' => $participant['display_name'],
                'score' => $score,
                'answered' => $answered,
                'total' => $total_questions,
                'percentage' => $total_questions > 0 ? round(($score / $total_questions) * 100, 1) : 0
            ];
        }
    }
    
       usort($participant_scores, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    debugLog("Participant scores calculated", ['scores_count' => count($participant_scores)]);
}

// Calculate time elapsed
$time_elapsed = 0;
if (isset($_SESSION['start_time'])) {
    $time_elapsed = time() - $_SESSION['start_time'];
}

debugLog("Time calculation", ['start_time' => $_SESSION['start_time'] ?? 'not_set', 'time_elapsed' => $time_elapsed]);

// Helper functions
function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $remainingSeconds);
}

function getQuestionId($current_question, $quiz_type) {
    if ($quiz_type === 'rof') {
        return $current_question['rof_question_id'];
    } elseif ($quiz_type === 'decision_maker') {
        return $current_question['question_id'];
    } else {
        return $current_question['question_id'];
    }
}

// Handle AJAX requests for status updates
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    debugLog("AJAX status request");
    header('Content-Type: application/json');
    
    $response = [
        'status' => $quiz_status,
        'quiz_started' => $_SESSION['quiz_started'],
        'current_question' => $_SESSION['current_question'],
        'total_questions' => $total_questions,
        'quiz_completed' => $quiz_completed,
        'participant_count' => count($participants),
        'participant_scores' => $participant_scores
    ];
    
    debugLog("AJAX response", $response);
    echo json_encode($response);
    exit;
}

debugLog("=== LIVE QUIZ RENDER START ===", [
    'quiz_status' => $quiz_status,
    'quiz_started' => $_SESSION['quiz_started'],
    'current_question' => $_SESSION['current_question'],
    'quiz_completed' => $quiz_completed,
    'is_host' => $is_host,
    'participant_id' => $_SESSION['participant_id']
]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Quiz <?= ucfirst($quiz_type) ?> - <?= htmlspecialchars($quiz_title) ?></title>
    
    <!-- DEBUG INFO -->
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
    <div id="debug-info" style="position: fixed; top: 0; left: 0; background: black; color: lime; padding: 10px; font-family: monospace; font-size: 12px; z-index: 9999; max-width: 300px; max-height: 200px; overflow-y: auto;">
        <strong>DEBUG INFO:</strong><br>
        Session ID: <?= $session_id ?><br>
        User ID: <?= $current_user_id ?? 'NULL' ?><br>
        Guest Name: <?= $guest_name ?? 'NULL' ?><br>
        Participant ID: <?= $_SESSION['participant_id'] ?? 'NULL' ?><br>
        Quiz Type: <?= $quiz_type ?><br>
        Quiz Status: <?= $quiz_status ?><br>
        Quiz Started: <?= $_SESSION['quiz_started'] ? 'YES' : 'NO' ?><br>
        Current Question: <?= $_SESSION['current_question'] ?><br>
        Total Questions: <?= $total_questions ?><br>
        Score: <?= $_SESSION['score'] ?><br>
        Is Host: <?= $is_host ? 'YES' : 'NO' ?><br>
        Quiz Completed: <?= $quiz_completed ? 'YES' : 'NO' ?><br>
        Participants: <?= count($participants) ?><br>
        <button onclick="document.getElementById('debug-info').style.display='none'">Hide</button>
    </div>
    <?php endif; ?>
    
    <style>
        /* CSS tetap sama seperti sebelumnya */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: <?= $quiz_type === 'rof' ? 'linear-gradient(135deg, #f093fb 0%, #764ba2 100%)' : ($quiz_type === 'decision_maker' ? 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)') ?>;
            min-height: 100vh;
            padding: 1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .room-info {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .room-info h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .quiz-type-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin: 0.5rem;
            display: inline-block;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-left: 0.5rem;
        }
        
        .status-waiting { background: #fbbf24; color: white; }
        .status-active { background: #10b981; color: white; }
        .status-ended { background: #ef4444; color: white; }
        
        .participants-panel {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .participant-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .participant-badge {
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            border: 2px solid transparent;
        }
        
        .participant-badge.host {
            background: #fbbf24;
            color: white;
        }
        
        .participant-badge.current-user {
            border-color: <?= $quiz_type === 'rof' ? '#f093fb' : ($quiz_type === 'decision_maker' ? '#ff9a9e' : '#667eea') ?>;
            background: <?= $quiz_type === 'rof' ? 'rgba(240, 147, 251, 0.2)' : ($quiz_type === 'decision_maker' ? 'rgba(255, 154, 158, 0.2)' : 'rgba(102, 126, 234, 0.2)') ?>;
        }
        
        .host-controls, .waiting-message, .question-card, .quiz-completed, .spectator-panel {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .spectator-panel {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .scoreboard {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .score-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .score-item.rank-1 { border-left: 4px solid #fbbf24; }
        .score-item.rank-2 { border-left: 4px solid #9ca3af; }
        .score-item.rank-3 { border-left: 4px solid #cd7c2f; }
        
        .participant-name {
            font-weight: 600;
        }
        
        .score-details {
            text-align: right;
        }
        
        .score-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: <?= $quiz_type === 'rof' ? '#f093fb' : ($quiz_type === 'decision_maker' ? '#ff9a9e' : '#667eea') ?>;
        }
        
        .progress-info {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .btn {
            background: <?= $quiz_type === 'rof' ? 'linear-gradient(135deg, #f093fb 0%, #764ba2 100%)' : ($quiz_type === 'decision_maker' ? 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)') ?>;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            margin-right: 0.5rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .question-number {
            font-weight: 600;
            color: <?= $quiz_type === 'rof' ? '#f093fb' : ($quiz_type === 'decision_maker' ? '#ff9a9e' : '#667eea') ?>;
        }
        
        .time-display {
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .question-text {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .answer-options {
            margin-bottom: 2rem;
        }
        
        .answer-option {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .answer-option:hover {
            border-color: <?= $quiz_type === 'rof' ? '#f093fb' : ($quiz_type === 'decision_maker' ? '#ff9a9e' : '#667eea') ?>;
            background: <?= $quiz_type === 'rof' ? 'rgba(240, 147, 251, 0.1)' : ($quiz_type === 'decision_maker' ? 'rgba(255, 154, 158, 0.1)' : 'rgba(102, 126, 234, 0.1)') ?>;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .answer-option.selected {
            border-color: <?= $quiz_type === 'rof' ? '#f093fb' : ($quiz_type === 'decision_maker' ? '#ff9a9e' : '#667eea') ?> !important;
            background: <?= $quiz_type === 'rof' ? 'rgba(240, 147, 251, 0.2)' : ($quiz_type === 'decision_maker' ? 'rgba(255, 154, 158, 0.2)' : 'rgba(102, 126, 234, 0.2)') ?> !important;
        }
        
        .answer-option.correct {
            border-color: #10b981 !important;
            background: rgba(16, 185, 129, 0.1) !important;
            animation: correctPulse 0.6s ease;
        }
        
        .answer-option.incorrect {
            border-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.1) !important;
            animation: incorrectShake 0.6s ease;
        }
        
        .answer-option.disabled {
            pointer-events: none;
            opacity: 0.7;
        }
        
        .answer-option .feedback-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .answer-option.correct .feedback-icon.correct,
        .answer-option.incorrect .feedback-icon.incorrect {
            opacity: 1;
        }
        
        @keyframes correctPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        @keyframes incorrectShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .answer-option input[type="radio"] {
            display: none;
        }
        
        .answer-option label {
            cursor: pointer;
            display: block;
            width: 100%;
            font-weight: 500;
            padding-right: 3rem;
        }
        
        .feedback-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .feedback-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .feedback-content {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }
        
        .feedback-overlay.show .feedback-content {
            transform: scale(1);
        }
        
        .feedback-icon-large {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .feedback-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .feedback-title.correct { color: #10b981; }
        .feedback-title.incorrect { color: #ef4444; }
        
        .feedback-message {
            color: #6b7280;
            margin-bottom: 1rem;
        }
        
        .next-question-timer {
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            display: inline-block;
        }
        
        .auto-refresh {
            text-align: center;
            margin-top: 1rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .refresh-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            z-index: 1000;
            display: none;
        }
        
        .final-score {
            font-size: 3rem;
            font-weight: bold;
            color: <?= $quiz_type === 'rof' ? '#f093fb' : ($quiz_type === 'decision_maker' ? '#ff9a9e' : '#667eea') ?>;
            text-align: center;
            margin: 2rem 0;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid <?= $quiz_type === 'rof' ? '#f093fb' : ($quiz_type === 'decision_maker' ? '#ff9a9e' : '#667eea') ?>;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* DEBUG STYLES */
        .debug-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            color: lime;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            max-width: 300px;
            z-index: 9998;
        }
        
        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0.5rem;
            }
            
            .room-info h1 {
                font-size: 1.25rem;
            }
            
            .participants-panel {
                padding: 1rem;
            }
            
            .host-controls, .waiting-message, .question-card, .quiz-completed, .spectator-panel {
                padding: 1.5rem;
            }
            
            .question-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .final-score {
                font-size: 2rem;
            }
            
            .scoreboard {
                gap: 0.5rem;
            }
            
            .score-item {
                padding: 0.75rem;
            }
            
            .feedback-content {
                padding: 1.5rem;
            }
            
            .feedback-icon-large {
                font-size: 3rem;
            }
            
            #debug-info {
                font-size: 10px;
                max-width: 250px;
            }
        }
    </style>
    
    <script>
        // ENHANCED JAVASCRIPT WITH DEBUG
        let refreshInterval;
        let isRefreshing = false;
        let lastQuestionNumber = <?= $_SESSION['current_question'] ?>;
        let lastStatus = '<?= $quiz_status ?>';
        let isAnswering = false;
        let nextQuestionTimer = null;
        
        // Debug logging
        const DEBUG = <?= isset($_GET['debug']) && $_GET['debug'] == '1' ? 'true' : 'false' ?>;
        
        function debugLog(message, data = null) {
            if (DEBUG) {
                const timestamp = new Date().toISOString();
                const logMessage = `[${timestamp}] JS DEBUG: ${message}`;
                console.log(logMessage, data || '');
                
                // Also show in debug panel if exists
                const debugPanel = document.getElementById('js-debug-panel');
                if (debugPanel) {
                    const logEntry = document.createElement('div');
                    logEntry.textContent = logMessage;
                    if (data) logEntry.textContent += ' | ' + JSON.stringify(data);
                    debugPanel.appendChild(logEntry);
                    debugPanel.scrollTop = debugPanel.scrollHeight;
                }
            }
        }
        
        function showRefreshIndicator() {
            debugLog("Showing refresh indicator");
            const indicator = document.getElementById('refresh-indicator');
            if (indicator) {
                indicator.style.display = 'block';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 2000);
            }
        }
        
        function showLoadingOverlay() {
            debugLog("Showing loading overlay");
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.add('show');
            }
        }
        
        function hideLoadingOverlay() {
            debugLog("Hiding loading overlay");
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.remove('show');
            }
        }
        
        function showFeedback(isCorrect, correctAnswer, selectedAnswer) {
            debugLog("Showing feedback", {isCorrect, correctAnswer, selectedAnswer});
            
            const overlay = document.getElementById('feedback-overlay');
            const icon = document.getElementById('feedback-icon');
            const title = document.getElementById('feedback-title');
            const message = document.getElementById('feedback-message');
            const timer = document.getElementById('next-timer');
            
            if (overlay && icon && title && message && timer) {
                // Set feedback content
                if (isCorrect) {
                    icon.textContent = '🎉';
                    title.textContent = 'Benar!';
                    title.className = 'feedback-title correct';
                    message.textContent = 'Jawaban Anda tepat!';
                } else {
                    icon.textContent = '😔';
                    title.textContent = 'Salah!';
                    title.className = 'feedback-title incorrect';
                    message.textContent = `Jawaban yang benar: ${correctAnswer}`;
                }
                
                // Show overlay
                overlay.classList.add('show');
                
                // Start countdown timer
                let countdown = 3;
                timer.textContent = `Lanjut dalam ${countdown} detik...`;
                
                nextQuestionTimer = setInterval(() => {
                    countdown--;
                    if (countdown > 0) {
                        timer.textContent = `Lanjut dalam ${countdown} detik...`;
                    } else {
                        clearInterval(nextQuestionTimer);
                        overlay.classList.remove('show');
                        
                        // Reload page to show next question or results
                        setTimeout(() => {
                            debugLog("Reloading page after feedback");
                            window.location.reload();
                        }, 300);
                    }
                }, 1000);
            }
        }
        
        function submitAnswer(questionId, selectedAnswer) {
            if (isAnswering) {
                debugLog("Already answering, ignoring click");
                return;
            }
            
            debugLog("Submitting answer", {questionId, selectedAnswer});
            isAnswering = true;
            showLoadingOverlay();
            
            // Disable all answer options
            const answerOptions = document.querySelectorAll('.answer-option');
            answerOptions.forEach(option => {
                option.classList.add('disabled');
            });
            
            // Highlight selected answer
            const selectedOption = document.querySelector(`input[value="${selectedAnswer}"]`).closest('.answer-option');
            selectedOption.classList.add('selected');
            
            // Submit via AJAX
            const formData = new FormData();
            formData.append('ajax_answer', '1');
            formData.append('question_id', questionId);
            formData.append('answer', selectedAnswer);
            
            debugLog("Sending AJAX request", {
                url: window.location.href,
                questionId,
                selectedAnswer
            });
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                debugLog("AJAX response received", {status: response.status});
                return response.json();
            })
            .then(data => {
                debugLog("AJAX response data", data);
                hideLoadingOverlay();
                
                if (data.status === 'success') {
                    // Show correct/incorrect feedback on options
                    answerOptions.forEach(option => {
                        const input = option.querySelector('input[type="radio"]');
                        const feedbackIcon = option.querySelector('.feedback-icon');
                        
                        if (input.value === data.correct_answer) {
                            option.classList.add('correct');
                            if (feedbackIcon) {
                                feedbackIcon.classList.add('correct');
                                feedbackIcon.textContent = '✅';
                            }
                        } else if (input.value === selectedAnswer && !data.is_correct) {
                            option.classList.add('incorrect');
                            if (feedbackIcon) {
                                feedbackIcon.classList.add('incorrect');
                                feedbackIcon.textContent = '❌';
                            }
                        }
                    });
                    
                    // Show feedback overlay after a short delay
                    setTimeout(() => {
                        showFeedback(data.is_correct, data.correct_answer, selectedAnswer);
                    }, 800);
                    
                } else if (data.status === 'already_answered') {
                    debugLog("Already answered error");
                    alert('Anda sudah menjawab pertanyaan ini!');
                    window.location.reload();
                } else if (data.status === 'error') {
                    debugLog("Server error", data.message);
                    alert('Error: ' + (data.message || 'Terjadi kesalahan server'));
                    isAnswering = false;
                    answerOptions.forEach(option => {
                        option.classList.remove('disabled');
                    });
                } else {
                    debugLog("Unknown response status", data);
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                    isAnswering = false;
                    answerOptions.forEach(option => {
                        option.classList.remove('disabled');
                    });
                }
            })
            .catch(error => {
                debugLog("AJAX error", error);
                console.error('Error:', error);
                hideLoadingOverlay();
                alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
                isAnswering = false;
                answerOptions.forEach(option => {
                    option.classList.remove('disabled');
                });
            });
        }
        
        function startAutoRefresh() {
            <?php if (($quiz_status === 'waiting' && !$is_host) || ($is_host && $_SESSION['quiz_started'] && $quiz_status !== 'ended')): ?>
            debugLog("Starting auto refresh");
            refreshInterval = setInterval(function() {
                if (!isRefreshing && !isAnswering) {
                    isRefreshing = true;
                    showRefreshIndicator();
                    
                    const refreshUrl = window.location.href.split('?')[0] + '?session_id=<?= $session_id ?>&ajax=1';
                    debugLog("Fetching refresh data", {url: refreshUrl});
                    
                    fetch(refreshUrl)
                        .then(response => response.json())
                        .then(data => {
                            debugLog("Refresh data received", data);
                            let shouldReload = false;
                            
                            <?php if (!$is_host): ?>
                            if (data.status !== lastStatus) {
                                debugLog("Status changed, reloading", {old: lastStatus, new: data.status});
                                shouldReload = true;
                            }
                            <?php endif; ?>
                            
                            <?php if ($is_host && $_SESSION['quiz_started']): ?>
                            if (data.participant_scores) {
                                updateScoreboard(data.participant_scores);
                            }
                            <?php endif; ?>
                            
                                                      if (shouldReload) {
                                window.location.reload();
                            }
                            
                            lastStatus = data.status;
                            isRefreshing = false;
                        })
                        .catch(error => {
                            debugLog('Refresh error', error);
                            console.log('Refresh error:', error);
                            isRefreshing = false;
                        });
                }
            }, 5000);
            <?php else: ?>
            debugLog("Auto refresh not needed for current state");
            <?php endif; ?>
        }
        
        function updateScoreboard(scores) {
            debugLog("Updating scoreboard", {scoresCount: scores.length});
            const scoreboard = document.querySelector('.scoreboard');
            if (!scoreboard || !scores || scores.length === 0) return;
            
            let html = '';
            scores.forEach((participant, index) => {
                const rankClass = index === 0 ? 'rank-1' : (index === 1 ? 'rank-2' : (index === 2 ? 'rank-3' : ''));
                html += `
                    <div class="score-item ${rankClass}">
                        <div>
                            <div class="participant-name">
                                ${index + 1}. ${participant.name}
                            </div>
                            <div class="progress-info">
                                ${participant.answered}/${participant.total} soal dijawab
                            </div>
                        </div>
                        <div class="score-details">
                            <div class="score-number">${participant.score}/${participant.total}</div>
                            <div class="progress-info">${participant.percentage}%</div>
                        </div>
                    </div>
                `;
            });
            scoreboard.innerHTML = html;
            debugLog("Scoreboard updated successfully");
        }
        
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
        
        function updateTimer() {
            const timerElement = document.getElementById('timer');
            if (timerElement) {
                const startTime = <?= isset($_SESSION['start_time']) ? $_SESSION['start_time'] : 'null' ?>;
                if (startTime) {
                    const currentTime = Math.floor(Date.now() / 1000);
                    const elapsed = currentTime - startTime;
                    timerElement.textContent = formatTime(elapsed);
                }
            }
        }
        
        function confirmEndQuiz() {
            debugLog("Confirming end quiz");
            return confirm('Apakah Anda yakin ingin mengakhiri quiz? Semua participant akan melihat hasil akhir.');
        }
        
        // Error handling for critical issues
        function handleCriticalError(message, details = null) {
            debugLog("CRITICAL ERROR", {message, details});
            console.error("CRITICAL ERROR:", message, details);
            
            // Show error to user
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `
                <strong>Error:</strong> ${message}<br>
                <small>Silakan refresh halaman atau hubungi administrator.</small>
                ${details ? `<br><small>Details: ${JSON.stringify(details)}</small>` : ''}
            `;
            
            // Insert at top of container
            const container = document.querySelector('.container');
            if (container && container.firstChild) {
                container.insertBefore(errorDiv, container.firstChild);
            }
        }
        
        // Validate critical data on page load
        function validatePageData() {
            debugLog("Validating page data");
            
            const participantId = <?= json_encode($_SESSION['participant_id']) ?>;
            const sessionId = <?= json_encode($session_id) ?>;
            const quizType = <?= json_encode($quiz_type) ?>;
            
            if (!participantId) {
                handleCriticalError("Participant ID tidak ditemukan", {
                    participantId,
                    sessionId,
                    quizType
                });
                return false;
            }
            
            if (!sessionId) {
                handleCriticalError("Session ID tidak valid", {
                    participantId,
                    sessionId,
                    quizType
                });
                return false;
            }
            
            debugLog("Page data validation passed", {
                participantId,
                sessionId,
                quizType
            });
            
            return true;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            debugLog("DOM Content Loaded");
            
            // Validate critical data first
            if (!validatePageData()) {
                debugLog("Page data validation failed, stopping initialization");
                return;
            }
            
            startAutoRefresh();
            
            <?php if ($_SESSION['quiz_started'] && !$quiz_completed): ?>
            setInterval(updateTimer, 1000);
            updateTimer();
            debugLog("Timer started");
            <?php endif; ?>
            
            // Add click handlers to answer options
            const answerOptions = document.querySelectorAll('.answer-option');
            debugLog("Adding click handlers to answer options", {count: answerOptions.length});
            
            answerOptions.forEach((option, index) => {
                option.addEventListener('click', function() {
                    debugLog("Answer option clicked", {index, isAnswering, isDisabled: this.classList.contains('disabled')});
                    
                    if (isAnswering || this.classList.contains('disabled')) {
                        debugLog("Click ignored - already answering or disabled");
                        return;
                    }
                    
                    const input = this.querySelector('input[type="radio"]');
                    const questionId = document.querySelector('input[name="question_id"]')?.value;
                    
                    if (input && questionId) {
                        input.checked = true;
                        submitAnswer(questionId, input.value);
                    } else {
                        debugLog("Missing input or question ID", {
                            hasInput: !!input,
                            questionId: questionId
                        });
                        handleCriticalError("Data pertanyaan tidak lengkap");
                    }
                });
            });
            
            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
                debugLog("History state replaced to prevent form resubmission");
            }
            
            // Add debug panel if in debug mode
            if (DEBUG) {
                createDebugPanel();
            }
            
            debugLog("Page initialization completed");
        });
        
        function createDebugPanel() {
            const debugPanel = document.createElement('div');
            debugPanel.id = 'js-debug-panel';
            debugPanel.className = 'debug-panel';
            debugPanel.innerHTML = `
                <strong>JS Debug Log:</strong>
                <button onclick="document.getElementById('js-debug-panel').style.display='none'" style="float: right; background: red; color: white; border: none; padding: 2px 6px; border-radius: 3px;">×</button>
                <div style="max-height: 200px; overflow-y: auto; margin-top: 10px; font-size: 10px;"></div>
            `;
            document.body.appendChild(debugPanel);
        }
        
        <?php if ($_SESSION['quiz_started'] && !$quiz_completed && !$is_host): ?>
        window.addEventListener('beforeunload', function(e) {
            if (!isAnswering) return;
            debugLog("Preventing page unload during answer submission");
            e.preventDefault();
            e.returnValue = 'Quiz sedang berlangsung. Yakin ingin keluar?';
            return 'Quiz sedang berlangsung. Yakin ingin keluar?';
        });
        <?php endif; ?>
        
        // Global error handler
        window.addEventListener('error', function(e) {
            debugLog("Global error caught", {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno
            });
        });
        
        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(e) {
            debugLog("Unhandled promise rejection", {
                reason: e.reason,
                promise: e.promise
            });
        });
    </script>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Feedback Overlay -->
    <div class="feedback-overlay" id="feedback-overlay">
        <div class="feedback-content">
            <div class="feedback-icon-large" id="feedback-icon">🎉</div>
            <div class="feedback-title" id="feedback-title">Benar!</div>
            <div class="feedback-message" id="feedback-message">Jawaban Anda tepat!</div>
            <div class="next-question-timer" id="next-timer">Lanjut dalam 3 detik...</div>
        </div>
    </div>
    
    <div class="refresh-indicator" id="refresh-indicator">
        🔄 Memperbarui data...
    </div>
    
    <!-- CRITICAL ERROR CHECK -->
    <?php if (!$_SESSION['participant_id']): ?>
    <div class="error-message">
        <strong>CRITICAL ERROR:</strong> Participant ID tidak ditemukan!<br>
        <small>Session ID: <?= $session_id ?></small><br>
        <small>User ID: <?= $current_user_id ?? 'NULL' ?></small><br>
        <small>Guest Name: <?= $guest_name ?? 'NULL' ?></small><br>
        <a href="../pages/join_room.php?session_id=<?= $session_id ?>" class="btn">🔄 Coba Join Ulang</a>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <!-- Room Info Header -->
        <div class="room-info">
            <h1><?= htmlspecialchars($quiz_title) ?></h1>
            <span class="quiz-type-badge"><?= ucfirst(str_replace('_', ' ', $quiz_type)) ?> Quiz</span>
            <span class="status-badge status-<?= $quiz_status ?>">
                <?= $quiz_status === 'waiting' ? '⏳ Menunggu' : ($quiz_status === 'active' ? '🟢 Aktif' : '🔴 Selesai') ?>
            </span>
            <p>Room ID: <?= $session_id ?></p>
            <?php if (isset($_SESSION['start_time'])): ?>
                <p>Waktu Berjalan: <span id="timer"><?= formatTime($time_elapsed) ?></span></p>
            <?php endif; ?>
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                <p style="font-size: 0.8em; margin-top: 0.5rem;">
                    <strong>DEBUG:</strong> Participant ID: <?= $_SESSION['participant_id'] ?? 'NULL' ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Participants Panel -->
        <div class="participants-panel">
            <h3>Participants (<?= count($participants) ?>)</h3>
            <div class="participant-list">
                <?php foreach ($participants as $participant): ?>
                    <div class="participant-badge <?= $participant['is_host'] ? 'host' : '' ?> <?= ($participant['user_id'] == $current_user_id || ($participant['is_guest'] && $participant['display_name'] == $guest_name)) ? 'current-user' : '' ?>">
                        <?= htmlspecialchars($participant['display_name']) ?>
                        <?php if ($participant['is_host']): ?>
                            <span style="margin-left: 0.25rem;">👑</span>
                        <?php endif; ?>
                        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                            <small style="display: block; font-size: 0.7em;">ID: <?= $participant['participant_id'] ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($is_host && $quiz_status === 'waiting'): ?>
            <!-- Host Controls - Start Quiz -->
            <div class="host-controls">
                <h3>🎮 Host Controls</h3>
                <p>Quiz belum dimulai. Klik tombol di bawah untuk memulai quiz.</p>
                <form method="POST" style="margin-top: 1rem;">
                    <button type="submit" name="start_quiz" class="btn">
                        🚀 Mulai Quiz
                    </button>
                </form>
                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                    <div class="debug-panel" style="position: static; margin-top: 1rem;">
                        <strong>Host Debug Info:</strong><br>
                        Total Questions: <?= $total_questions ?><br>
                        Quiz ID: <?= $quiz_id ?><br>
                        Quiz Type: <?= $quiz_type ?><br>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($is_host && $_SESSION['quiz_started'] && $quiz_status !== 'ended'): ?>
            <!-- Host Spectator Panel -->
            <div class="spectator-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>📊 Live Scoreboard</h3>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="end_quiz" class="btn btn-danger" onclick="return confirmEndQuiz()">
                            🏁 Akhiri Quiz
                        </button>
                    </form>
                </div>
                
                <?php if (!empty($participant_scores)): ?>
                    <div class="scoreboard">
                        <?php foreach ($participant_scores as $index => $participant): ?>
                            <div class="score-item <?= $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : '')) ?>">
                                <div>
                                    <div class="participant-name">
                                        <?= $index + 1 ?>. <?= htmlspecialchars($participant['name']) ?>
                                    </div>
                                    <div class="progress-info">
                                        <?= $participant['answered'] ?>/<?= $participant['total'] ?> soal dijawab
                                    </div>
                                </div>
                                <div class="score-details">
                                    <div class="score-number"><?= $participant['score'] ?>/<?= $participant['total'] ?></div>
                                    <div class="progress-info"><?= $participant['percentage'] ?>%</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #6b7280; padding: 2rem;">
                        📝 Belum ada participant yang menjawab soal
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$_SESSION['quiz_started'] && !$is_host): ?>
            <!-- Waiting Message -->
            <div class="waiting-message">
                <h3>⏳ Menunggu Host Memulai Quiz</h3>
                <p>Quiz akan segera dimulai. Harap tunggu instruksi dari host.</p>
                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                    <div class="debug-panel" style="position: static; margin-top: 1rem;">
                        <strong>Waiting Debug Info:</strong><br>
                        Quiz Started: <?= $_SESSION['quiz_started'] ? 'YES' : 'NO' ?><br>
                        Quiz Status: <?= $quiz_status ?><br>
                        Is Host: <?= $is_host ? 'YES' : 'NO' ?><br>
                        Participant ID: <?= $_SESSION['participant_id'] ?? 'NULL' ?><br>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($_SESSION['quiz_started'] && !$quiz_completed && $current_question && !$is_host): ?>
            <!-- Current Question -->
            <div class="question-card">
                <div class="question-header">
                    <div class="question-number">
                        Soal <?= $_SESSION['current_question'] ?> dari <?= $total_questions ?>
                    </div>
                    <div class="time-display" id="timer">
                        <?= formatTime($time_elapsed) ?>
                    </div>
                </div>

                <div class="question-text">
                    <?= htmlspecialchars($current_question['question_text']) ?>
                </div>

                <input type="hidden" name="question_id" value="<?= getQuestionId($current_question, $quiz_type) ?>">
                
                <div class="answer-options">
                    <?php if ($quiz_type === 'rof'): ?>
                        <!-- ROF Quiz - True/False Options -->
                        <div class="answer-option">
                            <input type="radio" id="answer_true" name="answer" value="true">
                            <label for="answer_true">✅ True (Benar)</label>
                            <span class="feedback-icon"></span>
                        </div>
                        <div class="answer-option">
                            <input type="radio" id="answer_false" name="answer" value="false">
                            <label for="answer_false">❌ False (Salah)</label>
                            <span class="feedback-icon"></span>
                        </div>
                    <?php elseif ($quiz_type === 'decision_maker'): ?>
                        <!-- Decision Maker Quiz - Decision-based answers -->
                        <div class="answer-option">
                            <input type="radio" id="answer_a" name="answer" value="A">
                            <label for="answer_a">A. Analisis mendalam terlebih dahulu</label>
                            <span class="feedback-icon"></span>
                        </div>
                        <div class="answer-option">
                            <input type="radio" id="answer_b" name="answer" value="B">
                            <label for="answer_b">B. Keputusan cepat berdasarkan intuisi</label>
                            <span class="feedback-icon"></span>
                        </div>
                        <div class="answer-option">
                            <input type="radio" id="answer_c" name="answer" value="C">
                            <label for="answer_c">C. Konsultasi dengan tim</label>
                            <span class="feedback-icon"></span>
                        </div>
                        <div class="answer-option">
                            <input type="radio" id="answer_d" name="answer" value="D">
                            <label for="answer_d">D. Menunda keputusan</label>
                            <span class="feedback-icon"></span>
                        </div>
                    <?php else: ?>
                        <!-- Regular Quiz - Multiple Choice -->
                        <div class="answer-option">
                            <input type="radio" id="answer_a" name="answer" value="A">
                            <label for="answer_a">A. <?= htmlspecialchars($current_question['option_a']) ?></label>
                            <span class="feedback-icon"></span>
                        </div>
                        <div class="answer-option">
                            <input type="radio" id="answer_b" name="answer" value="B">
                            <label for="answer_b">B. <?= htmlspecialchars($current_question['option_b']) ?></label>
                            <span class="feedback-icon"></span>
                        </div>
                        <div class="answer-option">
                            <input type="radio" id="answer_c" name="answer" value="C">
                            <label for="answer_c">C. <?= htmlspecialchars($current_question['option_c']) ?></label>
                            <span class="feedback-icon"></span>
                        </div>
                        <div class="answer-option">
                            <input type="radio" id="answer_d" name="answer" value="D">
                            <label for="answer_d">D. <?= htmlspecialchars($current_question['option_d']) ?></label>
                            <span class="feedback-icon"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="text-align: center; color: #6b7280; font-style: italic;">
                    💡 Klik jawaban untuk memilih dan melihat hasilnya
                </div>
                
                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                    <div class="debug-panel" style="position: static; margin-top: 1rem;">
                        <strong>Question Debug Info:</strong><br>
                        Question ID: <?= getQuestionId($current_question, $quiz_type) ?><br>
                        Quiz Type: <?= $quiz_type ?><br>
                        Current Question Number: <?= $_SESSION['current_question'] ?><br>
                        Submitted Answers: <?= count($_SESSION['submitted_answers']) ?><br>
                        Current Score: <?= $_SESSION['score'] ?><br>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($quiz_completed && !$is_host): ?>
            <!-- Quiz Completed -->
            <div class="quiz-completed">
                <h2>🎉 Quiz Selesai!</h2>
                <div style="text-align: center; margin: 2rem 0;">
                    <div class="final-score">
                        <?= $_SESSION['score'] ?>/<?= $total_questions ?>
                    </div>
                    <div style="font-size: 1.5rem; margin-top: 0.5rem;">
                        <?= $total_questions > 0 ? round(($_SESSION['score'] / $total_questions) * 100, 1) : 0 ?>%
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <?php
                    $percentage = $total_questions > 0 ? ($_SESSION['score'] / $total_questions) * 100 : 0;
                    if ($percentage >= 80) {
                        echo "<p style='color: #10b981; font-weight: 600;'>🌟 Excellent! Skor yang sangat baik!</p>";
                    } elseif ($percentage >= 60) {
                        echo "<p style='color: #f59e0b; font-weight: 600;'>👍 Good! Skor yang cukup baik!</p>";
                    } else {
                        echo "<p style='color: #ef4444; font-weight: 600;'>💪 Keep Learning! Terus berlatih!</p>";
                    }
                    ?>
                </div>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <a href="dashboard.php" class="btn">
                        🏠 Kembali ke Dashboard
                    </a>
                </div>
                
                <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                    <div class="debug-panel" style="position: static; margin-top: 1rem;">
                        <strong>Completion Debug Info:</strong><br>
                        Final Score: <?= $_SESSION['score'] ?><br>
                        Total Questions: <?= $total_questions ?><br>
                        Percentage: <?= $percentage ?>%<br>
                        Quiz Completed: <?= $_SESSION['quiz_completed'] ? 'YES' : 'NO' ?><br>
                        Final Score Calculated: <?= $_SESSION['final_score_calculated'] ? 'YES' : 'NO' ?><br>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($quiz_status === 'ended'): ?>
            <!-- Quiz Ended by Host -->
            <div class="quiz-completed">
                <h2>🏁 Quiz Telah Diakhiri</h2>
                <p style="text-align: center; margin: 1rem 0;">Host telah mengakhiri quiz ini.</p>
                
                <?php if (!$is_host && !empty($participant_scores)): ?>
                    <div style="margin: 2rem 0;">
                        <h3 style="text-align: center; margin-bottom: 1rem;">🏆 Hasil Akhir</h3>
                        <div class="scoreboard">
                            <?php foreach ($participant_scores as $index => $participant): ?>
                                <div class="score-item <?= $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : '')) ?>">
                                    <div>
                                        <div class="participant-name">
                                            <?php if ($index === 0): ?>🥇<?php elseif ($index === 1): ?>🥈<?php elseif ($index === 2): ?>🥉<?php else: ?><?= $index + 1 ?>.<?php endif; ?>
                                            <?= htmlspecialchars($participant['name']) ?>
                                        </div>
                                        <div class="progress-info">
                                            <?= $participant['answered'] ?>/<?= $participant['total'] ?> soal dijawab
                                        </div>
                                    </div>
                                    <div class="score-details">
                                        <div class="score-number"><?= $participant['score'] ?>/<?= $participant['total'] ?></div>
                                        <div class="progress-info"><?= $participant['percentage'] ?>%</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <a href="../pages/dashboard.php" class="btn">
                        🏠 Kembali ke Dashboard
                    </a>
                    <?php if ($is_host): ?>
                        <a href="../pages/create_room.php" class="btn">
                            🎮 Buat Room Baru
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Auto-refresh info -->
        <?php if (($quiz_status === 'waiting' && !$is_host) || ($is_host && $_SESSION['quiz_started'] && $quiz_status !== 'ended')): ?>
            <div class="auto-refresh">
                🔄 Halaman akan diperbarui secara otomatis setiap 5 detik
            </div>
        <?php endif; ?>
        
        <!-- Debug URL Info -->
        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            <div style="text-align: center; margin-top: 1rem; color: rgba(255, 255, 255, 0.8); font-size: 0.8em;">
                💡 Debug mode aktif - <a href="?session_id=<?= $session_id ?>" style="color: white;">Nonaktifkan Debug</a>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-top: 1rem; color: rgba(255, 255, 255, 0.8); font-size: 0.8em;">
                🔧 <a href="?session_id=<?= $session_id ?>&debug=1" style="color: white;">Aktifkan Debug Mode</a>
            </div>
        <?php endif; ?>
    </div>

    <?php
    debugLog("=== LIVE QUIZ RENDER COMPLETE ===");
    ?>
</body>
</html>
