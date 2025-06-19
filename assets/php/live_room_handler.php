<?php
// assets/php/live_room_handler.php - COMPLETE REVISED VERSION

class LiveRoomHandler {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    /**
     * Get all participants in a quiz session
     */
    public function getParticipants($session_id) {
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
        
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::getParticipants - Prepare failed: " . $this->koneksi->error);
            return [];
        }
        
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $participants = [];
        $seen_users = [];
        
        while ($row = $result->fetch_assoc()) {
            // Create unique key to prevent duplicates
            $unique_key = $row['user_id'] ? 'user_' . $row['user_id'] : 'guest_' . $row['guest_name'];
            
            if (isset($seen_users[$unique_key])) {
                continue; // Skip duplicate
            }
            $seen_users[$unique_key] = true;
            
            // Determine display name
            $display_name = '';
            if ($row['is_guest'] == 1) {
                $display_name = $row['guest_name'] ?: 'Guest User';
            } else {
                $display_name = $row['registered_username'] ?: 'Unknown User';
            }
            
            // Skip if display name is invalid
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
        
        $stmt->close();
        return $participants;
    }
    
    /**
     * Add a new participant to quiz session
     */
    public function addParticipant($session_id, $user_id = null, $guest_name = null, $is_host = false) {
        try {
            // Validate input
            if (!$session_id) {
                error_log("LiveRoomHandler::addParticipant - Invalid session_id");
                return false;
            }
            
            if (!$user_id && !$guest_name) {
                error_log("LiveRoomHandler::addParticipant - Neither user_id nor guest_name provided");
                return false;
            }
            
            // Check if participant already exists
            if ($user_id) {
                $check_query = "SELECT participant_id FROM participants WHERE session_id = ? AND user_id = ? AND connection_status = 'connected'";
                $check_stmt = $this->koneksi->prepare($check_query);
                if (!$check_stmt) {
                    error_log("LiveRoomHandler::addParticipant - Check prepare failed: " . $this->koneksi->error);
                    return false;
                }
                
                $check_stmt->bind_param("ii", $session_id, $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $row = $check_result->fetch_assoc();
                    $check_stmt->close();
                    error_log("LiveRoomHandler::addParticipant - Found existing user participant: " . $row['participant_id']);
                    return $row['participant_id']; // Return existing participant ID
                }
                $check_stmt->close();
            } else if ($guest_name) {
                // Check for duplicate guest names
                $check_query = "SELECT participant_id FROM participants WHERE session_id = ? AND guest_name = ? AND connection_status = 'connected'";
                $check_stmt = $this->koneksi->prepare($check_query);
                if (!$check_stmt) {
                    error_log("LiveRoomHandler::addParticipant - Guest check prepare failed: " . $this->koneksi->error);
                    return false;
                }
                
                $check_stmt->bind_param("is", $session_id, $guest_name);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $row = $check_result->fetch_assoc();
                    $check_stmt->close();
                    error_log("LiveRoomHandler::addParticipant - Found existing guest participant: " . $row['participant_id']);
                    return $row['participant_id']; // Return existing participant ID
                }
                $check_stmt->close();
            }
            
            $is_guest = empty($user_id) ? 1 : 0;
            
            $query = "
                INSERT INTO participants (session_id, user_id, is_guest, guest_name, is_host, connection_status, join_time)
                VALUES (?, ?, ?, ?, ?, 'connected', NOW())
            ";
            
            $stmt = $this->koneksi->prepare($query);
            if (!$stmt) {
                error_log("LiveRoomHandler::addParticipant - Insert prepare failed: " . $this->koneksi->error);
                return false;
            }
            
            $stmt->bind_param("iisii", $session_id, $user_id, $is_guest, $guest_name, $is_host);
            $success = $stmt->execute();
            
            if ($success) {
                $participant_id = $this->koneksi->insert_id;
                $stmt->close();
                error_log("LiveRoomHandler::addParticipant - Successfully created participant: " . $participant_id);
                
                // Log activity
                $this->logParticipantActivity($session_id, $participant_id, 'joined', [
                    'user_id' => $user_id,
                    'guest_name' => $guest_name,
                    'is_host' => $is_host
                ]);
                
                return $participant_id;
            } else {
                error_log("LiveRoomHandler::addParticipant - Insert failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
        } catch (Exception $e) {
            error_log("LiveRoomHandler::addParticipant - Exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is host of the session
     */
    public function isUserHost($session_id, $user_id) {
        if (!$user_id) {
            return false; // Guests cannot be hosts
        }
        
        $query = "
            SELECT qs.created_by, p.is_host 
            FROM quiz_sessions qs
            LEFT JOIN participants p ON qs.session_id = p.session_id AND p.user_id = ? AND p.connection_status = 'connected'
            WHERE qs.session_id = ?
        ";
        
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::isUserHost - Prepare failed: " . $this->koneksi->error);
            return false;
        }
        
        $stmt->bind_param("ii", $user_id, $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            return false;
        }
        
        // User is host if they created the session OR marked as host in participants
        return ($row['created_by'] == $user_id || $row['is_host'] == 1);
    }
    
    /**
     * Get current quiz status
     */
    public function getQuizStatus($session_id) {
        $query = "SELECT room_status FROM quiz_sessions WHERE session_id = ?";
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::getQuizStatus - Prepare failed: " . $this->koneksi->error);
            return 'waiting';
        }
        
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['room_status'] : 'waiting';
    }
    
    /**
     * Update quiz status
     */
    public function updateQuizStatus($session_id, $status) {
        $valid_statuses = ['waiting', 'active', 'ended'];
        if (!in_array($status, $valid_statuses)) {
            error_log("LiveRoomHandler::updateQuizStatus - Invalid status: " . $status);
            return false;
        }
        
        $query = "UPDATE quiz_sessions SET room_status = ? WHERE session_id = ?";
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::updateQuizStatus - Prepare failed: " . $this->koneksi->error);
            return false;
        }
        
        $stmt->bind_param("si", $status, $session_id);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("LiveRoomHandler::updateQuizStatus - Update failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Get complete session information
     */
    public function getSessionInfo($session_id) {
        $query = "
            SELECT 
                qs.*,
                q.title as quiz_title,
                q.quiz_type,
                u.username as host_username
            FROM quiz_sessions qs
            LEFT JOIN quizzes q ON qs.quiz_id = q.quiz_id
            LEFT JOIN users u ON qs.created_by = u.user_id
            WHERE qs.session_id = ?
        ";
        
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::getSessionInfo - Prepare failed: " . $this->koneksi->error);
            return null;
        }
        
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        return $session;
    }
    
    /**
     * Remove participant from session (disconnect)
     */
    public function removeParticipant($session_id, $participant_id) {
        $query = "UPDATE participants SET connection_status = 'disconnected' WHERE session_id = ? AND participant_id = ?";
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::removeParticipant - Prepare failed: " . $this->koneksi->error);
            return false;
        }
        
        $stmt->bind_param("ii", $session_id, $participant_id);
        $success = $stmt->execute();
        
        if ($success) {
            $this->logParticipantActivity($session_id, $participant_id, 'disconnected');
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Get participant scores for a specific quiz type - FIXED VERSION
     */
    public function getParticipantScores($session_id, $quiz_type, $quiz_id) {
        $participants = $this->getParticipants($session_id);
        $scores = [];
        
        foreach ($participants as $participant) {
            if ($participant['is_host']) {
                continue; // Skip host
            }
            
            $participant_id = $participant['participant_id'];
            $score = 0;
            $answered = 0;
            
            if ($quiz_type === 'rof') {
                // FIXED: Use participant_id instead of rof_participant_id
                $query = "
                    SELECT COUNT(*) as answered, SUM(is_correct) as score 
                    FROM rof_answers 
                    WHERE participant_id = ?
                ";
                $stmt = $this->koneksi->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $participant_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $answered = $row['answered'] ?? 0;
                    $score = $row['score'] ?? 0;
                    $stmt->close();
                }
            } elseif ($quiz_type === 'decision_maker') {
                $query = "
                    SELECT COUNT(*) as answered, SUM(is_correct) as score 
                    FROM user_answers 
                    WHERE participant_id = ?
                ";
                $stmt = $this->koneksi->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $participant_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $answered = $row['answered'] ?? 0;
                    $score = $row['score'] ?? 0;
                    $stmt->close();
                }
            } else {
                // Regular quiz
                $query = "
                    SELECT COUNT(*) as answered, SUM(is_correct) as score 
                    FROM user_answers 
                    WHERE participant_id = ?
                ";
                $stmt = $this->koneksi->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $participant_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $answered = $row['answered'] ?? 0;
                    $score = $row['score'] ?? 0;
                    $stmt->close();
                }
            }
            
            // Get total questions for this quiz
            $total_questions = $this->getTotalQuestions($quiz_id, $quiz_type);
            
            $scores[] = [
                'participant_id' => $participant_id,
                'name' => $participant['display_name'],
                'score' => (int)$score,
                'answered' => (int)$answered,
                'total' => $total_questions,
                'percentage' => $total_questions > 0 ? round(($score / $total_questions) * 100, 1) : 0
            ];
        }
        
        // Sort by score descending, then by answered descending
        usort($scores, function($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['answered'] - $a['answered'];
            }
            return $b['score'] - $a['score'];
        });
        
