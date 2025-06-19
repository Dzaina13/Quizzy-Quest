<?php
require_once '../../assets/php/koneksi_db.php';
require_once '../../assets/php/session_check.php';
require_once '../../assets/php/quiz_handler.php';

// Cek login
checkUserLogin();

$userInfo = getUserInfo();
$quizHandler = new QuizHandler($koneksi);

// Parameter untuk pagination dan filter
$page = intval($_GET['page'] ?? 1);
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';

// Ambil data quiz
$quizzes = $quizHandler->getQuizzes($page, 10, $search, $type_filter);
$total_quizzes = $quizHandler->getTotalQuizzes($search, $type_filter);
$quiz_stats = $quizHandler->getQuizStats();

// Pagination
$total_pages = ceil($total_quizzes / 10);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Quiz - Quizzy Quest Admin</title>
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
                    <a href="quis.php" class="flex items-center space-x-3 p-3 rounded-lg bg-white bg-opacity-20 hover:bg-opacity-30 transition-all">
                        <i class="fas fa-question-circle"></i>
                        <span>Kelola Quiz</span>
                    </a>
                    <a href="pengguna.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
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
                        <h2 class="text-2xl font-bold text-gray-800">Kelola Quiz</h2>
                        <p class="text-gray-600 mt-1">Buat, edit, dan kelola semua quiz Anda</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <i class="fas fa-bell text-gray-500 text-xl"></i>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                        </div>
                        <a href="../../assets/php/logout.php" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-4 py-2 rounded-lg hover:opacity-90 transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </header>

            <!-- Quiz Management Content -->
            <main class="p-6">
                <!-- Action Bar -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex flex-col md:flex-row gap-4 flex-1">
                            <!-- Search -->
                            <div class="relative flex-1 max-w-md">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari quiz..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            </div>
                            
                            <!-- Filter -->
                            <select id="typeFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                <option value="">Semua Tipe</option>
                                <option value="normal" <?php echo $type_filter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                <option value="rof" <?php echo $type_filter === 'rof' ? 'selected' : ''; ?>>Right or False</option>
                                <option value="decision_maker" <?php echo $type_filter === 'decision_maker' ? 'selected' : ''; ?>>Decision Maker</option>
                            </select>
                        </div>
                        
                        <!-- Create Quiz Button -->
                        <button onclick="openModal()" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-6 py-2 rounded-lg hover:opacity-90 transition-all flex items-center">
                            <i class="fas fa-plus mr-2"></i>Buat Quiz Baru
                        </button>
                    </div>
                </div>

                <!-- Quiz Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Quiz</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($quiz_stats['total']); ?></p>
                            </div>
                            <i class="fas fa-question-circle text-blue-500 text-2xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Quiz Aktif</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo number_format($quiz_stats['active']); ?></p>
                            </div>
                            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Draft</p>
                                <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($quiz_stats['draft']); ?></p>
                            </div>
                            <i class="fas fa-edit text-yellow-500 text-2xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Nonaktif</p>
                                <p class="text-2xl font-bold text-red-600"><?php echo number_format($quiz_stats['inactive']); ?></p>
                            </div>
                            <i class="fas fa-times-circle text-red-500 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Quiz Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Daftar Quiz</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dimainkan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($quizzes as $quiz): ?>
                                <tr class="hover:bg-gray-50" data-quiz-id="<?php echo $quiz['quiz_id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-lg flex items-center justify-center">
                                                <i class="fas <?php echo $quizHandler->getQuizIcon($quiz['quiz_type']); ?> text-white"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($quiz['title']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($quiz['description'] ?? '', 0, 50)); ?><?php echo strlen($quiz['description'] ?? '') > 50 ? '...' : ''; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $quizHandler->getQuizTypeColor($quiz['quiz_type']); ?>-100 text-<?php echo $quizHandler->getQuizTypeColor($quiz['quiz_type']); ?>-800">
                                            <?php 
                                            $type_labels = [
                                                'normal' => 'Normal',
                                                'rof' => 'Right or False',
                                                'decision_maker' => 'Decision Maker'
                                            ];
                                            echo $type_labels[$quiz['quiz_type']] ?? ucfirst($quiz['quiz_type']);
                                            ?>
                                        </span>
                                    </td>
                                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $quiz['question_count']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo number_format($quiz['play_count']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($quiz['question_count'] > 0): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $quizHandler->formatDate($quiz['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewQuiz(<?php echo $quiz['quiz_id']; ?>)" class="text-blue-600 hover:text-blue-900" title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editQuiz(<?php echo $quiz['quiz_id']; ?>)" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="duplicateQuiz(<?php echo $quiz['quiz_id']; ?>)" class="text-green-600 hover:text-green-900" title="Duplikat">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button onclick="deleteQuiz(<?php echo $quiz['quiz_id']; ?>)" class="text-red-600 hover:text-red-900" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($quizzes)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-question-circle text-4xl text-gray-300 mb-2"></i>
                                            <p>Tidak ada quiz ditemukan</p>
                                            <p class="text-sm">Buat quiz pertama Anda dengan mengklik tombol "Buat Quiz Baru"</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>
                                <?php endif; ?>
                                <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Menampilkan <span class="font-medium"><?php echo (($page - 1) * 10) + 1; ?></span> sampai <span class="font-medium"><?php echo min($page * 10, $total_quizzes); ?></span> dari <span class="font-medium"><?php echo number_format($total_quizzes); ?></span> hasil
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start = max(1, $page - 2);
                                        $end = min($total_pages, $page + 2);
                                        
                                        for ($i = $start; $i <= $end; $i++):
                                        ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>" class="<?php echo $i == $page ? 'bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end border-pink-500 text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                            <?php echo $i; ?>
                                        </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type_filter=<?php echo urlencode($type_filter); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal untuk Buat Quiz Baru -->
    <div id="createQuizModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Buat Quiz Baru</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="createQuizForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Judul Quiz *</label>
                        <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="Masukkan judul quiz">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500" placeholder="Deskripsi singkat tentang quiz"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Quiz *</label>
                        <select name="quiz_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            <option value="">Pilih Tipe Quiz</option>
                            <option value="normal">Normal (Multiple Choice)</option>
                            <option value="rof">Right or False</option>
                            <option value="decision_maker">Decision Maker</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white rounded-md text-sm font-medium hover:opacity-90">
                            <span class="submit-text">Buat Quiz</span>
                            <i class="fas fa-spinner fa-spin hidden loading-icon"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i class="fas fa-spinner fa-spin text-pink-500 text-2xl mr-3"></i>
                <span class="text-gray-700">Memproses...</span>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal() {
            document.getElementById('createQuizModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('createQuizModal').classList.add('hidden');
            document.getElementById('createQuizForm').reset();
        }
        
        // Loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check' : 'fa-exclamation-triangle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Create quiz form submission
        document.getElementById('createQuizForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_quiz');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingIcon = submitBtn.querySelector('.loading-icon');
            
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            loadingIcon.classList.remove('hidden');
            
            fetch('../../assets/php/quiz_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan saat membuat quiz', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                loadingIcon.classList.add('hidden');
            });
        });
        
        // Search functionality
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const search = e.target.value;
                const typeFilter = document.getElementById('typeFilter').value;
                const url = new URL(window.location);
                url.searchParams.set('search', search);
                url.searchParams.set('type_filter', typeFilter);
                url.searchParams.set('page', '1');
                window.location.href = url.toString();
            }, 500);
        });
        
        // Type filter
        document.getElementById('typeFilter').addEventListener('change', function(e) {
            const search = document.getElementById('searchInput').value;
            const typeFilter = e.target.value;
            const url = new URL(window.location);
            url.searchParams.set('search', search);
            url.searchParams.set('type_filter', typeFilter);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
        
        // Quiz actions
        function viewQuiz(id) {
            window.location.href = `view_quiz.php?id=${id}`;
        }
        
        function editQuiz(id) {
            window.location.href = `edit_quiz.php?id=${id}`;
        }
        
        function duplicateQuiz(id) {
            if (confirm('Apakah Anda yakin ingin menduplikasi quiz ini?')) {
                showLoading();
                
                const formData = new FormData();
                formData.append('action', 'duplicate_quiz');
                formData.append('quiz_id', id);
                
                fetch('../../assets/php/quiz_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan saat menduplikasi quiz', 'error');
                });
            }
        }
        
        function deleteQuiz(id) {
            if (confirm('Apakah Anda yakin ingin menghapus quiz ini? Tindakan ini tidak dapat dibatalkan.')) {
                showLoading();
                
                const formData = new FormData();
                formData.append('action', 'delete_quiz');
                formData.append('quiz_id', id);
                
                fetch('../../assets/php/quiz_actions.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    showNotification('Terjadi kesalahan saat menghapus quiz', 'error');
                });
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('createQuizModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close modal
            if (e.key === 'Escape') {
                closeModal();
            }
            
            // Ctrl+N to create new quiz
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                openModal();
            }
        });
    </script>
</body>
</html>

