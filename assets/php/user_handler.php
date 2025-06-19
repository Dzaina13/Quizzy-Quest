<?php
class UserHandler {
    private $koneksi;
    
    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }
    
    public function getAllUsers($search = '', $statusFilter = '', $roleFilter = '', $limit = 10, $offset = 0) {
        try {
            $whereConditions = [];
            $params = [];
            $types = '';
            
            // Search condition
            if (!empty($search)) {
                $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'ss';
            }
            
            // Role filter
            if (!empty($roleFilter)) {
                $whereConditions[] = "u.role = ?";
                $params[] = $roleFilter;
                $types .= 's';
            }
            
            // Status filter (simplified - just check if user exists)
            if (!empty($statusFilter)) {
                if ($statusFilter === 'active') {
                    $whereConditions[] = "u.user_id IS NOT NULL";
                }
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT 
                        u.user_id,
                        u.username,
                        u.email,
                        u.role,
                        u.created_at,
                        COALESCE(login_stats.last_login, 'Never') as last_login,
                        COALESCE(login_stats.total_logins, 0) as total_logins,
                        'Offline' as status
                    FROM users u
                    LEFT JOIN (
                        SELECT 
                            user_id,
                            MAX(created_at) as last_login,
                            COUNT(*) as total_logins
                        FROM user_activity_logs 
                        WHERE activity_type = 'login'
                        GROUP BY user_id
                    ) login_stats ON u.user_id = login_stats.user_id
                    $whereClause
                    ORDER BY u.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = $this->koneksi->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            
            return $users;
            
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalUsers($search = '', $statusFilter = '', $roleFilter = '') {
        try {
            $whereConditions = [];
            $params = [];
            $types = '';
            
            // Search condition
            if (!empty($search)) {
                $whereConditions[] = "(username LIKE ? OR email LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'ss';
            }
            
            // Role filter
            if (!empty($roleFilter)) {
                $whereConditions[] = "role = ?";
                $params[] = $roleFilter;
                $types .= 's';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT COUNT(*) as total FROM users $whereClause";
            
            $stmt = $this->koneksi->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row['total'];
            
        } catch (Exception $e) {
            error_log("Get total users error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getUserById($userId) {
        try {
            $stmt = $this->koneksi->prepare("SELECT user_id, username, email, role, created_at FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    public function addUser($username, $email, $password, $role = 'user') {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->koneksi->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Add user error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function updateUser($userId, $username, $email, $role, $password = null) {
        try {
            if ($password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->koneksi->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $username, $email, $role, $hashedPassword, $userId);
            } else {
                $stmt = $this->koneksi->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $username, $email, $role, $userId);
            }
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteUser($userId) {
        try {
            // Start transaction
            $this->koneksi->begin_transaction();
            
            // Delete user activity logs first
            $stmt = $this->koneksi->prepare("DELETE FROM user_activity_logs WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            // Delete user
            $stmt = $this->koneksi->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $result = $stmt->execute();
            
            if ($result) {
                $this->koneksi->commit();
                return true;
            } else {
                $this->koneksi->rollback();
                return false;
            }
            
        } catch (Exception $e) {
            $this->koneksi->rollback();
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserActivityLogs($userId = null, $limit = 50, $search = '', $filter = '') {
        try {
            $whereConditions = [];
            $params = [];
            $types = '';
            
            // User filter
            if ($userId) {
                $whereConditions[] = "ual.user_id = ?";
                $params[] = $userId;
                $types .= 'i';
            }
            
            // Search condition
            if (!empty($search)) {
                $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ? OR ual.activity_type LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'sss';
            }
            
            // Activity type filter
            if (!empty($filter)) {
                $whereConditions[] = "ual.activity_type = ?";
                $params[] = $filter;
                $types .= 's';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $sql = "SELECT 
                        ual.log_id,
                        ual.user_id,
                        u.username,
                        u.email,
                        ual.activity_type,
                        ual.ip_address,
                        ual.user_agent,
                        ual.created_at
                    FROM user_activity_logs ual
                    LEFT JOIN users u ON ual.user_id = u.user_id
                    $whereClause
                    ORDER BY ual.created_at DESC
                    LIMIT ?";
            
            $params[] = $limit;
            $types .= 'i';
            
            $stmt = $this->koneksi->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = [];
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            
            return $activities;
            
        } catch (Exception $e) {
            error_log("Get user activity logs error: " . $e->getMessage());
            return [];
        }
    }
    
    public function recordLogin($userId, $ipAddress, $userAgent = null, $sessionId = null) {
        try {
            $stmt = $this->koneksi->prepare("INSERT INTO user_activity_logs (user_id, activity_type, ip_address, user_agent, session_id, created_at) VALUES (?, 'login', ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $userId, $ipAddress, $userAgent, $sessionId);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Record login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function recordLogout($userId, $sessionId = null) {
        try {
            $stmt = $this->koneksi->prepare("INSERT INTO user_activity_logs (user_id, activity_type, session_id, created_at) VALUES (?, 'logout', ?, NOW())");
            $stmt->bind_param("is", $userId, $sessionId);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Record logout error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getOnlineUsers() {
        // Return empty array since we don't have online_users table
        return [];
    }
    
    public function getTotalOnlineUsers() {
        // Return 0 since we don't have online_users table
        return 0;
    }
    
    public function getUserStats() {
        try {
            $stats = [];
            
            // Total users
            $stmt = $this->koneksi->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_users'] = $result->fetch_assoc()['total'];
            
            // Total admins
            $stmt = $this->koneksi->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_admins'] = $result->fetch_assoc()['total'];
            
            // Total regular users
            $stmt = $this->koneksi->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_regular_users'] = $result->fetch_assoc()['total'];
            
            // Online users (always 0 since we don't track)
            $stats['online_users'] = 0;
            
            // Recent logins (last 24 hours)
            $stmt = $this->koneksi->prepare("SELECT COUNT(DISTINCT user_id) as total FROM user_activity_logs WHERE activity_type = 'login' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['recent_logins'] = $result->fetch_assoc()['total'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return [
                'total_users' => 0,
                'total_admins' => 0,
                'total_regular_users' => 0,
                'online_users' => 0,
                'recent_logins' => 0
            ];
        }
    }
}
?>