        return $scores;
    }
    
    /**
     * Get total questions for a quiz
     */
    public function getTotalQuestions($quiz_id, $quiz_type) {
        $table = '';
        $id_field = '';
        
        switch ($quiz_type) {
            case 'rof':
                $table = 'rof_questions';
                $id_field = 'rof_quiz_id';
                break;
            case 'decision_maker':
                $table = 'decision_maker_questions';
                $id_field = 'quiz_id';
                break;
            default:
                $table = 'questions';
                $id_field = 'quiz_id';
                break;
        }
        
        $query = "SELECT COUNT(*) as total FROM {$table} WHERE {$id_field} = ?";
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::getTotalQuestions - Prepare failed: " . $this->koneksi->error);
            return 0;
        }
        
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Check if session exists and is valid
     */
    public function sessionExists($session_id) {
        $query = "SELECT session_id FROM quiz_sessions WHERE session_id = ?";
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::sessionExists - Prepare failed: " . $this->koneksi->error);
            return false;
        }
        
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Get quiz questions based on type
     */
    public function getQuizQuestions($quiz_id, $quiz_type) {
        $questions = [];
        
        if ($quiz_type === 'rof') {
            $query = "
                SELECT rq.*, rq.question_text as question_text 
                FROM rof_questions rq 
                WHERE rq.rof_quiz_id = ? 
                ORDER BY rq.rof_question_id
            ";
            $stmt = $this->koneksi->prepare($query);
            if ($stmt) {
                $stmt->bind_param("i", $quiz_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $questions[] = $row;
                }
                $stmt->close();
            }
        } elseif ($quiz_type === 'decision_maker') {
            $query = "
                SELECT dm.*, dm.question_text as question_text 
                FROM decision_maker_questions dm 
                WHERE dm.quiz_id = ? 
                ORDER BY dm.question_id
            ";
            $stmt = $this->koneksi->prepare($query);
            if ($stmt) {
                $stmt->bind_param("i", $quiz_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $questions[] = $row;
                }
                $stmt->close();
            }
        } else {
            $query = "
                SELECT q.* 
                FROM questions q 
                WHERE q.quiz_id = ? 
                ORDER BY q.question_id
            ";
            $stmt = $this->koneksi->prepare($query);
            if ($stmt) {
                $stmt->bind_param("i", $quiz_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $questions[] = $row;
                }
                $stmt->close();
            }
        }
        
        return $questions;
    }
    
    /**
     * Clean up old disconnected participants
     */
    public function cleanupOldParticipants($session_id, $hours_old = 24) {
        $query = "
            DELETE FROM participants 
            WHERE session_id = ? 
            AND connection_status = 'disconnected' 
            AND join_time < DATE_SUB(NOW(), INTERVAL ? HOUR)
        ";
        
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::cleanupOldParticipants - Prepare failed: " . $this->koneksi->error);
            return false;
        }
        
        $stmt->bind_param("ii", $session_id, $hours_old);
        $success = $stmt->execute();
        
        if ($success) {
            $deleted_count = $stmt->affected_rows;
            error_log("LiveRoomHandler::cleanupOldParticipants - Cleaned up {$deleted_count} old participants");
        }
        
        $stmt->close();
        return $success;
    }
    
    /**
     * Get session statistics
     */
    public function getSessionStats($session_id) {
        $stats = [
            'total_participants' => 0,
            'active_participants' => 0,
            'completed_participants' => 0,
            'average_score' => 0,
            'highest_score' => 0,
            'session_duration' => 0
        ];
        
        // Get basic participant counts
        $query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN connection_status = 'connected' THEN 1 ELSE 0 END) as active
            FROM participants 
            WHERE session_id = ?
        ";
        
        $stmt = $this->koneksi->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $session_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['total_participants'] = (int)$row['total'];
            $stats['active_participants'] = (int)$row['active'];
            $stmt->close();
        }
        
        // Get session duration
        $query = "
            SELECT 
                created_at,
                CASE 
                    WHEN ended_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, ended_at)
                    ELSE TIMESTAMPDIFF(SECOND, created_at, NOW())
                END as duration_seconds
            FROM quiz_sessions 
            WHERE session_id = ?
        ";
        
        $stmt = $this->koneksi->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $session_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['session_duration'] = (int)($row['duration_seconds'] ?? 0);
            $stmt->close();
        }
        
        return $stats;
    }
    
    /**
     * Log participant activity - SAFE VERSION
     */
    public function logParticipantActivity($session_id, $participant_id, $activity_type, $details = null) {
        // Check if activity log table exists first
        $check_table = $this->koneksi->query("SHOW TABLES LIKE 'participant_activity_log'");
        if ($check_table->num_rows == 0) {
            // Table doesn't exist, skip logging
            return true;
        }
        
        $query = "
            INSERT INTO participant_activity_log (session_id, participant_id, activity_type, activity_details, activity_time)
            VALUES (?, ?, ?, ?, NOW())
        ";
        
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::logParticipantActivity - Prepare failed: " . $this->koneksi->error);
            return false;
        }
        
        $details_json = $details ? json_encode($details) : null;
        $stmt->bind_param("iiss", $session_id, $participant_id, $activity_type, $details_json);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Validate quiz session access
     */
    public function validateSessionAccess($session_id, $user_id = null, $guest_name = null) {
        // Check if session exists
        if (!$this->sessionExists($session_id)) {
            return ['valid' => false, 'reason' => 'Session not found'];
        }
        
        // Check session status
        $session_info = $this->getSessionInfo($session_id);
        if (!$session_info) {
            return ['valid' => false, 'reason' => 'Unable to get session info'];
        }
        
        // Check if session is expired (optional - implement based on your needs)
        // You can add expiration logic here
        
        return ['valid' => true, 'session_info' => $session_info];
    }
    
    /**
     * Get participant by ID
     */
    public function getParticipantById($participant_id) {
        $query = "
            SELECT 
                p.*,
                u.username as registered_username,
                CASE 
                    WHEN p.is_guest = 1 THEN p.guest_name 
                    ELSE u.username 
                END as display_name
            FROM participants p
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE p.participant_id = ?
        ";
        
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::getParticipantById - Prepare failed: " . $this->koneksi->error);
            return null;
        }
        
        $stmt->bind_param("i", $participant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $participant = $result->fetch_assoc();
        $stmt->close();
        
        return $participant;
    }
    
    /**
     * Update participant connection status
     */
    public function updateParticipantStatus($participant_id, $status) {
        $valid_statuses = ['connected', 'disconnected', 'timeout'];
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        $query = "UPDATE participants SET connection_status = ? WHERE participant_id = ?";
        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("LiveRoomHandler::updateParticipantStatus - Prepare failed: " . $this->koneksi->error);
            return false;
        }
        
        $stmt->bind_param("si", $status, $participant_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get participant's current quiz progress
     */
    public function getParticipantProgress($participant_id, $quiz_type) {
        $progress = [
            'answered' => 0,
            'score' => 0,
            'last_answer_time' => null
        ];
        
        if ($quiz_type === 'rof') {
            $query = "
                SELECT 
                    COUNT(*) as answered,
                    SUM(is_correct) as score,
                    MAX(answered_at) as last_answer_time
                FROM rof_answers 
                WHERE participant_id = ?
            ";
        } else {
            $query = "
                SELECT 
                    COUNT(*) as answered,
                    SUM(is_correct) as score,
                    MAX(answered_at) as last_answer_time
                FROM user_answers 
                WHERE participant_id = ?
            ";
        }
        
        $stmt = $this->koneksi->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $participant_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $progress['answered'] = (int)($row['answered'] ?? 0);
            $progress['score'] = (int)($row['score'] ?? 0);
            $progress['last_answer_time'] = $row['last_answer_time'];
            
            $stmt->close();
        }
        
        return $progress;
    }
}
?>
