<?php
require_once 'koneksi_db.php';
require_once 'session_check.php';

class QuizHandler {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    // Mendapatkan semua quiz dengan pagination dan filter
    public function getQuizzes($page = 1, $limit = 10, $search = '', $type_filter = '', $status_filter = '') {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $where_conditions[] = "(q.title LIKE ? OR q.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= 'ss';
        }
        
        if (!empty($type_filter) && $type_filter !== 'Semua Kategori') {
            $where_conditions[] = "q.quiz_type = ?";
            $params[] = $type_filter;
            $types .= 's';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT q.*, u.username as creator_name, 
                         COUNT(DISTINCT qs.session_id) as play_count,
                         COUNT(DISTINCT CASE WHEN q.quiz_type = 'normal' THEN quest.question_id 
                                           WHEN q.quiz_type = 'rof' THEN rq.rof_question_id 
                                           WHEN q.quiz_type = 'decision_maker' THEN dq.question_id 
                                           END) as question_count
                  FROM quizzes q 
                  LEFT JOIN users u ON q.created_by = u.user_id 
                  LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id
                  LEFT JOIN questions quest ON q.quiz_id = quest.quiz_id
                  LEFT JOIN rof_questions rq ON q.quiz_id = rq.rof_quiz_id
                  LEFT JOIN decision_maker_questions dq ON q.quiz_id = dq.quiz_id
                  $where_clause
                  GROUP BY q.quiz_id, q.title, q.description, q.quiz_type, q.created_by, q.created_at, u.username
                  ORDER BY q.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->koneksi->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $quizzes = [];
        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
        
        return $quizzes;
    }
    
    // Mendapatkan total quiz untuk pagination
    public function getTotalQuizzes($search = '', $type_filter = '') {
        $where_conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= 'ss';
        }
        
        if (!empty($type_filter) && $type_filter !== 'Semua Kategori') {
            $where_conditions[] = "quiz_type = ?";
            $params[] = $type_filter;
            $types .= 's';
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT COUNT(*) as total FROM quizzes $where_clause";
        
        $stmt = $this->koneksi->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc()['total'];
    }
    
    // Mendapatkan statistik quiz
    public function getQuizStats() {
        $stats = [];
        
        // Total quiz
        $query = "SELECT COUNT(*) as total FROM quizzes";
        $result = $this->koneksi->query($query);
        $stats['total'] = $result->fetch_assoc()['total'];
        
        // Quiz aktif (yang memiliki pertanyaan)
        $query = "SELECT COUNT(DISTINCT q.quiz_id) as active 
                  FROM quizzes q 
                  LEFT JOIN questions quest ON q.quiz_id = quest.quiz_id
                  LEFT JOIN rof_questions rq ON q.quiz_id = rq.rof_quiz_id
                  LEFT JOIN decision_maker_questions dq ON q.quiz_id = dq.quiz_id
                  WHERE quest.question_id IS NOT NULL 
                     OR rq.rof_question_id IS NOT NULL 
                     OR dq.question_id IS NOT NULL";
        $result = $this->koneksi->query($query);
        $stats['active'] = $result->fetch_assoc()['active'];
        
        // Draft (quiz tanpa pertanyaan)
        $query = "SELECT COUNT(DISTINCT q.quiz_id) as draft 
                  FROM quizzes q 
                  LEFT JOIN questions quest ON q.quiz_id = quest.quiz_id
                  LEFT JOIN rof_questions rq ON q.quiz_id = rq.rof_quiz_id
                  LEFT JOIN decision_maker_questions dq ON q.quiz_id = dq.quiz_id
                  WHERE quest.question_id IS NULL 
                    AND rq.rof_question_id IS NULL 
                    AND dq.question_id IS NULL";
        $result = $this->koneksi->query($query);
        $stats['draft'] = $result->fetch_assoc()['draft'];
        
        // Nonaktif (untuk sementara 0, bisa dikembangkan dengan status field)
        $stats['inactive'] = 0;
        
        return $stats;
    }
    
    // Membuat quiz baru
    public function createQuiz($title, $description, $quiz_type, $created_by) {
        $query = "INSERT INTO quizzes (title, description, quiz_type, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("sssi", $title, $description, $quiz_type, $created_by);
        
        if ($stmt->execute()) {
            return $this->koneksi->insert_id;
        }
        return false;
    }
    
    // Menghapus quiz - ADMIN DAPAT MENGHAPUS SEMUA QUIZ
    public function deleteQuiz($quiz_id) {
        // Cek apakah quiz exists
        $query = "SELECT quiz_id FROM quizzes WHERE quiz_id = ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result->fetch_assoc()) {
            return false; // Quiz tidak ditemukan
        }
        
        // Hapus quiz (cascade akan menghapus pertanyaan terkait)
        $query = "DELETE FROM quizzes WHERE quiz_id = ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("i", $quiz_id);
        
