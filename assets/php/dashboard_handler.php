<?php
require_once 'koneksi_db.php';
require_once 'session_check.php';

class DashboardHandler {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    // Mendapatkan total quiz
    public function getTotalQuizzes() {
        $query = "SELECT COUNT(*) as total FROM quizzes";
        $result = $this->koneksi->query($query);
        return $result->fetch_assoc()['total'];
    }
    
    // Mendapatkan total pengguna
    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as total FROM users";
        $result = $this->koneksi->query($query);
        return $result->fetch_assoc()['total'];
    }
    
    // Mendapatkan total quiz yang dimainkan
    public function getTotalQuizSessions() {
        $query = "SELECT COUNT(*) as total FROM quiz_sessions";
        $result = $this->koneksi->query($query);
        return $result->fetch_assoc()['total'];
    }
    
    // Mendapatkan rata-rata skor (simulasi - karena belum ada data lengkap)
    public function getAverageScore() {
        // Simulasi karena struktur scoring masih belum lengkap
        return 78.5;
    }
    
    // Mendapatkan quiz terpopuler
    public function getPopularQuizzes($limit = 3) {
        $query = "SELECT q.title, q.quiz_id, COUNT(qs.session_id) as play_count 
                  FROM quizzes q 
                  LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id 
                  GROUP BY q.quiz_id, q.title 
                  ORDER BY play_count DESC 
                  LIMIT ?";
        
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $quizzes = [];
        while ($row = $result->fetch_assoc()) {
            $quizzes[] = $row;
        }
        
        return $quizzes;
    }
    
    // Mendapatkan aktivitas terbaru
    public function getRecentActivities($limit = 4) {
        $activities = [];
        
        // Pengguna baru terdaftar
        $query = "SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'type' => 'user_register',
                'message' => "Pengguna baru <strong>{$row['username']}</strong> mendaftar",
                'time' => $this->timeAgo($row['created_at']),
                'icon' => 'fa-user-plus',
                'color' => 'blue'
            ];
        }
        
        // Quiz baru ditambahkan
        $query = "SELECT title, created_at FROM quizzes ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->koneksi->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $activities[] = [
                'type' => 'quiz_added',
                'message' => "Quiz baru <strong>\"{$row['title']}\"</strong> ditambahkan",
                'time' => $this->timeAgo($row['created_at']),
                'icon' => 'fa-plus',
                'color' => 'green'
            ];
        }
        
        // Urutkan berdasarkan waktu dan ambil sesuai limit
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    // Fungsi helper untuk menghitung waktu relatif
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return $time . ' detik yang lalu';
        if ($time < 3600) return floor($time/60) . ' menit yang lalu';
        if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
        if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
        if ($time < 31536000) return floor($time/2592000) . ' bulan yang lalu';
        return floor($time/31536000) . ' tahun yang lalu';
    }
    
    // Mendapatkan statistik pertumbuhan (simulasi)
    public function getGrowthStats() {
        return [
            'quiz_growth' => '+12%',
            'user_growth' => '+8%',
            'session_growth' => '+25%',
            'score_growth' => '-2%'
        ];
    }
}
?>
