<?php
require_once '../../assets/php/koneksi_db.php';
require_once '../../assets/php/session_check.php';
require_once '../../assets/php/user_handler.php';

// Cek login dan role admin
checkUserLogin();
$userInfo = getUserInfo();

if ($userInfo['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$userHandler = new UserHandler($koneksi);

// Get user statistics
$stats = $userHandler->getUserStats();

// Get recent users (simplified)
try {
    $recentUsers = $userHandler->getAllUsers('', '', '', 5, 0);
} catch (Exception $e) {
    error_log("Get recent users error: " . $e->getMessage());
    $recentUsers = [];
}

// Get recent activities
try {
    $recentActivities = $userHandler->getUserActivityLogs(null, 20);
} catch (Exception $e) {
    error_log("Get recent activities error: " . $e->getMessage());
    $recentActivities = [];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Quizzy Quest Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'pink-gradient-start': '#ff6b9d',
                        'pink-gradient-end': '#c44569'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="flex h-screen">
        <div class="w-64 bg-gradient-to-b from-pink-gradient-start to-pink-gradient-end text-white">
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-8">Quizzy Quest</h1>
                <nav class="space-y-4">
                    <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="quis.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-question-circle"></i>
                        <span>Kelola Quiz</span>
                    </a>
                    <a href="pengguna.php" class="flex items-center space-x-3 p-3 rounded-lg bg-white bg-opacity-20 hover:bg-opacity-30 transition-all">
                        <i class="fas fa-users"></i>
                        <span>Pengguna</span>
                    </a>
                    <a href="statistik.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik</span>
                    </a>
                    <a href="pengaturan.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </a>
                </nav>
            </div>
            <div class="absolute bottom-0 w-64 p-6">
                <div class="flex items-center space-x-3 p-3 rounded-lg bg-white bg-opacity-20">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-pink-500"></i>
                    </div>
                    <div>
                        <p class="font-semibold"><?php echo htmlspecialchars($userInfo['username']); ?></p>
                        <p class="text-sm opacity-75"><?php echo htmlspecialchars($userInfo['email']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Kelola Pengguna</h2>
                        <p class="text-gray-600 mt-1">Kelola semua pengguna dan aktivitas mereka</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <i class="fas fa-bell text-gray-500 text-xl"></i>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                        </div>
                        <button onclick="logout()" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-4 py-2 rounded-lg hover:opacity-90 transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </div>
                </div>
            </header>

            <!-- User Management Content -->
            <main class="p-6">
                <!-- Action Bar -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div class="flex flex-col md:flex-row gap-4 flex-1">
                            <!-- Search -->
                            <div class="relative flex-1 max-w-md">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="searchInput" placeholder="Cari pengguna..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            </div>
                            
                            <!-- Filters -->
                            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                            </select>
                            
                            <select id="roleFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                                <option value="">Semua Role</option>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="flex gap-3">
                            <button onclick="exportUsers()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all">
                                <i class="fas fa-download mr-2"></i>Export
                            </button>
                            <button onclick="showAddUserModal()" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-4 py-2 rounded-lg hover:opacity-90 transition-all">
                                <i class="fas fa-plus mr-2"></i>Tambah Pengguna
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Pengguna</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_users'] ?? 0; ?></p>
                                <p class="text-xs text-green-600">Terdaftar</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-check text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">User Reguler</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_regular_users'] ?? 0; ?></p>
                                <p class="text-xs text-green-600">Pengguna biasa</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-crown text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Admin</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_admins'] ?? 0; ?></p>
                                <p class="text-xs text-green-600">Administrator</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                                <i class="fas fa-sign-in-alt text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Login 24 Jam</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['recent_logins'] ?? 0; ?></p>
                                <p class="text-xs text-green-600">Aktivitas terbaru</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Login Activity Log Button -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Aktivitas Login/Logout</h3>
                            <p class="text-gray-600 text-sm">Lihat log lengkap aktivitas pengguna</p>
                        </div>
                        <button onclick="showActivityLogModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all">
                            <i class="fas fa-history mr-2"></i>Lihat Log Aktivitas
                        </button>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Daftar Pengguna</h3>
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input type="checkbox" id="selectAll" class="mr-2 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                    <span class="text-sm text-gray-600">Pilih Semua</span>
                                </label>
                                <select id="bulkAction" class="px-3 py-1 border border-gray-300 rounded text-sm">
                                    <option value="">Aksi Massal</option>
                                    <option value="delete">Hapus Terpilih</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login Terakhir</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bergabung</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Data akan dimuat via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Menampilkan <span id="showingStart">1</span> sampai <span id="showingEnd">10</span> dari <span id="totalUsers">0</span> pengguna
                            </div>
                            <div class="flex space-x-2" id="paginationContainer">
                                <!-- Pagination buttons akan dimuat via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Activity Log -->
    <div id="activityLogModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Log Aktivitas Login/Logout</h3>
                    <button onclick="closeActivityLogModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Filter untuk modal -->
                <div class="flex flex-wrap gap-4 mb-4">
                    <input type="text" id="activitySearch" placeholder="Cari aktivitas..." 
                           class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                    <select id="activityFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-pink-500 focus:border-pink-500">
                        <option value="">Semua Aktivitas</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                    </select>
                    <button onclick="exportActivityLog()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </button>
                </div>
                
                <div id="activityLogContent" class="max-h-96 overflow-y-auto">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                        <p class="text-gray-500 mt-2">Memuat data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add/Edit User -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Tambah Pengguna Baru</h3>
                <form id="userForm">
                    <input type="hidden" id="userId" name="userId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" id="username" name="username" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div class="mb-4" id="passwordField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" id="password" name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select id="role" name="role" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeUserModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Batal
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white rounded-md hover:opacity-90">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        let isLoading = false;

        // Load users on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Event listeners
            document.getElementById('searchInput').addEventListener('input', debounce(loadUsers, 300));
            document.getElementById('statusFilter').addEventListener('change', loadUsers);
            document.getElementById('roleFilter').addEventListener('change', loadUsers);
            document.getElementById('selectAll').addEventListener('change', toggleSelectAll);
            document.getElementById('bulkAction').addEventListener('change', handleBulkAction);
            document.getElementById('userForm').addEventListener('submit', handleUserSubmit);
            document.getElementById('activitySearch').addEventListener('input', debounce(filterActivityLog, 300));
            document.getElementById('activityFilter').addEventListener('change', filterActivityLog);
        });

        function loadUsers(page = 1) {
            if (isLoading) return;
            isLoading = true;
            
            const search = document.getElementById('searchInput').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const roleFilter = document.getElementById('roleFilter').value;
            
            const params = new URLSearchParams({
                ajax: '1',
                page: page,
                search: search,
                status_filter: statusFilter,
                role_filter: roleFilter
            });
            
            fetch(`../../assets/php/user_actions.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUsers(data.users);
                        updatePagination(data.pagination);
                        currentPage = page;
                    } else {
                        showAlert('error', data.message || 'Gagal memuat data pengguna');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan saat memuat data');
                })
                .finally(() => {
                    isLoading = false;
                });
        }

        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            
            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-4 opacity-50"></i>
                            <p>Tidak ada pengguna ditemukan</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            users.forEach(user => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <input type="checkbox" class="user-checkbox mr-3" value="${user.user_id}">
                            <div class="w-10 h-10 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold">${user.username.charAt(0).toUpperCase()}</span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${escapeHtml(user.username)}</div>
                                <div class="text-sm text-gray-500">ID: #${user.user_id.toString().padStart(3, '0')}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${escapeHtml(user.email)}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getRoleColorClass(user.role)}">
                            ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${user.status === 'Online' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                            <div class="w-2 h-2 ${user.status === 'Online' ? 'bg-green-400' : 'bg-gray-400'} rounded-full mr-1"></div>
                            ${user.status}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${user.last_login && user.last_login !== 'Never' ? formatDate(user.last_login) : 'Belum pernah login'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${formatDate(user.created_at)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="editUser(${user.user_id})" class="text-green-600 hover:text-green-900 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteUser(${user.user_id})" class="text-red-600 hover:text-red-900" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updatePagination(pagination) {
            totalPages = pagination.total_pages;
            document.getElementById('showingStart').textContent = pagination.showing_start;
            document.getElementById('showingEnd').textContent = pagination.showing_end;
            document.getElementById('totalUsers').textContent = pagination.total_users;
            
            const container = document.getElementById('paginationContainer');
            container.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            if (currentPage > 1) {
                const prevBtn = createPaginationButton(currentPage - 1, '‹ Sebelumnya');
                container.appendChild(prevBtn);
            }
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const btn = createPaginationButton(i, i.toString(), i === currentPage);
                container.appendChild(btn);
            }
            
            // Next button
            if (currentPage < totalPages) {
                const nextBtn = createPaginationButton(currentPage + 1, 'Selanjutnya ›');
                container.appendChild(nextBtn);
            }
        }

        function createPaginationButton(page, text, isActive = false) {
            const button = document.createElement('button');
            button.textContent = text;
            button.className = `px-3 py-1 text-sm border rounded ${isActive ? 'bg-pink-500 text-white border-pink-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'}`;
            button.onclick = () => loadUsers(page);
            return button;
        }

        // Activity Log Modal Functions
        function showActivityLogModal() {
            document.getElementById('activityLogModal').classList.remove('hidden');
            loadActivityLog();
        }

        function closeActivityLogModal() {
            document.getElementById('activityLogModal').classList.add('hidden');
        }

        function loadActivityLog() {
            const content = document.getElementById('activityLogContent');
            content.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                    <p class="text-gray-500 mt-2">Memuat data...</p>
                </div>
            `;
            
            const search = document.getElementById('activitySearch').value;
                      const filter = document.getElementById('activityFilter').value;
            
            const params = new URLSearchParams({
                ajax: '1',
                action: 'get_activity_log',
                search: search,
                filter: filter,
                limit: 100
            });
            
            fetch(`../../assets/php/user_actions.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActivityLog(data.activities);
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                                <p class="text-red-500 mt-2">${data.message || 'Gagal memuat log aktivitas'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-2xl text-red-400"></i>
                            <p class="text-red-500 mt-2">Terjadi kesalahan saat memuat data</p>
                        </div>
                    `;
                });
        }

        function displayActivityLog(activities) {
            const content = document.getElementById('activityLogContent');
            
            if (activities.length === 0) {
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-300"></i>
                        <p class="text-gray-500 mt-2">Tidak ada aktivitas ditemukan</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600">Waktu</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600">Pengguna</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600">Aktivitas</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600">IP Address</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600">Device</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
            `;
            
            activities.forEach(activity => {
                const activityIcon = activity.activity_type === 'login' ? 
                    '<i class="fas fa-sign-in-alt text-green-500"></i>' : 
                    '<i class="fas fa-sign-out-alt text-red-500"></i>';
                
                const activityColor = activity.activity_type === 'login' ? 
                    'bg-green-100 text-green-800' : 
                    'bg-red-100 text-red-800';
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">${formatDateTime(activity.created_at)}</div>
                            <div class="text-xs text-gray-500">${formatTimeAgo(activity.created_at)}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-full flex items-center justify-center">
                                    <span class="text-white text-xs font-semibold">${activity.username ? activity.username.charAt(0).toUpperCase() : 'U'}</span>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">${escapeHtml(activity.username || 'Unknown')}</div>
                                    <div class="text-xs text-gray-500">${escapeHtml(activity.email || '')}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${activityColor}">
                                ${activityIcon}
                                <span class="ml-1">${activity.activity_type.charAt(0).toUpperCase() + activity.activity_type.slice(1)}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            ${activity.ip_address || 'Unknown'}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            ${parseUserAgent(activity.user_agent)}
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            content.innerHTML = html;
        }

        function filterActivityLog() {
            loadActivityLog();
        }

        function exportActivityLog() {
            const search = document.getElementById('activitySearch').value;
            const filter = document.getElementById('activityFilter').value;
            
            const params = new URLSearchParams({
                action: 'export_activity_log',
                search: search,
                filter: filter
            });
            
            window.open(`../../assets/php/user_actions.php?${params}`, '_blank');
        }

        // User Modal Functions
        function showAddUserModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Pengguna Baru';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('password').required = true;
            document.getElementById('userModal').classList.remove('hidden');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        function editUser(userId) {
            document.getElementById('modalTitle').textContent = 'Edit Pengguna';
            document.getElementById('userId').value = userId;
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('password').required = false;
            
            // Load user data
            fetch(`../../assets/php/user_actions.php?ajax=1&action=get_user&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('username').value = data.user.username;
                        document.getElementById('email').value = data.user.email;
                        document.getElementById('role').value = data.user.role;
                        document.getElementById('userModal').classList.remove('hidden');
                    } else {
                        showAlert('error', data.message || 'Gagal memuat data pengguna');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan saat memuat data');
                });
        }

        function handleUserSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const userId = formData.get('userId');
            const action = userId ? 'update_user' : 'add_user';
            formData.append('action', action);
            
            fetch('../../assets/php/user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message || 'Pengguna berhasil disimpan');
                    closeUserModal();
                    loadUsers(currentPage);
                } else {
                    showAlert('error', data.message || 'Gagal menyimpan pengguna');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Terjadi kesalahan saat menyimpan data');
            });
        }

        function deleteUser(userId) {
            if (confirm('Apakah Anda yakin ingin menghapus pengguna ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('userId', userId);
                
                fetch('../../assets/php/user_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Pengguna berhasil dihapus');
                        loadUsers(currentPage);
                    } else {
                        showAlert('error', data.message || 'Gagal menghapus pengguna');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan saat menghapus data');
                });
            }
        }

        // Bulk Actions
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function handleBulkAction() {
            const action = document.getElementById('bulkAction').value;
            if (!action) return;
            
            const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            if (selectedUsers.length === 0) {
                showAlert('warning', 'Pilih minimal satu pengguna');
                return;
            }
            
            if (confirm(`Apakah Anda yakin ingin ${action} ${selectedUsers.length} pengguna?`)) {
                const formData = new FormData();
                formData.append('action', 'bulk_action');
                formData.append('bulk_action', action);
                formData.append('user_ids', JSON.stringify(selectedUsers));
                
                fetch('../../assets/php/user_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Aksi berhasil dilakukan');
                        loadUsers(currentPage);
                        document.getElementById('selectAll').checked = false;
                        document.getElementById('bulkAction').value = '';
                    } else {
                        showAlert('error', data.message || 'Gagal melakukan aksi');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan saat melakukan aksi');
                });
            }
        }

        function exportUsers() {
            const search = document.getElementById('searchInput').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const roleFilter = document.getElementById('roleFilter').value;
            
            const params = new URLSearchParams({
                action: 'export_users',
                search: search,
                status_filter: statusFilter,
                role_filter: roleFilter
            });
            
            window.open(`../../assets/php/user_actions.php?${params}`, '_blank');
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../../assets/php/logout.php';
            }
        }

        // Utility Functions
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getRoleColorClass(role) {
            switch(role) {
                case 'admin': return 'bg-red-100 text-red-800';
                case 'premium': return 'bg-purple-100 text-purple-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }

        function formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatTimeAgo(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) return 'Baru saja';
            if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit lalu`;
            if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam lalu`;
            return `${Math.floor(diffInSeconds / 86400)} hari lalu`;
        }

        function parseUserAgent(userAgent) {
            if (!userAgent) return 'Unknown';
            
            // Simple user agent parsing
            if (userAgent.includes('Chrome')) return 'Chrome';
            if (userAgent.includes('Firefox')) return 'Firefox';
            if (userAgent.includes('Safari')) return 'Safari';
            if (userAgent.includes('Edge')) return 'Edge';
            return 'Other';
        }

        function showAlert(type, message) {
            // Simple alert implementation
            const alertClass = type === 'success' ? 'bg-green-500' : 
                              type === 'warning' ? 'bg-yellow-500' : 'bg-red-500';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 ${alertClass} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
            alertDiv.textContent = message;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html>
