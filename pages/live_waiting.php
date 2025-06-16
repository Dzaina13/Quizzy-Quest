<?php
// pages/live_waiting.php - FIXED VERSION WITH CONSISTENT PATHS

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../assets/php/koneksi_db.php';

if (!isset($_GET['session_id'])) {
    header('Location: ../index.php');
    exit();
}

$session_id = (int)$_GET['session_id'];

// Get session info dari quiz_sessions table
$session_query = "SELECT * FROM quiz_sessions WHERE session_id = ?";
$session_stmt = mysqli_prepare($koneksi, $session_query);

if (!$session_stmt) {
    die("Database error: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($session_stmt, "i", $session_id);
mysqli_stmt_execute($session_stmt);
$session_result = mysqli_stmt_get_result($session_stmt);
$session = mysqli_fetch_assoc($session_result);
mysqli_stmt_close($session_stmt);

if (!$session) {
    die("Session not found. Session ID: " . $session_id);
}

// Check if user is logged in
$current_user_id = $_SESSION['user_id'] ?? null;
$is_host = false;

if ($current_user_id) {
    // Check if user is host
    $is_host = ($session['created_by'] == $current_user_id);

    // Add user as participant if not already added
    $check_participant = "SELECT participant_id FROM participants WHERE session_id = ? AND user_id = ?";
    $check_stmt = mysqli_prepare($koneksi, $check_participant);

    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "ii", $session_id, $current_user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) == 0) {
            // Add participant - ONLY ADD ONCE dengan is_host flag
            $add_participant = "INSERT INTO participants (session_id, user_id, is_guest, is_host, connection_status, join_time) VALUES (?, ?, 0, ?, 'connected', NOW())";
            $add_stmt = mysqli_prepare($koneksi, $add_participant);
            
            if ($add_stmt) {
                $is_host_int = $is_host ? 1 : 0;
                mysqli_stmt_bind_param($add_stmt, "iii", $session_id, $current_user_id, $is_host_int);
                mysqli_stmt_execute($add_stmt);
                mysqli_stmt_close($add_stmt);
            }
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Get participants - FIXED: No duplicate counting
$participants = [];
$participants_query = "
    SELECT 
        p.participant_id,
        p.user_id,
        p.is_guest,
        p.guest_name,
        p.is_host,
        u.username
    FROM participants p
    LEFT JOIN users u ON p.user_id = u.user_id
    WHERE p.session_id = ? AND p.connection_status = 'connected'
    ORDER BY p.is_host DESC, p.join_time ASC
";

$participants_stmt = mysqli_prepare($koneksi, $participants_query);
if ($participants_stmt) {
    mysqli_stmt_bind_param($participants_stmt, "i", $session_id);
    mysqli_stmt_execute($participants_stmt);
    $participants_result = mysqli_stmt_get_result($participants_stmt);

    while ($row = mysqli_fetch_assoc($participants_result)) {
        $display_name = $row['is_guest'] ? ($row['guest_name'] ?: 'Guest') : ($row['username'] ?: 'User');
        $participants[] = [
            'participant_id' => $row['participant_id'],
            'user_id' => $row['user_id'],
            'display_name' => $display_name,
            'is_host' => (bool)$row['is_host']
        ];
    }
    mysqli_stmt_close($participants_stmt);
}

$participant_count = count($participants);
$non_host_count = count(array_filter($participants, function($p) { return !$p['is_host']; }));

// Get room code dari quiz_sessions
$room_code = $session['room_code'] ?? 'N/A';
$quiz_title = $session['title'] ?? 'Quiz';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting Room - <?php echo htmlspecialchars($quiz_title); ?></title>
    <link rel="stylesheet" href="../assets/css/live_waiting.css">
</head>
<body>
<div class="container">
    <!-- Debug Info (hapus nanti setelah testing) -->
    <div class="debug-info">
        <strong>🔧 Debug Info:</strong><br>
        Session ID: <?php echo $session_id; ?> | 
        User ID: <?php echo $current_user_id ?? 'Not logged in'; ?> | 
        Is Host: <?php echo $is_host ? 'Yes' : 'No'; ?> | 
        Room Code: <?php echo $room_code; ?> |
        Total Participants: <?php echo $participant_count; ?> |
        Non-Host Participants: <?php echo $non_host_count; ?>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="logo">🎯 Quizzy Quest</div>
        <div class="quiz-title"><?php echo htmlspecialchars($quiz_title); ?></div>
        <h2>Room Code</h2>
        <div class="room-code"><?php echo htmlspecialchars($room_code); ?></div>
        <button class="copy-btn" onclick="copyRoomCode()">📋 Copy Code</button>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-item">
            <div class="stat-icon">📝</div>
            <div class="stat-value">Live</div>
            <div class="stat-label">Quiz Type</div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?php echo $non_host_count; ?></div>
            <div class="stat-label">Players</div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">⏱️</div>
            <div class="stat-value">--:--</div>
            <div class="stat-label">Duration</div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">📊</div>
            <div class="stat-value">Waiting</div>
            <div class="stat-label">Status</div>
        </div>
    </div>

    <!-- Status Message -->
    <div class="status-message">
        <?php if ($is_host): ?>
            <div class="host-message">
                👑 <strong>You are the host!</strong> Click "Start Quiz" when everyone is ready.
            </div>
        <?php else: ?>
            <div class="waiting-message">
                ⏳ <strong>Waiting for host to start the quiz...</strong>
            </div>
        <?php endif; ?>
    </div>

    <!-- Participants -->
    <div class="participants-section">
        <h3>
            👥 Participants 
            <span class="count"><?php echo $participant_count; ?></span>
            <button class="refresh-btn" onclick="refreshParticipants()">🔄 Refresh</button>
        </h3>
        
        <div class="participants-list" id="participantsList">
            <?php if (empty($participants)): ?>
                <div class="no-participants">
                    <div style="font-size: 48px; margin-bottom: 20px;">👻</div>
                    <h4>No participants yet</h4>
                    <p>Share the room code to invite players!</p>
                </div>
            <?php else: ?>
                <?php foreach ($participants as $participant): ?>
                    <div class="participant-item">
                        <div class="participant-avatar">
                            <?php echo $participant['is_host'] ? '👑' : '👤'; ?>
                        </div>
                        <div class="participant-info">
                            <div class="participant-name">
                                <?php echo htmlspecialchars($participant['display_name']); ?>
                                <?php if ($participant['user_id'] == $current_user_id): ?>
                                    <span class="you-label">(You)</span>
                                <?php endif; ?>
                            </div>
                            <div class="participant-status">
                                <?php echo $participant['is_host'] ? 'Host • Ready to start' : 'Participant • Ready'; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Host Controls -->
        <?php if ($is_host): ?>
            <button class="start-quiz-btn" onclick="startQuiz()" <?php echo $non_host_count < 1 ? 'disabled' : ''; ?>>
                <?php if ($non_host_count < 1): ?>
                    ⏳ Waiting for participants...
                <?php else: ?>
                    🚀 Start Quiz (<?php echo $non_host_count; ?> players)
                <?php endif; ?>
            </button>
        <?php endif; ?>
    </div>

    <!-- Invite Section -->
    <div class="invite-section">
        <h4>📢 Invite others to join!</h4>
        <p>Share the room code: <strong><?php echo htmlspecialchars($room_code); ?></strong></p>
        <div class="invite-buttons">
            <button class="whatsapp-btn" onclick="shareWhatsApp()">📱 WhatsApp</button>
            <button class="copy-link-btn" onclick="copyLink()">🔗 Copy Link</button>
        </div>
    </div>
</div>

<script>
// 🔧 FIXED JavaScript dengan path yang konsisten
const BASE_PATH = '../assets/php/'; // Definisi base path yang konsisten

function startQuiz() {
    const sessionId = <?php echo $session_id; ?>;
    
    // Disable button to prevent multiple clicks
    const startBtn = document.querySelector('.start-quiz-btn');
    if (startBtn) {
        startBtn.disabled = true;
        startBtn.textContent = 'Starting...';
    }
    
    // 🎯 FIXED: Gunakan path yang konsisten
    fetch(BASE_PATH + 'start_quiz_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            session_id: sessionId,
            action: 'start'
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Show success message
            showNotification('Quiz started successfully!', 'success');
            
            // Redirect to quiz page after short delay
            setTimeout(() => {
                window.location.href = `live_quiz.php?session_id=${sessionId}`;
            }, 1000);
        } else {
            // Show error message
            showNotification(data.message || 'Failed to start quiz', 'error');
            
            // Re-enable button
            if (startBtn) {
                startBtn.disabled = false;
                startBtn.textContent = '🚀 Start Quiz';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Network error occurred', 'error');
        
        // Re-enable button
        if (startBtn) {
            startBtn.disabled = false;
            startBtn.textContent = '🚀 Start Quiz';
        }
    });
}

function endQuiz() {
    const sessionId = <?php echo $session_id; ?>;
    
    if (confirm('Are you sure you want to end this quiz?')) {
        // 🎯 FIXED: Gunakan path yang konsisten
        fetch(BASE_PATH + 'start_quiz_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                action: 'end'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Quiz ended successfully!', 'success');
                setTimeout(() => {
                    window.location.href = 'live_results.php?session_id=' + sessionId;
                }, 1000);
            } else {
                showNotification(data.message || 'Failed to end quiz', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error occurred', 'error');
        });
    }
}

function checkQuizStatus() {
    const sessionId = <?php echo $session_id; ?>;
    
    // 🎯 FIXED: Gunakan path yang konsisten
    fetch(BASE_PATH + 'start_quiz_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            session_id: sessionId,
            action: 'status'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const status = data.data.room_status;
            
            if (status === 'active' && window.location.pathname.includes('live_waiting.php')) {
                // Quiz has started, redirect to quiz page
                window.location.href = `live_quiz.php?session_id=${sessionId}`;
            } else if (status === 'ended') {
                // Quiz has ended, redirect to results
                window.location.href = `live_results.php?session_id=${sessionId}`;
            }
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
    });
}

function refreshParticipants() {
    // Refresh halaman untuk update participants
    window.location.reload();
}

function copyRoomCode() {
    const roomCode = '<?php echo $room_code; ?>';
    navigator.clipboard.writeText(roomCode).then(() => {
        showNotification('Room code copied to clipboard!', 'success');
    }).catch(() => {
        showNotification('Failed to copy room code', 'error');
    });
}

function shareWhatsApp() {
    const roomCode = '<?php echo $room_code; ?>';
    const message = `Join my quiz! Room code: ${roomCode}`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

function copyLink() {
    const currentUrl = window.location.href;
    navigator.clipboard.writeText(currentUrl).then(() => {
        showNotification('Link copied to clipboard!', 'success');
    }).catch(() => {
        showNotification('Failed to copy link', 'error');
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        default:
            notification.style.backgroundColor = '#007bff';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.style.opacity = '1';
    }, 100);
    
    // Hide and remove notification after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Check status every 5 seconds
setInterval(checkQuizStatus, 5000);

// Check status when page loads
document.addEventListener('DOMContentLoaded', checkQuizStatus);
</script>
</body>
</html>