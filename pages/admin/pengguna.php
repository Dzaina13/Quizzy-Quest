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
$stats = $userHandler->getUserStats();
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
                                <input type="text" id="searchUsers" placeholder="Cari pengguna..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            </div>
                            
                            <!-- Status Filter -->
                            <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            
                            <!-- Role Filter -->
                            <select id="roleFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                <option value="">Semua Role</option>
                                <option value="user">User</option>
                                <option value="premium">Premium</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button onclick="exportUsers()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all flex items-center">
                                <i class="fas fa-download mr-2"></i>Export
                            </button>
                            <button onclick="openAddUserModal()" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-6 py-2 rounded-lg hover:opacity-90 transition-all flex items-center">
                                <i class="fas fa-user-plus mr-2"></i>Tambah Pengguna
                            </button>
                        </div>
                    </div>
                </div>

                <!-- User Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Pengguna</p>
                                <p class="text-3xl font-bold text-gray-800" id="totalUsers"><?php echo number_format($stats['total']); ?></p>
                            </div>
                            <div class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end p-3 rounded-full">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-500 text-sm">+8% dari bulan lalu</span>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Pengguna Aktif</p>
                                <p class="text-3xl font-bold text-green-600" id="activeUsers"><?php echo number_format($stats['active']); ?></p>
                            </div>
                            <div class="bg-green-500 p-3 rounded-full">
                                <i class="fas fa-user-check text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-500 text-sm">+12% dari bulan lalu</span>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Admin Users</p>
                                <p class="text-3xl font-bold text-yellow-600" id="premiumUsers"><?php echo number_format($stats['premium']); ?></p>
                            </div>
                            <div class="bg-yellow-500 p-3 rounded-full">
                                <i class="fas fa-crown text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-500 text-sm">+25% dari bulan lalu</span>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Registrasi Hari Ini</p>
                                <p class="text-3xl font-bold text-blue-600" id="todayUsers"><?php echo number_format($stats['today']); ?></p>
                            </div>
                            <div class="bg-blue-500 p-3 rounded-full">
                                <i class="fas fa-user-plus text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-500 text-sm">+15% dari kemarin</span>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">Daftar Pengguna</h3>
                            <div class="flex items-center space-x-2">
                                <button onclick="selectAllUsers()" class="text-sm text-pink-600 hover:text-pink-800">Pilih Semua</button>
                                <span class="text-gray-300">|</span>
                                <button onclick="showBulkActionModal()" class="text-sm text-red-600 hover:text-red-800">Aksi Massal</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktivitas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bergabung</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6" id="paginationContainer">
                        <!-- Pagination will be loaded via AJAX -->
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Tambah Pengguna -->
    <div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Tambah Pengguna Baru</h3>
                    <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="addUserForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                            <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="Masukkan username">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="user@email.com">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="Masukkan password">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password *</label>
                            <input type="password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="Konfirmasi password">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white rounded-md text-sm font-medium hover:opacity-90">
                            Tambah Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Pengguna -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Pengguna</h3>
                    <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="editUserForm" class="space-y-4">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                            <input type="text" name="username" id="editUsername" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" id="editEmail" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" id="editRole" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white rounded-md text-sm font-medium hover:opacity-90">
                            Update Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail Pengguna -->
    <div id="userDetailModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Detail Pengguna</h3>
                    <button onclick="closeUserDetailModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="userDetailContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Kirim Pesan -->
    <div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Kirim Pesan</h3>
                    <button onclick="closeMessageModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="messageForm" class="space-y-4">
                    <input type="hidden" name="user_id" id="messageUserId">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kepada</label>
                        <input type="text" id="messageUserName" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subjek</label>
                        <input type="text" name="subject" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="Masukkan subjek pesan">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pesan</label>
                        <textarea name="message" rows="5" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="Tulis pesan Anda..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeMessageModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white rounded-md text-sm font-medium hover:opacity-90">
                            <i class="fas fa-paper-plane mr-2"></i>Kirim Pesan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Action -->
    <div id="bulkActionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Aksi Massal</h3>
                    <button onclick="closeBulkActionModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-600">Pilih aksi untuk <span id="selectedCount">0</span> pengguna yang dipilih:</p>
                    
                    <div class="space-y-2">
                        <button onclick="bulkAction('activate')" class="w-full text-left px-4 py-3 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200 text-green-700">
                            <i class="fas fa-check-circle mr-3"></i>Aktifkan Pengguna
                        </button>
                        <button onclick="bulkAction('suspend')" class="w-full text-left px-4 py-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg border border-yellow-200 text-yellow-700">
                            <i class="fas fa-ban mr-3"></i>Suspend Pengguna
                        </button>
                        <button onclick="bulkAction('delete')" class="w-full text-left px-4 py-3 bg-red-50 hover:bg-red-100 rounded-lg border border-red-200 text-red-700">
                            <i class="fas fa-trash mr-3"></i>Hapus Pengguna
                        </button>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button onclick="closeBulkActionModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentSearch = '';
        let currentStatusFilter = '';
        let currentRoleFilter = '';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Search functionality
            document.getElementById('searchUsers').addEventListener('input', function(e) {
                currentSearch = e.target.value;
                currentPage = 1;
                loadUsers();
            });

            // Filter functionality
            document.getElementById('statusFilter').addEventListener('change', function(e) {
                currentStatusFilter = e.target.value;
                currentPage = 1;
                loadUsers();
            });

            document.getElementById('roleFilter').addEventListener('change', function(e) {
                currentRoleFilter = e.target.value;
                currentPage = 1;
                loadUsers();
            });

            // Select all functionality
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.user-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelectedCount();
            });

            // Form submissions
            document.getElementById('addUserForm').addEventListener('submit', handleAddUser);
            document.getElementById('editUserForm').addEventListener('submit', handleEditUser);
            document.getElementById('messageForm').addEventListener('submit', handleSendMessage);
        }

        function loadUsers() {
            const params = new URLSearchParams({
                ajax: '1',
                page: currentPage,
                search: currentSearch,
                status_filter: currentStatusFilter,
                role_filter: currentRoleFilter
            });

            fetch(`../../assets/php/user_actions.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUsers(data.users);
                                               displayPagination(data.page, data.total_pages, data.total);
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan saat memuat data');
                });
        }

        function displayUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';

            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">
                            <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                            <p>Tidak ada data pengguna ditemukan</p>
                        </td>
                    </tr>
                `;
                return;
            }

            users.forEach(user => {
                const row = createUserRow(user);
                tbody.appendChild(row);
            });

            // Reset select all checkbox
            document.getElementById('selectAll').checked = false;
            updateSelectedCount();
        }

        function createUserRow(user) {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';
            
            // Ensure all fields have default values based on actual database structure
            const username = user.username || 'Unknown';
            const email = user.email || '';
            const role = user.role || 'participant';
            const createdAt = user.created_at || '';
            const userId = user.user_id || 0;
            
            const initials = getInitials(username);
            const roleColor = getRoleColor(role);
            
            // Since we don't have status in database, simulate it as active
            const status = 'active';
            const statusColor = getStatusColor(status);
            
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" class="user-checkbox rounded border-gray-300 text-pink-600 focus:ring-pink-500" value="${userId}" onchange="updateSelectedCount()">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold">${initials}</span>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${escapeHtml(username)}</div>
                            <div class="text-sm text-gray-500">ID: #${userId.toString().padStart(3, '0')}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${escapeHtml(email)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-${roleColor}-100 text-${roleColor}-800">
                        ${capitalizeFirst(role)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-${statusColor}-100 text-${statusColor}-800">
                        ${getStatusLabel(status)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="text-xs">
                        <div>Login terakhir: Belum diketahui</div>
                        <div>Aktivitas: Normal</div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${formatDate(createdAt)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <button onclick="viewUser(${userId})" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editUser(${userId})" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="sendMessage(${userId})" class="text-green-600 hover:text-green-900" title="Kirim Pesan">
                            <i class="fas fa-envelope"></i>
                        </button>
                        <button onclick="deleteUser(${userId})" class="text-red-600 hover:text-red-900" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            
            return row;
        }

        function displayPagination(page, totalPages, total) {
            const container = document.getElementById('paginationContainer');
            const start = (page - 1) * 10 + 1;
            const end = Math.min(page * 10, total);
            
            container.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button onclick="changePage(${page - 1})" ${page <= 1 ? 'disabled' : ''} 
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 ${page <= 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                            Previous
                        </button>
                        <button onclick="changePage(${page + 1})" ${page >= totalPages ? 'disabled' : ''} 
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 ${page >= totalPages ? 'opacity-50 cursor-not-allowed' : ''}">
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Menampilkan <span class="font-medium">${start}</span> sampai <span class="font-medium">${end}</span> dari <span class="font-medium">${total}</span> pengguna
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <button onclick="changePage(${page - 1})" ${page <= 1 ? 'disabled' : ''} 
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${page <= 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                ${generatePageNumbers(page, totalPages)}
                                <button onclick="changePage(${page + 1})" ${page >= totalPages ? 'disabled' : ''} 
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${page >= totalPages ? 'opacity-50 cursor-not-allowed' : ''}">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            `;
        }

        function generatePageNumbers(currentPage, totalPages) {
            let pages = '';
            const maxVisible = 5;
            let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(totalPages, start + maxVisible - 1);
            
            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }
            
            for (let i = start; i <= end; i++) {
                const isActive = i === currentPage;
                pages += `
                    <button onclick="changePage(${i})" 
                            class="${isActive ? 'bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end border-pink-500 text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        ${i}
                    </button>
                `;
            }
            
            return pages;
        }

        function changePage(page) {
            if (page < 1) return;
            currentPage = page;
            loadUsers();
        }

        // Modal Functions
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.add('hidden');
            document.getElementById('addUserForm').reset();
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
            document.getElementById('editUserForm').reset();
        }

        function closeUserDetailModal() {
            document.getElementById('userDetailModal').classList.add('hidden');
        }

        function closeMessageModal() {
            document.getElementById('messageModal').classList.add('hidden');
            document.getElementById('messageForm').reset();
        }

        function closeBulkActionModal() {
            document.getElementById('bulkActionModal').classList.add('hidden');
        }

        // User Actions
        function viewUser(userId) {
            const formData = new FormData();
            formData.append('action', 'get_user');
            formData.append('user_id', userId);

            fetch('../../assets/php/user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUserDetail(data.user);
                    document.getElementById('userDetailModal').classList.remove('hidden');
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Terjadi kesalahan saat memuat detail pengguna');
            });
        }

        function displayUserDetail(user) {
            const initials = getInitials(user.username);
            const roleColor = getRoleColor(user.role);
            
            document.getElementById('userDetailContent').innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- User Info -->
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <div class="text-center">
                                <div class="w-20 h-20 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-full flex items-center justify-center mx-auto mb-4">
                                    <span class="text-white font-bold text-2xl">${initials}</span>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900">${escapeHtml(user.username)}</h4>
                                <p class="text-gray-500 text-sm">ID: #${user.user_id.toString().padStart(3, '0')}</p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${roleColor}-100 text-${roleColor}-800 mt-2">
                                    ${capitalizeFirst(user.role)}
                                </span>
                            </div>
                            
                            <div class="mt-6 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Email:</span>
                                    <span class="text-gray-900">${escapeHtml(user.email)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Status:</span>
                                    <span class="text-green-600">Aktif</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Bergabung:</span>
                                    <span class="text-gray-900">${formatDate(user.created_at)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats & Activity -->
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-question-circle text-blue-500 text-2xl mr-3"></i>
                                    <div>
                                        <p class="text-blue-600 text-sm">Quiz Dibuat</p>
                                        <p class="text-2xl font-bold text-blue-700">0</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-trophy text-green-500 text-2xl mr-3"></i>
                                    <div>
                                        <p class="text-green-600 text-sm">Quiz Dimainkan</p>
                                        <p class="text-2xl font-bold text-green-700">0</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-star text-yellow-500 text-2xl mr-3"></i>
                                    <div>
                                        <p class="text-yellow-600 text-sm">Rata-rata Skor</p>
                                        <p class="text-2xl font-bold text-yellow-700">0%</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-purple-500 text-2xl mr-3"></i>
                                    <div>
                                        <p class="text-purple-600 text-sm">Login Terakhir</p>
                                        <p class="text-sm font-bold text-purple-700">Belum diketahui</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h5 class="font-semibold text-gray-900 mb-4">Aktivitas Terbaru</h5>
                            <div class="space-y-3">
                                <p class="text-gray-500 text-sm">Belum ada aktivitas yang tercatat</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                    <button onclick="sendMessage(${user.user_id})" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                        <i class="fas fa-envelope mr-2"></i>Kirim Pesan
                    </button>
                    <button onclick="editUser(${user.user_id})" class="px-4 py-2 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white rounded-md text-sm font-medium hover:opacity-90">
                        <i class="fas fa-edit mr-2"></i>Edit Pengguna
                    </button>
                </div>
            `;
        }

        function editUser(userId) {
            const formData = new FormData();
            formData.append('action', 'get_user');
            formData.append('user_id', userId);

            fetch('../../assets/php/user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('editUserId').value = user.user_id;
                    document.getElementById('editUsername').value = user.username;
                    document.getElementById('editEmail').value = user.email;
                    document.getElementById('editRole').value = user.role;
                    
                    // Close detail modal if open
                    closeUserDetailModal();
                    document.getElementById('editUserModal').classList.remove('hidden');
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Terjadi kesalahan saat memuat data pengguna');
            });
        }

        function sendMessage(userId) {
            const formData = new FormData();
            formData.append('action', 'get_user');
            formData.append('user_id', userId);

            fetch('../../assets/php/user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('messageUserId').value = user.user_id;
                    document.getElementById('messageUserName').value = `${user.username} (${user.email})`;
                    
                    // Close detail modal if open
                    closeUserDetailModal();
                    document.getElementById('messageModal').classList.remove('hidden');
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Terjadi kesalahan saat memuat data pengguna');
            });
        }

        function deleteUser(userId) {
            if (confirm('Apakah Anda yakin ingin menghapus pengguna ini? Aksi ini tidak dapat dibatalkan!')) {
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);

                fetch('../../assets/php/user_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        loadUsers();
                        closeUserDetailModal();
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan saat menghapus pengguna');
                });
            }
        }

        // Form Handlers
        function handleAddUser(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            // Validate password confirmation
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            if (password !== confirmPassword) {
                showAlert('error', 'Konfirmasi password tidak cocok');
                return;
            }
            
            formData.append('action', 'create_user');

            fetch('../../assets/php/user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    closeAddUserModal();
                    loadUsers();
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Terjadi kesalahan saat membuat pengguna');
            });
        }

        function handleEditUser(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'update_user');

            fetch('../../assets/php/user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    closeEditUserModal();
                    loadUsers();
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Terjadi kesalahan saat mengupdate pengguna');
            });
        }

        function handleSendMessage(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'send_message');

            fetch('../../assets/php/user_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    closeMessageModal();
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Terjadi kesalahan saat mengirim pesan');
            });
        }

        // Bulk Actions
        function selectAllUsers() {
            const selectAllCheckbox = document.getElementById('selectAll');
            selectAllCheckbox.checked = !selectAllCheckbox.checked;
            
            const userCheckboxes = document.querySelectorAll('.user-checkbox');
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
            const count = selectedCheckboxes.length;
            
            const countElement = document.getElementById('selectedCount');
            if (countElement) {
                countElement.textContent = count;
            }
        }

        function showBulkActionModal() {
            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                showAlert('warning', 'Pilih minimal satu pengguna untuk melakukan aksi massal.');
                return;
            }
            
            updateSelectedCount();
            document.getElementById('bulkActionModal').classList.remove('hidden');
        }

        function bulkAction(action) {
            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
            const userIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            if (userIds.length === 0) {
                showAlert('warning', 'Pilih minimal satu pengguna.');
                return;
            }
            
            let confirmMessage = '';
            switch(action) {
                case 'activate':
                    confirmMessage = `Aktifkan ${userIds.length} pengguna yang dipilih?`;
                    break;
                case 'suspend':
                    confirmMessage = `Suspend ${userIds.length} pengguna yang dipilih?`;
                    break;
                case 'delete':
                    confirmMessage = `Hapus ${userIds.length} pengguna yang dipilih? Aksi ini tidak dapat dibatalkan!`;
                    break;
                default:
                    return;
            }
            
            if (confirm(confirmMessage)) {
                const formData = new FormData();
                formData.append('action', 'bulk_action');
                formData.append('bulk_action', action);
                userIds.forEach(id => formData.append('user_ids[]', id));

                fetch('../../assets/php/user_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        closeBulkActionModal();
                        loadUsers();
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Terjadi kesalahan saat melakukan aksi massal');
                });
            }
        }

        // Export Function
        function exportUsers() {
            showAlert('info', 'Fitur export sedang dalam pengembangan');
        }

        // Utility Functions
        function getInitials(name) {
            if (!name) return 'U';
            return name.split(' ').map(word => word.charAt(0).toUpperCase()).join('').substring(0, 2);
        }

        function getRoleColor(role) {
            const colors = {
                'participant': 'blue',
                'admin': 'purple'
            };
            return colors[role] || 'gray';
        }

        function getStatusColor(status) {
            const colors = {
                'active': 'green',
                'inactive': 'gray',
                'suspended': 'red'
            };
            return colors[status] || 'green';
        }

        function getStatusLabel(status) {
            const labels = {
                'active': 'Aktif',
                'inactive': 'Nonaktif',
                'suspended': 'Suspended'
            };
            return labels[status] || 'Aktif';
        }

        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function formatDate(dateString) {
            if (!dateString) return 'Tidak diketahui';
            const date = new Date(dateString);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showAlert(type, message) {
            // Create alert element
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md transition-all duration-300 transform translate-x-full`;
            
            let bgColor, textColor, icon;
            switch(type) {
                case 'success':
                    bgColor = 'bg-green-500';
                    textColor = 'text-white';
                    icon = 'fa-check-circle';
                    break;
                case 'error':
                    bgColor = 'bg-red-500';
                    textColor = 'text-white';
                    icon = 'fa-exclamation-circle';
                    break;
                case 'warning':
                    bgColor = 'bg-yellow-500';
                    textColor = 'text-white';
                    icon = 'fa-exclamation-triangle';
                    break;
                case 'info':
                    bgColor = 'bg-blue-500';
                    textColor = 'text-white';
                    icon = 'fa-info-circle';
                    break;
                default:
                    bgColor = 'bg-gray-500';
                    textColor = 'text-white';
                    icon = 'fa-info-circle';
            }
            
            alertDiv.className += ` ${bgColor} ${textColor}`;
            alertDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-3"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Animate in
            setTimeout(() => {
                alertDiv.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.classList.add('translate-x-full');
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 300);
            }, 5000);
        }

        function logout() {
            if (confirm('Apakah Anda yakin ingin logout?')) {
                window.location.href = '../../assets/php/logout.php';
            }
        }

        // Close modals when clicking outside
               // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            const modals = [
                'addUserModal', 
                'editUserModal', 
                'userDetailModal', 
                'messageModal', 
                'bulkActionModal'
            ];
            
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close modals
            if (e.key === 'Escape') {
                const modals = [
                    'addUserModal', 
                    'editUserModal', 
                    'userDetailModal', 
                    'messageModal', 
                    'bulkActionModal'
                ];
                
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (!modal.classList.contains('hidden')) {
                        modal.classList.add('hidden');
                    }
                });
            }
            
            // Ctrl+N to add new user
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                openAddUserModal();
            }
        });
    </script>
</body>
</html>
