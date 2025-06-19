<?php
// Tambahkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi_db.php';
require_once 'session_check.php';

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

    // Handle AJAX requests
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        handleAjaxRequest();
    } elseif (isset($_POST['action'])) {
        handlePostRequest();
    } else {
        echo json_encode(['success' => false, 'message' => 'Request tidak valid']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function handleAjaxRequest() {
    global $koneksi;
    
    try {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $statusFilter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        $roleFilter = isset($_GET['role_filter']) ? trim($_GET['role_filter']) : '';
        
        // Build WHERE clause
        $whereConditions = [];
        
        if (!empty($search)) {
            $search = mysqli_real_escape_string($koneksi, $search);
            $whereConditions[] = "(username LIKE '%$search%' OR email LIKE '%$search%')";
        }
        
        if (!empty($roleFilter) && $roleFilter !== 'all') {
            $roleFilter = mysqli_real_escape_string($koneksi, $roleFilter);
            $whereConditions[] = "role = '$roleFilter'";
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users $whereClause";
        $countResult = mysqli_query($koneksi, $countSql);
        
        if (!$countResult) {
            throw new Exception("Error counting users: " . mysqli_error($koneksi));
        }
        
        $totalUsers = mysqli_fetch_assoc($countResult)['total'];
        
        // Get users
        $sql = "SELECT user_id, username, email, role, created_at FROM users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $result = mysqli_query($koneksi, $sql);
        
        if (!$result) {
            throw new Exception("Error fetching users: " . mysqli_error($koneksi));
        }
        
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        
        $totalPages = ceil($totalUsers / $limit);
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => (int)$totalUsers
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error loading users: ' . $e->getMessage()
        ]);
    }
}

function handlePostRequest() {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get_user':
                getUserDetails();
                break;
            case 'create_user':
                createUser();
                break;
            case 'update_user':
                updateUser();
                break;
            case 'delete_user':
                deleteUser();
                break;
            case 'bulk_action':
                handleBulkAction();
                break;
            case 'send_message':
                sendMessage();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getUserDetails() {
    global $koneksi;
    
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pengguna tidak valid']);
        return;
    }
    
    $sql = "SELECT user_id, username, email, role, created_at FROM users WHERE user_id = $userId";
    $result = mysqli_query($koneksi, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($koneksi)]);
        return;
    }
    
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan']);
    }
}

function createUser() {
    global $koneksi;
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'participant';
    
    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
        return;
    }
    
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
        return;
    }
    
    // Escape input
    $username = mysqli_real_escape_string($koneksi, $username);
    $email = mysqli_real_escape_string($koneksi, $email);
    $role = mysqli_real_escape_string($koneksi, $role);
    
    // Cek username dan email sudah ada atau belum
    $checkSql = "SELECT COUNT(*) as count FROM users WHERE username = '$username' OR email = '$email'";
    $checkResult = mysqli_query($koneksi, $checkSql);
    
    if (!$checkResult) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($koneksi)]);
        return;
    }
    
    $exists = mysqli_fetch_assoc($checkResult)['count'];
    
    if ($exists > 0) {
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan']);
        return;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $hashedPassword = mysqli_real_escape_string($koneksi, $hashedPassword);
    
    $sql = "INSERT INTO users (username, email, password, role, created_at) VALUES ('$username', '$email', '$hashedPassword', '$role', NOW())";
    
    if (mysqli_query($koneksi, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Pengguna berhasil ditambahkan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan pengguna: ' . mysqli_error($koneksi)]);
    }
}

function updateUser() {
    global $koneksi;
    
    $userId = (int)($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'participant';
    
    if ($userId <= 0 || empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        return;
    }
    
    // Escape input
    $username = mysqli_real_escape_string($koneksi, $username);
    $email = mysqli_real_escape_string($koneksi, $email);
    $role = mysqli_real_escape_string($koneksi, $role);
    
    // Cek username dan email sudah ada atau belum (kecuali untuk user ini)
    $checkSql = "SELECT COUNT(*) as count FROM users WHERE (username = '$username' OR email = '$email') AND user_id != $userId";
    $checkResult = mysqli_query($koneksi, $checkSql);
    
    if (!$checkResult) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($koneksi)]);
        return;
    }
    
    $exists = mysqli_fetch_assoc($checkResult)['count'];
    
    if ($exists > 0) {
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan']);
        return;
    }
    
    $sql = "UPDATE users SET username = '$username', email = '$email', role = '$role' WHERE user_id = $userId";
    
    if (mysqli_query($koneksi, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Pengguna berhasil diupdate']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate pengguna: ' . mysqli_error($koneksi)]);
    }
}

