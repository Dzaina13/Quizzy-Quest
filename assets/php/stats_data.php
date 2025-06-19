<?php
require_once 'koneksi_db.php';
require_once 'session_check.php';

// Set error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek login dan role admin
checkUserLogin();
$userInfo = getUserInfo();

if ($userInfo['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

header('Content-Type: application/json');

try {
    $dateRange = isset($_GET['range']) ? (int)$_GET['range'] : 30;
    
    // 1. Key Metrics
    $metrics = getKeyMetrics($koneksi);
    
    // 2. Quiz Activity Data (untuk chart)
    $activityData = getQuizActivityData($koneksi, $dateRange);
    
    // 3. Top Quiz
    $topQuizzes = getTopQuizzes($koneksi);
    
    // 4. Top Users
    $topUsers = getTopUsers($koneksi);
    
    // 5. Quiz Details
    $quizDetails = getQuizDetails($koneksi);
    
    echo json_encode([
        'success' => true,
        'metrics' => $metrics,
        'activity' => $activityData,
        'topQuizzes' => $topQuizzes,
        'topUsers' => $topUsers,
        'quizDetails' => $quizDetails
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function getKeyMetrics($koneksi) {
    try {
        // Total Quiz Sessions
        $totalSessionsSql = "SELECT COUNT(*) as total FROM quiz_sessions";
        $totalSessionsResult = mysqli_query($koneksi, $totalSessionsSql);
        if (!$totalSessionsResult) {
            throw new Exception("Error getting total sessions: " . mysqli_error($koneksi));
        }
        $totalSessions = mysqli_fetch_assoc($totalSessionsResult)['total'];
        
        // Total Participants
        $totalParticipantsSql = "SELECT COUNT(*) as total FROM participants";
        $totalParticipantsResult = mysqli_query($koneksi, $totalParticipantsSql);
        if (!$totalParticipantsResult) {
            throw new Exception("Error getting total participants: " . mysqli_error($koneksi));
        }
        $totalParticipants = mysqli_fetch_assoc($totalParticipantsResult)['total'];
        
        // Active Users (users who participated in sessions)
        $activeUsersSql = "SELECT COUNT(DISTINCT user_id) as active FROM participants WHERE user_id IS NOT NULL";
        $activeUsersResult = mysqli_query($koneksi, $activeUsersSql);
        if (!$activeUsersResult) {
            throw new Exception("Error getting active users: " . mysqli_error($koneksi));
        }
        $activeUsers = mysqli_fetch_assoc($activeUsersResult)['active'];
        
        // Total Quizzes
        $totalQuizzesSql = "SELECT COUNT(*) as total FROM quizzes";
        $totalQuizzesResult = mysqli_query($koneksi, $totalQuizzesSql);
        if (!$totalQuizzesResult) {
            throw new Exception("Error getting total quizzes: " . mysqli_error($koneksi));
        }
        $totalQuizzes = mysqli_fetch_assoc($totalQuizzesResult)['total'];
        
        return [
            'totalSessions' => (int)$totalSessions,
            'totalParticipants' => (int)$totalParticipants,
            'activeUsers' => (int)$activeUsers,
            'totalQuizzes' => (int)$totalQuizzes
        ];
    } catch (Exception $e) {
        throw new Exception("Error in getKeyMetrics: " . $e->getMessage());
    }
}

function getQuizActivityData($koneksi, $days) {
    try {
        $labels = [];
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('j M', strtotime($date));
            
            // Count sessions created on this date - quiz_sessions tidak memiliki created_at, jadi kita pakai start_time atau fallback ke join_time dari participants
            $sql = "SELECT COUNT(DISTINCT qs.session_id) as count 
                    FROM quiz_sessions qs 
                    LEFT JOIN participants p ON qs.session_id = p.session_id 
                    WHERE DATE(COALESCE(qs.start_time, p.join_time)) = '$date'";
            $result = mysqli_query($koneksi, $sql);
            if (!$result) {
                throw new Exception("Error getting activity data: " . mysqli_error($koneksi));
            }
            $count = mysqli_fetch_assoc($result)['count'];
            $data[] = (int)$count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    } catch (Exception $e) {
        throw new Exception("Error in getQuizActivityData: " . $e->getMessage());
    }
}

function getTopQuizzes($koneksi) {
    try {
        $sql = "SELECT 
                    q.title,
                    q.description,
                    COUNT(DISTINCT qs.session_id) as session_count,
                    COUNT(DISTINCT p.participant_id) as participant_count
                FROM quizzes q
                LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id
                LEFT JOIN participants p ON qs.session_id = p.session_id
                GROUP BY q.quiz_id, q.title, q.description
                HAVING session_count > 0 OR participant_count > 0
                ORDER BY session_count DESC, participant_count DESC
                LIMIT 5";
        
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            throw new Exception("Error getting top quizzes: " . mysqli_error($koneksi));
        }
        
        $quizzes = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $quizzes[] = [
                'title' => $row['title'] ?: 'Quiz Tanpa Judul',
                'description' => $row['description'] ?: '',
                'sessions' => (int)$row['session_count'],
                'participants' => (int)$row['participant_count']
            ];
        }
        
        return $quizzes;
    } catch (Exception $e) {
        throw new Exception("Error in getTopQuizzes: " . $e->getMessage());
    }
}

function getTopUsers($koneksi) {
    try {
        $sql = "SELECT 
                    u.username,
                    u.email,
                    COUNT(DISTINCT p.participant_id) as participation_count
                FROM users u
                INNER JOIN participants p ON u.user_id = p.user_id
                GROUP BY u.user_id, u.username, u.email
                ORDER BY participation_count DESC
                LIMIT 5";
        
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            throw new Exception("Error getting top users: " . mysqli_error($koneksi));
        }
        
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = [
                'username' => $row['username'],
                'email' => $row['email'],
                'participations' => (int)$row['participation_count']
            ];
        }
        
        return $users;
    } catch (Exception $e) {
        throw new Exception("Error in getTopUsers: " . $e->getMessage());
    }
}

function getQuizDetails($koneksi) {
    try {
        $sql = "SELECT 
                    q.quiz_id,
                    q.title,
                    q.description,
                    q.quiz_type,
                    COUNT(DISTINCT qs.session_id) as sessions,
                    COUNT(DISTINCT p.participant_id) as participants,
                    COALESCE(
                        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.quiz_id),
                        (SELECT COUNT(*) FROM rof_questions WHERE rof_quiz_id = q.quiz_id),
                        (SELECT COUNT(*) FROM decision_maker_questions WHERE quiz_id = q.quiz_id),
                        0
                    ) as question_count
                FROM quizzes q
                LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id
                LEFT JOIN participants p ON qs.session_id = p.session_id
                GROUP BY q.quiz_id, q.title, q.description, q.quiz_type
                ORDER BY sessions DESC, participants DESC
                LIMIT 10";
        
        $result = mysqli_query($koneksi, $sql);
        if (!$result) {
            throw new Exception("Error getting quiz details: " . mysqli_error($koneksi));
        }
        
        $details = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $details[] = [
                'title' => $row['title'] ?: 'Quiz Tanpa Judul',
                'description' => $row['description'] ?: '',
                'type' => $row['quiz_type'],
                'sessions' => (int)$row['sessions'],
                'participants' => (int)$row['participants'],
                'questions' => (int)$row['question_count']
            ];
        }
        
        return $details;
    } catch (Exception $e) {
        throw new Exception("Error in getQuizDetails: " . $e->getMessage());
    }
}
?>
