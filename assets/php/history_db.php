# Buat history_db.php yang diperbaiki dengan koneksi yang benar dan menggunakan stored procedures
history_db_content = '''<?php
// File: assets/php/history_db.php
require_once 'koneksi_db.php';

class HistoryManager {
    private $conn;
    
    public function __construct() {
        global $koneksi;
        $this->conn = $koneksi;
    }
    
    /**
     * Mengambil riwayat quiz user menggunakan stored procedure
     */
    public function getUserQuizHistory($user_id) {
        try {
            $stmt = $this->conn->prepare("CALL GetUserQuizHistory(?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $history = [];
            
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            $stmt->close();
            return $history;
            
        } catch (Exception $e) {
            error_log("Error in getUserQuizHistory: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengambil statistik user menggunakan stored procedure
     */
    public function getUserStats($user_id) {
        try {
            $stmt = $this->conn->prepare("CALL GetUserStatistics(?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            
            $stmt->close();
            
            // Format hasil sesuai dengan output stored procedure
            return [
                'total_sessions' => (int)$stats['total_sessions'],
                'completed_sessions' => (int)$stats['completed_sessions'],
                'hosted_sessions' => (int)$stats['hosted_sessions'],
                'live_sessions' => (int)$stats['live_sessions'],
                'normal_quiz_count' => (int)$stats['normal_quiz_count'],
                'rof_quiz_count' => (int)$stats['rof_quiz_count'],
                'decision_maker_count' => (int)$stats['decision_maker_count'],
                'avg_score' => (float)$stats['avg_score'],
                'best_score' => (float)$stats['best_score'],
                'worst_score' => (float)$stats['worst_score'],
                'total_correct_answers' => (int)$stats['total_correct_answers'],
                'total_questions_answered' => (int)$stats['total_questions_answered'],
                'overall_accuracy' => (float)$stats['overall_accuracy'],
                'first_activity' => $stats['first_activity'],
                'last_activity' => $stats['last_activity'],
                'last_connection_status' => $stats['last_connection_status']
            ];
            
        } catch (Exception $e) {
            error_log("Error in getUserStats: " . $e->getMessage());
            return [
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'hosted_sessions' => 0,
                'live_sessions' => 0,
                'normal_quiz_count' => 0,
                'rof_quiz_count' => 0,
                'decision_maker_count' => 0,
                'avg_score' => 0,
                'best_score' => 0,
                'worst_score' => 0,
                'total_correct_answers' => 0,
                'total_questions_answered' => 0,
                'overall_accuracy' => 0,
                'first_activity' => null,
                'last_activity' => null,
                'last_connection_status' => null
            ];
        }
    }
    
    /**
     * Mengambil ranking session menggunakan stored procedure
     */
    public function getSessionRanking($session_id) {
        try {
            $stmt = $this->conn->prepare("CALL GetSessionRanking(?)");
            $stmt->bind_param("i", $session_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $ranking = [];
            
            while ($row = $result->fetch_assoc()) {
                $ranking[] = $row;
            }
            
            $stmt->close();
            return $ranking;
            
        } catch (Exception $e) {
            error_log("Error in getSessionRanking: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengambil riwayat dengan filter (tanpa stored procedure untuk fleksibilitas)
     */
    public function getFilteredHistory($user_id, $quiz_type = 'all', $status = 'all', $limit = null) {
        try {
            $query = "
                SELECT 
                    qs.session_id,
                    qs.session_name,
                    q.title as quiz_title,
                    q.description as quiz_description,
                    q.quiz_type,
                    p.join_time,
                    p.is_host,
                    qs.start_time,
                    qs.end_time,
                    qs.room_status,
                    qs.room_code,
                    qs.is_live_room,
                    u.username as creator_name,
                    COALESCE(pts.score, 0) as final_score,
                    COALESCE(pts.total_correct, 0) as correct_answers,
                    COALESCE(pts.total_questions, 0) as total_questions,
                    p.connection_status,
                    p.is_guest,
                    p.guest_name,
                    CASE 
                        WHEN qs.end_time IS NOT NULL THEN 'completed'
                        WHEN qs.start_time IS NOT NULL AND qs.end_time IS NULL THEN 'active'
                        WHEN qs.room_status = 'active' THEN 'active'
                        WHEN qs.room_status = 'ended' THEN 'completed'
                        WHEN qs.room_status = 'cancelled' THEN 'cancelled'
                        ELSE 'waiting'
                    END as session_status,
                    CASE 
                        WHEN qs.start_time IS NOT NULL AND qs.end_time IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, qs.start_time, qs.end_time)
                        ELSE NULL
                    END as duration_minutes,
                    (SELECT COUNT(*) FROM participants p2 WHERE p2.session_id = qs.session_id) as total_participants,
                    CASE 
                        WHEN p.is_host = 1 THEN 'Host'
                        ELSE 'Participant'
                    END as user_role,
                    CASE 
                        WHEN pts.total_questions > 0 THEN 
                            ROUND((pts.total_correct / pts.total_questions) * 100, 2)
                        ELSE 0
                    END as accuracy_percentage
                FROM participants p
                INNER JOIN quiz_sessions qs ON p.session_id = qs.session_id
                INNER JOIN quizzes q ON qs.quiz_id = q.quiz_id
                LEFT JOIN users u ON qs.created_by = u.user_id
                LEFT JOIN points pts ON p.participant_id = pts.participant_id
                WHERE p.user_id = ?
            ";
            
            $params = [$user_id];
            $param_types = "i";
            
            // Filter berdasarkan tipe quiz
            if ($quiz_type !== 'all') {
                $query .= " AND q.quiz_type = ?";
                $params[] = $quiz_type;
                $param_types .= "s";
            }
            
            // Filter berdasarkan status
            if ($status !== 'all') {
                switch ($status) {
                    case 'completed':
                        $query .= " AND (qs.end_time IS NOT NULL OR qs.room_status = 'ended')";
                        break;
                    case 'active':
                        $query .= " AND (qs.room_status = 'active' OR (qs.start_time IS NOT NULL AND qs.end_time IS NULL))";
                        break;
                    case 'waiting':
                        $query .= " AND (qs.start_time IS NULL OR qs.room_status = 'waiting')";
                        break;
                    case 'cancelled':
                        $query .= " AND qs.room_status = 'cancelled'";
                        break;
                }
            }
            
            $query .= " ORDER BY p.join_time DESC";
            
            // Limit hasil
            if ($limit) {
                $query .= " LIMIT ?";
                $params[] = $limit;
                $param_types .= "i";
            } else {
                $query .= " LIMIT 10"; // Default limit
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $history = [];
            
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            $stmt->close();
            return $history;
            
        } catch (Exception $e) {
            error_log("Error in getFilteredHistory: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengambil detail quiz session tertentu
     */
    public function getQuizSessionDetail($session_id, $user_id) {
        try {
            $query = "
                SELECT 
                    qs.*,
                    q.title as quiz_title,
                    q.description as quiz_description,
                    q.quiz_type,
                    u.username as creator_name,
                    p.join_time,
                    p.is_host,
                    p.connection_status,
                    p.is_guest,
                    p.guest_name,
                    pts.score as final_score,
                    pts.total_correct,
                    pts.total_questions,
                    (SELECT COUNT(*) FROM participants p2 WHERE p2.session_id = qs.session_id) as total_participants,
                    CASE 
                        WHEN qs.end_time IS NOT NULL THEN 'completed'
                        WHEN qs.start_time IS NOT NULL AND qs.end_time IS NULL THEN 'active'
                        WHEN qs.room_status = 'active' THEN 'active'
                        WHEN qs.room_status = 'ended' THEN 'completed'
                        WHEN qs.room_status = 'cancelled' THEN 'cancelled'
                        ELSE 'waiting'
                    END as session_status
                FROM quiz_sessions qs
                INNER JOIN quizzes q ON qs.quiz_id = q.quiz_id
                LEFT JOIN users u ON qs.created_by = u.user_id
                LEFT JOIN participants p ON qs.session_id = p.session_id AND p.user_id = ?
                LEFT JOIN points pts ON p.participant_id = pts.participant_id
                WHERE qs.session_id = ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $session_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $detail = $result->fetch_assoc();
            
            $stmt->close();
            return $detail;
            
        } catch (Exception $e) {
            error_log("Error in getQuizSessionDetail: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mengambil quiz yang dibuat oleh user
     */
    public function getUserCreatedQuizzes($user_id, $limit = 10) {
        try {
            $query = "
                SELECT 
                    q.quiz_id,
                    q.title,
                    q.description,
                    q.quiz_type,
                    q.created_at,
                    q.updated_at,
                    COUNT(DISTINCT qs.session_id) as total_sessions,
                    COUNT(DISTINCT p.participant_id) as total_participants,
                    COALESCE(AVG(pts.score), 0) as avg_score
                FROM quizzes q
                LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id
                LEFT JOIN participants p ON qs.session_id = p.session_id
                LEFT JOIN points pts ON p.participant_id = pts.participant_id
                WHERE q.created_by = ?
                GROUP BY q.quiz_id
                ORDER BY q.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $user_id, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $quizzes = [];
            
            while ($row = $result->fetch_assoc()) {
                $quizzes[] = $row;
            }
            
            $stmt->close();
            return $quizzes;
            
        } catch (Exception $e) {
            error_log("Error in getUserCreatedQuizzes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Helper function untuk mendapatkan icon berdasarkan tipe quiz
     */
    public static function getQuizTypeIcon($quiz_type) {
        switch ($quiz_type) {
            case 'normal':
                return 'fas fa-question-circle';
            case 'rof':
                return 'fas fa-bolt';
            case 'decision_maker':
                return 'fas fa-balance-scale';
            default:
                return 'fas fa-quiz';
        }
    }
    
    /**
     * Helper function untuk mendapatkan badge class berdasarkan tipe quiz
     */
    public static function getQuizTypeBadge($quiz_type) {
        switch ($quiz_type) {
            case 'normal':
                return 'bg-blue-100 text-blue-800';
            case 'rof':
                return 'bg-red-100 text-red-800';
            case 'decision_maker':
                return 'bg-purple-100 text-purple-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
    
    /**
     * Helper function untuk mendapatkan badge class berdasarkan status
     */
    public static function getStatusBadge($status) {
        switch ($status) {
            case 'completed':
                return 'bg-green-100 text-green-800';
            case 'active':
                return 'bg-yellow-100 text-yellow-800';
            case 'waiting':
                return 'bg-gray-100 text-gray-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
    
    /**
     * Helper function untuk mendapatkan badge class berdasarkan connection status
     */
    public static function getConnectionStatusBadge($status) {
        switch ($status) {
            case 'connected':
                return 'bg-green-100 text-green-800';
            case 'disconnected':
                return 'bg-red-100 text-red-800';
            case 'reconnecting':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
    
    /**
     * Helper function untuk format tanggal
     */
    public static function formatDate($datetime) {
        if (!$datetime) return '-';
        
        $date = new DateTime($datetime);
        $now = new DateTime();
        
        $diff = $now->diff($date);
        
        // Jika kurang dari 24 jam, tampilkan relative time
        if ($diff->days == 0) {
            if ($diff->h == 0) {
                if ($diff->i == 0) {
                    return 'Baru saja';
                }
                return $diff->i . ' menit yang lalu';
            }
            return $diff->h . ' jam yang lalu';
        }
        
        // Jika kurang dari 7 hari
        if ($diff->days < 7) {
            return $diff->days . ' hari yang lalu';
        }
        
        // Format normal
        return $date->format('d M Y, H:i');
    }
    
    /**
     * Helper function untuk format durasi
     */
    public static function formatDuration($minutes) {
        if (!$minutes || $minutes <= 0) return '-';
        
        if ($minutes < 60) {
            return $minutes . ' menit';
        }
        
        $hours = floor($minutes / 60);
        $remaining_minutes = $minutes % 60;
        
        if ($remaining_minutes == 0) {
            return $hours . ' jam';
        }
        
        return $hours . ' jam ' . $remaining_minutes . ' menit';
    }
    
    /**
     * Helper function untuk format nama participant (guest atau user)
     */
    public static function formatParticipantName($username, $is_guest, $guest_name) {
        if ($is_guest && $guest_name) {
            return $guest_name . ' (Guest)';
        }
        return $username ?: 'Unknown User';
    }
    
    /**
     * Helper function untuk mendapatkan quiz type label
     */
    public static function getQuizTypeLabel($quiz_type) {
        switch ($quiz_type) {
            case 'normal':
                return 'Normal Quiz';
            case 'rof':
                return 'Race of Facts';
            case 'decision_maker':
                return 'Decision Maker';
            default:
                return ucfirst($quiz_type);
        }
    }
    
    /**
     * Destructor - koneksi akan ditutup otomatis oleh mysqli
     */
    public function __destruct() {
        // Koneksi global tidak perlu ditutup di sini
    }
}
?>'''

