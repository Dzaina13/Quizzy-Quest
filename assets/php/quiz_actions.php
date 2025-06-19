<?php
require_once 'koneksi_db.php';
require_once 'session_check.php';
require_once 'quiz_handler.php';

// Cek login
checkUserLogin();

$userInfo = getUserInfo();
$quizHandler = new QuizHandler($koneksi);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_quiz':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $quiz_type = $_POST['quiz_type'] ?? 'normal';
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Judul quiz tidak boleh kosong']);
                exit;
            }
            
            $quiz_id = $quizHandler->createQuiz($title, $description, $quiz_type, $userInfo['user_id']);
            
            if ($quiz_id) {
                echo json_encode(['success' => true, 'message' => 'Quiz berhasil dibuat', 'quiz_id' => $quiz_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal membuat quiz']);
            }
            break;
            
        case 'delete_quiz':
            $quiz_id = intval($_POST['quiz_id'] ?? 0);
            
            if ($quiz_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID quiz tidak valid']);
                exit;
            }
            
            // Admin dapat menghapus semua quiz tanpa pengecekan ownership
            if ($quizHandler->deleteQuiz($quiz_id)) {
                echo json_encode(['success' => true, 'message' => 'Quiz berhasil dihapus']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus quiz atau quiz tidak ditemukan']);
            }
            break;
            
        case 'duplicate_quiz':
            $quiz_id = intval($_POST['quiz_id'] ?? 0);
            
            if ($quiz_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID quiz tidak valid']);
                exit;
            }
            
            // Admin dapat menduplikasi semua quiz, duplikat akan dimiliki oleh admin
            $new_quiz_id = $quizHandler->duplicateQuiz($quiz_id, $userInfo['user_id']);
            
            if ($new_quiz_id) {
                echo json_encode(['success' => true, 'message' => 'Quiz berhasil diduplikasi', 'new_quiz_id' => $new_quiz_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menduplikasi quiz atau quiz tidak ditemukan']);
            }
            break;
            
        case 'update_quiz':
            $quiz_id = intval($_POST['quiz_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $quiz_type = $_POST['quiz_type'] ?? 'normal';
            
            if ($quiz_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID quiz tidak valid']);
                exit;
            }
            
            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Judul quiz tidak boleh kosong']);
                exit;
            }
            
            // Admin dapat mengupdate semua quiz
            if ($quizHandler->updateQuiz($quiz_id, $title, $description, $quiz_type)) {
                echo json_encode(['success' => true, 'message' => 'Quiz berhasil diperbarui']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal memperbarui quiz']);
            }
            break;
            
        case 'get_quiz':
            $quiz_id = intval($_POST['quiz_id'] ?? 0);
            
            if ($quiz_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID quiz tidak valid']);
                exit;
            }
            
            $quiz = $quizHandler->getQuizById($quiz_id);
            
            if ($quiz) {
                echo json_encode(['success' => true, 'quiz' => $quiz]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Quiz tidak ditemukan']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
    }
    exit;
}

// Handle GET requests (untuk AJAX load data)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $page = intval($_GET['page'] ?? 1);
    $search = $_GET['search'] ?? '';
    $type_filter = $_GET['type_filter'] ?? '';
    
    $quizzes = $quizHandler->getQuizzes($page, 10, $search, $type_filter);
    $total = $quizHandler->getTotalQuizzes($search, $type_filter);
    
    echo json_encode([
        'success' => true,
        'quizzes' => $quizzes,
        'total' => $total,
        'page' => $page,
        'total_pages' => ceil($total / 10)
    ]);
    exit;
}

// Jika bukan AJAX request, redirect ke halaman quiz
header('Location: ../../pages/admin/quis.php');
exit;
?>
