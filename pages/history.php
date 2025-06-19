<?php
// File: history.php
require_once '../assets/php/session_check.php';
require_once '../assets/php/history_db.php';

// Inisialisasi
$historyManager = new HistoryManager();
$user_id = $_SESSION['user_id'];

// Ambil parameter filter
$quiz_type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

// Ambil data
$quiz_history = $historyManager->getFilteredHistory($user_id, $quiz_type, $status, $limit);
$user_stats = $historyManager->getUserStats($user_id);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Quiz - Quiz App</title>
    
    <!-- CSS Framework -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .filter-active {
            background: #3b82f6;
            color: white;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center space-x-2">
                        <i class="fas fa-arrow-left text-gray-600"></i>
                        <span class="text-gray-600 hover:text-gray-900">Kembali ke Dashboard</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="assets/php/logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-history mr-3 text-blue-600"></i>
                Riwayat Quiz
            </h1>
            <p class="text-gray-600">Lihat semua quiz yang pernah Anda ikuti</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="stats-card text-white p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm">Total Sesi</p>
                        <p class="text-2xl font-bold"><?php echo $user_stats['total_sessions']; ?></p>
                    </div>
                    <i class="fas fa-list text-2xl text-blue-200"></i>
                </div>
            </div>
            
            <div class="bg-green-500 text-white p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm">Selesai</p>
                        <p class="text-2xl font-bold"><?php echo $user_stats['completed_sessions']; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-2xl text-green-200"></i>
                </div>
            </div>
            
            <div class="bg-yellow-500 text-white p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm">Rata-rata Skor</p>
                        <p class="text-2xl font-bold"><?php echo number_format($user_stats['avg_score'], 1); ?></p>
                    </div>
                    <i class="fas fa-chart-line text-2xl text-yellow-200"></i>
                </div>
            </div>
            
            <div class="bg-purple-500 text-white p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm">Skor Terbaik</p>
                        <p class="text-2xl font-bold"><?php echo number_format($user_stats['best_score'], 0); ?></p>
                    </div>
                    <i class="fas fa-trophy text-2xl text-purple-200"></i>
                </div>
            </div>
            
            <div class="bg-indigo-500 text-white p-6 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-100 text-sm">Sebagai Host</p>
                        <p class="text-2xl font-bold"><?php echo $user_stats['hosted_sessions']; ?></p>
                    </div>
                    <i class="fas fa-crown text-2xl text-indigo-200"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Tipe Quiz:</label>
                    <div class="flex space-x-2">
                        <a href="?type=all&status=<?php echo $status; ?>" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $quiz_type === 'all' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Semua
                        </a>
                        <a href="?type=normal&status=<?php echo $status; ?>" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $quiz_type === 'normal' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Normal
                        </a>
                        <a href="?type=rof&status=<?php echo $status; ?>" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $quiz_type === 'rof' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            ROF
                        </a>
                        <a href="?type=decision_maker&status=<?php echo $status; ?>" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $quiz_type === 'decision_maker' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Decision Maker
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Status:</label>
                    <div class="flex space-x-2">
                        <a href="?type=<?php echo $quiz_type; ?>&status=all" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $status === 'all' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Semua
                        </a>
                        <a href="?type=<?php echo $quiz_type; ?>&status=completed" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $status === 'completed' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Selesai
                        </a>
                        <a href="?type=<?php echo $quiz_type; ?>&status=active" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $status === 'active' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Aktif
                        </a>
                        <a href="?type=<?php echo $quiz_type; ?>&status=waiting" 
                           class="px-3 py-1 rounded-full text-sm <?php echo $status === 'waiting' ? 'filter-active' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                            Menunggu
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz History List -->
        <div class="space-y-4">
            <?php if (empty($quiz_history)): ?>
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Belum Ada Riwayat</h3>
                    <p class="text-gray-500 mb-6">Anda belum pernah mengikuti quiz apapun</p>
                    <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Mulai Quiz Pertama
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($quiz_history as $index => $quiz): ?>
                    <div class="bg-white rounded-lg shadow-sm card-hover animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <i class="<?php echo HistoryManager::getQuizTypeIcon($quiz['quiz_type']); ?> text-lg text-blue-600"></i>
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($quiz['session_name']); ?>
                                        </h3>
                                        
                                        <!-- Quiz Type Badge -->
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo HistoryManager::getQuizTypeBadge($quiz['quiz_type']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $quiz['quiz_type'])); ?>
                                        </span>
                                        
                                        <!-- Status Badge -->
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo HistoryManager::getStatusBadge($quiz['session_status']); ?>">
                                            <?php 
                                            $status_text = [
                                                'completed' => 'Selesai',
                                                'active' => 'Aktif',
                                                'waiting' => 'Menunggu'
                                            ];
                                            echo $status_text[$quiz['session_status']] ?? 'Unknown';
                                            ?>
                                        </span>
                                        
                                        <?php if ($quiz['is_host']): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-crown mr-1"></i>Host
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="text-gray-600 mb-3">
                                        <strong>Quiz:</strong> <?php echo htmlspecialchars($quiz['quiz_title']); ?>
                                    </p>
                                    
                                    <?php if ($quiz['quiz_description']): ?>
                                        <p class="text-gray-500 text-sm mb-3">
                                            <?php echo htmlspecialchars($quiz['quiz_description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($quiz['final_score'] !== null): ?>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-blue-600">
                                            <?php echo number_format($quiz['final_score'], 0); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo $quiz['correct_answers']; ?>/<?php echo $quiz['total_questions']; ?> benar
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                    <span>
                                        <strong>Bergabung:</strong><br>
                                        <?php echo HistoryManager::formatDate($quiz['join_time']); ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-play mr-2 text-gray-400"></i>
                                    <span>
                                        <strong>Dimulai:</strong><br>
                                        <?php echo HistoryManager::formatDate($quiz['start_time']); ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-stop mr-2 text-gray-400"></i>
                                    <span>
                                        <strong>Selesai:</strong><br>
                                        <?php echo HistoryManager::formatDate($quiz['end_time']); ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>
                                    <span>
                                        <strong>Dibuat oleh:</strong><br>
                                        <?php echo htmlspecialchars($quiz['creator_name'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($quiz['room_code']): ?>
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-key text-gray-400"></i>
                                            <span class="text-sm text-gray-600">
                                                Kode Room: <strong><?php echo htmlspecialchars($quiz['room_code']); ?></strong>
                                            </span>
                                        </div>
                                        
                                        <?php if ($quiz['session_status'] === 'active'): ?>
                                            <a href="live_waiting.php?session_id=<?php echo $quiz['session_id']; ?>" 
                                               class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                <i class="fas fa-play mr-1"></i>
                                                Lanjutkan
                                            </a>
                                        <?php elseif ($quiz['session_status'] === 'completed' && $quiz['final_score'] !== null): ?>
                                            <button onclick="showScoreDetail(<?php echo htmlspecialchars(json_encode($quiz)); ?>)" 
                                                    class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                                <i class="fas fa-chart-bar mr-1"></i>
                                                Lihat Detail
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <?php if (count($quiz_history) >= 10 && !$limit): ?>
            <div class="text-center mt-8">
                <a href="?type=<?php echo $quiz_type; ?>&status=<?php echo $status; ?>&limit=50" 
                   class="inline-flex items-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fas fa-plus mr-2"></i>
                    Muat Lebih Banyak
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Score Detail Modal -->
    <div id="scoreModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Detail Skor</h3>
                <button onclick="closeScoreModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="scoreContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function showScoreDetail(quiz) {
            const modal = document.getElementById('scoreModal');
            const content = document.getElementById('scoreContent');
            
            const percentage = quiz.total_questions > 0 ? (quiz.correct_answers / quiz.total_questions * 100).toFixed(1) : 0;
            
            content.innerHTML = `
                <div class="text-center mb-4">
                    <div class="text-3xl font-bold text-blue-600 mb-2">${parseFloat(quiz.final_score).toFixed(0)}</div>
                    <div class="text-gray-600">Skor Akhir</div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Jawaban Benar:</span>
                        <span class="font-semibold">${quiz.correct_answers}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Pertanyaan:</span>
                        <span class="font-semibold">${quiz.total_questions}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Persentase:</span>
                        <span class="font-semibold">${percentage}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tipe Quiz:</span>
                        <span class="font-semibold">${quiz.quiz_type.replace('_', ' ')}</span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button onclick="closeScoreModal()" 
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Tutup
                    </button>
                </div>
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeScoreModal() {
            const modal = document.getElementById('scoreModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.getElementById('scoreModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeScoreModal();
            }
        });
    </script>
</body>
</html>