function deleteUser() {
    global $koneksi;
    
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pengguna tidak valid']);
        return;
    }
    
    // Pastikan tidak menghapus admin terakhir
    $adminCountSql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
    $adminCountResult = mysqli_query($koneksi, $adminCountSql);
    
    if (!$adminCountResult) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($koneksi)]);
        return;
    }
    
    $adminCount = mysqli_fetch_assoc($adminCountResult)['count'];
    
    // Cek apakah user yang akan dihapus adalah admin
    $userSql = "SELECT role FROM users WHERE user_id = $userId";
    $userResult = mysqli_query($koneksi, $userSql);
    
    if (!$userResult) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($koneksi)]);
        return;
    }
    
    $user = mysqli_fetch_assoc($userResult);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan']);
        return;
    }
    
    if ($user['role'] === 'admin' && $adminCount <= 1) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus admin terakhir']);
        return;
    }
    
    // Start transaction
    mysqli_autocommit($koneksi, FALSE);
    
    try {
        // Hapus quiz yang dibuat user (jika tabel ada)
        $deleteQuizSql = "DELETE FROM quizzes WHERE created_by = $userId";
        mysqli_query($koneksi, $deleteQuizSql);
        
        // Hapus session yang dibuat user (jika tabel ada)
        $deleteSessionSql = "DELETE FROM quiz_sessions WHERE created_by = $userId";
        mysqli_query($koneksi, $deleteSessionSql);
        
        // Hapus partisipasi user (jika tabel ada)
        $deleteParticipantSql = "DELETE FROM session_participants WHERE user_id = $userId";
        mysqli_query($koneksi, $deleteParticipantSql);
        
        // Hapus user
        $deleteUserSql = "DELETE FROM users WHERE user_id = $userId";
        $result = mysqli_query($koneksi, $deleteUserSql);
        
        if (!$result) {
            throw new Exception(mysqli_error($koneksi));
        }
        
        mysqli_commit($koneksi);
        mysqli_autocommit($koneksi, TRUE);
        
        echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus']);
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        mysqli_autocommit($koneksi, TRUE);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus pengguna: ' . $e->getMessage()]);
    }
}

function handleBulkAction() {
    global $koneksi;
    
    $bulkAction = $_POST['bulk_action'] ?? '';
    $userIds = $_POST['user_ids'] ?? [];
    
    if (empty($userIds) || !is_array($userIds)) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada pengguna yang dipilih']);
        return;
    }
    
    // Sanitize user IDs
    $userIds = array_map('intval', $userIds);
    $userIds = array_filter($userIds, function($id) { return $id > 0; });
    
    if (empty($userIds)) {
        echo json_encode(['success' => false, 'message' => 'ID pengguna tidak valid']);
        return;
    }
    
    // Convert array to string for SQL IN clause
    $userIdsStr = implode(',', $userIds);
    
    mysqli_autocommit($koneksi, FALSE);
    
    try {
        switch ($bulkAction) {
            case 'delete':
                // Pastikan tidak menghapus semua admin
                $adminCountSql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
                $adminCountResult = mysqli_query($koneksi, $adminCountSql);
                
                if (!$adminCountResult) {
                    throw new Exception(mysqli_error($koneksi));
                }
                
                $totalAdmins = mysqli_fetch_assoc($adminCountResult)['count'];
                
                $adminInSelectionSql = "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND user_id IN ($userIdsStr)";
                $adminInSelectionResult = mysqli_query($koneksi, $adminInSelectionSql);
                
                if (!$adminInSelectionResult) {
                    throw new Exception(mysqli_error($koneksi));
                }
                
                $adminsToDelete = mysqli_fetch_assoc($adminInSelectionResult)['count'];
                
                if ($totalAdmins - $adminsToDelete < 1) {
                    echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus semua admin']);
                    return;
                }
                
                // Hapus data terkait
                foreach ($userIds as $userId) {
                    // Hapus quiz yang dibuat user (jika tabel ada)
                    $deleteQuizSql = "DELETE FROM quizzes WHERE created_by = $userId";
                    mysqli_query($koneksi, $deleteQuizSql);
                    
                    // Hapus session yang dibuat user (jika tabel ada)
                    $deleteSessionSql = "DELETE FROM quiz_sessions WHERE created_by = $userId";
                    mysqli_query($koneksi, $deleteSessionSql);
                    
                    // Hapus partisipasi user (jika tabel ada)
                    $deleteParticipantSql = "DELETE FROM session_participants WHERE user_id = $userId";
                    mysqli_query($koneksi, $deleteParticipantSql);
                }
                
                // Hapus users
                $deleteUsersSql = "DELETE FROM users WHERE user_id IN ($userIdsStr)";
                $result = mysqli_query($koneksi, $deleteUsersSql);
                
                if (!$result) {
                    throw new Exception(mysqli_error($koneksi));
                }
                
                $message = count($userIds) . ' pengguna berhasil dihapus';
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Aksi bulk tidak valid']);
                return;
        }
        
        mysqli_commit($koneksi);
        mysqli_autocommit($koneksi, TRUE);
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        mysqli_autocommit($koneksi, TRUE);
        echo json_encode(['success' => false, 'message' => 'Gagal melakukan aksi bulk: ' . $e->getMessage()]);
    }
}

function sendMessage() {
    // Untuk sementara, hanya simulasi
    echo json_encode(['success' => true, 'message' => 'Fitur kirim pesan sedang dalam pengembangan']);
}
?>
