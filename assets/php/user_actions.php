<?php
// Tambahkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi_db.php';
require_once 'session_check.php';
require_once 'user_handler.php';

// Set header untuk JSON response
header('Content-Type: application/json');

try {
    // Cek login dan role admin
    checkUserLogin();
    $userInfo = getUserInfo();

    if ($userInfo['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
        exit;
    }

    $userHandler = new UserHandler($koneksi);

    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            
            // Get users with pagination
            if (!isset($_GET['action']) || $_GET['action'] === 'get_users') {
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $limit = 10;
                $offset = ($page - 1) * $limit;
                
                $search = $_GET['search'] ?? '';
                $statusFilter = $_GET['status_filter'] ?? '';
                $roleFilter = $_GET['role_filter'] ?? '';
                
                $users = $userHandler->getAllUsers($search, $statusFilter, $roleFilter, $limit, $offset);
                $totalUsers = $userHandler->getTotalUsers($search, $statusFilter, $roleFilter);
                $totalPages = ceil($totalUsers / $limit);
                
                $showingStart = $offset + 1;
                $showingEnd = min($offset + $limit, $totalUsers);
                
                echo json_encode([
                    'success' => true,
                    'users' => $users,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_users' => $totalUsers,
                        'showing_start' => $showingStart,
                        'showing_end' => $showingEnd
                    ]
                ]);
                exit;
            }
            
            // Get activity log
            if ($_GET['action'] === 'get_activity_log') {
                $search = $_GET['search'] ?? '';
                $filter = $_GET['filter'] ?? '';
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                
                $activities = $userHandler->getUserActivityLogs(null, $limit, $search, $filter);
                
                echo json_encode([
                    'success' => true,
                    'activities' => $activities
                ]);
                exit;
            }
            
            // Get single user
            if ($_GET['action'] === 'get_user' && isset($_GET['id'])) {
                $userId = intval($_GET['id']);
                $user = $userHandler->getUserById($userId);
                
                if ($user) {
                    echo json_encode([
                        'success' => true,
                        'user' => $user
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Pengguna tidak ditemukan'
                    ]);
                }
                exit;
            }
        }
        
        // Export users
        if (isset($_GET['action']) && $_GET['action'] === 'export_users') {
            $search = $_GET['search'] ?? '';
            $statusFilter = $_GET['status_filter'] ?? '';
            $roleFilter = $_GET['role_filter'] ?? '';
            
            $users = $userHandler->getAllUsers($search, $statusFilter, $roleFilter, 1000, 0);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($output, [
                'ID', 'Username', 'Email', 'Role', 'Status', 
                'Last Login', 'Total Logins', 'Created At'
            ]);
            
            // CSV Data
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['user_id'],
                    $user['username'],
                    $user['email'],
                    $user['role'],
                    $user['is_online'] ? 'Online' : 'Offline',
                    $user['last_login'] ?? 'Never',
                    $user['total_logins'] ?? 0,
                    $user['created_at']
                ]);
            }
            
            fclose($output);
            exit;
        }
        
        // Export activity log
        if (isset($_GET['action']) && $_GET['action'] === 'export_activity_log') {
            $search = $_GET['search'] ?? '';
            $filter = $_GET['filter'] ?? '';
            
            $activities = $userHandler->getUserActivityLogs(null, 1000, $search, $filter);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="activity_log_export_' . date('Y-m-d_H-i-s') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($output, [
                'ID', 'Username', 'Email', 'Activity Type', 
                'IP Address', 'User Agent', 'Created At'
            ]);
            
            // CSV Data
            foreach ($activities as $activity) {
                fputcsv($output, [
                    $activity['log_id'],
                    $activity['username'],
                    $activity['email'],
                    $activity['activity_type'],
                    $activity['ip_address'] ?? '',
                    $activity['user_agent'] ?? '',
                    $activity['created_at']
                ]);
            }
            
            fclose($output);
            exit;
        }
    }

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'user';
                
                // Validation
                if (empty($username) || empty($email) || empty($password)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Semua field harus diisi'
                    ]);
                    exit;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Format email tidak valid'
                    ]);
                    exit;
                }
                
                if (strlen($password) < 6) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Password minimal 6 karakter'
                    ]);
                    exit;
                }
                
                // Check if username or email already exists
                $stmt = $koneksi->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Username atau email sudah digunakan'
                    ]);
                    exit;
                }
                
                if ($userHandler->addUser($username, $email, $password, $role)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Pengguna berhasil ditambahkan'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Gagal menambahkan pengguna'
                    ]);
                }
                break;
                
            case 'update_user':
                $userId = intval($_POST['userId'] ?? 0);
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'user';
                
                if ($userId <= 0 || empty($username) || empty($email)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Data tidak valid'
                    ]);
                    exit;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Format email tidak valid'
                    ]);
                    exit;
                }
                
                // Check if username or email already exists (except current user)
                $stmt = $koneksi->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
                $stmt->bind_param("ssi", $username, $email, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Username atau email sudah digunakan'
                    ]);
                    exit;
                }
                
                $updatePassword = !empty($password) ? $password : null;
                if ($updatePassword && strlen($updatePassword) < 6) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Password minimal 6 karakter'
                    ]);
                    exit;
                }
                
                if ($userHandler->updateUser($userId, $username, $email, $role, $updatePassword)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Pengguna berhasil diupdate'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Gagal mengupdate pengguna'
                    ]);
                }
                break;
                
            case 'delete_user':
                $userId = intval($_POST['userId'] ?? 0);
                
                if ($userId <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ID pengguna tidak valid'
                    ]);
                    exit;
                }
                
                // Prevent deleting current user
                if ($userId == $userInfo['user_id']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Tidak dapat menghapus akun sendiri'
                    ]);
                    exit;
                }
                
                if ($userHandler->deleteUser($userId)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Pengguna berhasil dihapus'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Gagal menghapus pengguna'
                    ]);
                }
                break;
                
            case 'bulk_action':
                $bulkAction = $_POST['bulk_action'] ?? '';
                $userIds = json_decode($_POST['user_ids'] ?? '[]', true);
                
                if (empty($bulkAction) || empty($userIds) || !is_array($userIds)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Data tidak valid'
                    ]);
                    exit;
                }
                
                // Remove current user from bulk actions
                $userIds = array_filter($userIds, function($id) use ($userInfo) {
                    return intval($id) !== $userInfo['user_id'];
                });
                
                if (empty($userIds)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Tidak ada pengguna yang dapat diproses'
                    ]);
                    exit;
                }
                
                $success = 0;
                $failed = 0;
                
                foreach ($userIds as $userId) {
                    $userId = intval($userId);
                    
                    switch ($bulkAction) {
                        case 'delete':
                            if ($userHandler->deleteUser($userId)) {
                                $success++;
                            } else {
                                $failed++;
                            }
                            break;
                            
                        case 'activate':
                            $stmt = $koneksi->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
                            $stmt->bind_param("i", $userId);
                            if ($stmt->execute()) {
                                $success++;
                            } else {
                                $failed++;
                            }
                            break;
                            
                        case 'deactivate':
                            $stmt = $koneksi->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
                            $stmt->bind_param("i", $userId);
                            if ($stmt->execute()) {
                                $success++;
                            } else {
                                $failed++;
                            }
                            break;
                    }
                }
                
                $message = "Berhasil: $success, Gagal: $failed";
                echo json_encode([
                    'success' => $success > 0,
                    'message' => $message
                ]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Aksi tidak valid'
                ]);
                break;
        }
        exit;
    }

    // Default response for unsupported methods
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak didukung'
    ]);

} catch (Exception $e) {
    error_log("User actions error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
?>