        return $stmt->execute();
    }
    
    // Menduplikasi quiz - ADMIN DAPAT MENDUPLIKASI SEMUA QUIZ
    public function duplicateQuiz($quiz_id, $new_creator_id) {
        // Ambil data quiz asli
        $query = "SELECT * FROM quizzes WHERE quiz_id = ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $original_quiz = $result->fetch_assoc();
        
        if (!$original_quiz) {
            return false;
        }
        
        // Buat quiz baru
        $new_title = $original_quiz['title'] . ' (Copy)';
        $new_quiz_id = $this->createQuiz($new_title, $original_quiz['description'], $original_quiz['quiz_type'], $new_creator_id);
        
        if (!$new_quiz_id) {
            return false;
        }
        
        // Duplikasi pertanyaan berdasarkan tipe quiz
        if ($original_quiz['quiz_type'] == 'normal') {
            $this->duplicateNormalQuestions($quiz_id, $new_quiz_id);
        } elseif ($original_quiz['quiz_type'] == 'rof') {
            $this->duplicateRofQuestions($quiz_id, $new_quiz_id, $new_creator_id);
        } elseif ($original_quiz['quiz_type'] == 'decision_maker') {
            $this->duplicateDecisionMakerQuestions($quiz_id, $new_quiz_id, $new_creator_id);
        }
        
        return $new_quiz_id;
    }
    
    // Update quiz - ADMIN DAPAT UPDATE SEMUA QUIZ
    public function updateQuiz($quiz_id, $title, $description, $quiz_type) {
        $query = "UPDATE quizzes SET title = ?, description = ?, quiz_type = ? WHERE quiz_id = ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("sssi", $title, $description, $quiz_type, $quiz_id);
        
        return $stmt->execute();
    }
    
    // Get single quiz - ADMIN DAPAT MELIHAT SEMUA QUIZ
    public function getQuizById($quiz_id) {
        $query = "SELECT q.*, u.username as creator_name, u.email as creator_email,
                         COUNT(DISTINCT qs.session_id) as play_count,
                         COUNT(DISTINCT CASE WHEN q.quiz_type = 'normal' THEN quest.question_id 
                                           WHEN q.quiz_type = 'rof' THEN rq.rof_question_id 
                                           WHEN q.quiz_type = 'decision_maker' THEN dq.question_id 
                                           END) as question_count
                  FROM quizzes q 
                  LEFT JOIN users u ON q.created_by = u.user_id 
                  LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id
                  LEFT JOIN questions quest ON q.quiz_id = quest.quiz_id
                  LEFT JOIN rof_questions rq ON q.quiz_id = rq.rof_quiz_id
                  LEFT JOIN decision_maker_questions dq ON q.quiz_id = dq.quiz_id
                  WHERE q.quiz_id = ?
                  GROUP BY q.quiz_id";
        
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    // Helper functions untuk duplikasi pertanyaan
    private function duplicateNormalQuestions($old_quiz_id, $new_quiz_id) {
        $query = "INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, is_decision_critical)
                  SELECT ?, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, is_decision_critical
                  FROM questions WHERE quiz_id = ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("ii", $new_quiz_id, $old_quiz_id);
        return $stmt->execute();
    }
    
    private function duplicateRofQuestions($old_quiz_id, $new_quiz_id, $new_creator_id) {
        $query = "INSERT INTO rof_questions (rof_quiz_id, question_text, correct_answer, created_by)
                  SELECT ?, question_text, correct_answer, ?
                  FROM rof_questions WHERE rof_quiz_id = ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("iii", $new_quiz_id, $new_creator_id, $old_quiz_id);
        return $stmt->execute();
    }
    
    private function duplicateDecisionMakerQuestions($old_quiz_id, $new_quiz_id, $new_creator_id) {
        $query = "INSERT INTO decision_maker_questions (quiz_id, question_text, correct_answer, created_by)
                  SELECT ?, question_text, correct_answer, ?
                  FROM decision_maker_questions WHERE quiz_id = ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("iii", $new_quiz_id, $new_creator_id, $old_quiz_id);
        return $stmt->execute();
    }
    
    // Mendapatkan icon berdasarkan tipe quiz
    public function getQuizIcon($quiz_type) {
        $icons = [
            'normal' => 'fa-question-circle',
            'rof' => 'fa-check-circle',
            'decision_maker' => 'fa-brain'
        ];
        
        return isset($icons[$quiz_type]) ? $icons[$quiz_type] : 'fa-question-circle';
    }
    
    // Mendapatkan warna badge berdasarkan tipe quiz
    public function getQuizTypeColor($quiz_type) {
        $colors = [
            'normal' => 'blue',
            'rof' => 'green',
            'decision_maker' => 'purple'
        ];
        
        return isset($colors[$quiz_type]) ? $colors[$quiz_type] : 'gray';
    }
    
    // Format tanggal
    public function formatDate($date) {
        return date('d M Y', strtotime($date));
    }
    
    // Format tanggal dengan waktu
    public function formatDateTime($date) {
        return date('d M Y H:i', strtotime($date));
    }
    
    // Get quiz type label
    public function getQuizTypeLabel($quiz_type) {
        $labels = [
            'normal' => 'Normal Quiz',
            'rof' => 'Right or False',
            'decision_maker' => 'Decision Maker'
        ];
        
        return isset($labels[$quiz_type]) ? $labels[$quiz_type] : ucfirst($quiz_type);
    }
}
?>
