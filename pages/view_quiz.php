<?php
session_start();
require_once '../assets/php/koneksi_db.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Get filter parameters
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$view_mode = $_GET['view'] ?? 'grid';

// Build query based on category
$where_conditions = [];
$search_param = '';

if ($category !== 'all') {
    if ($category === 'quiz') {
        $where_conditions[] = "q.quiz_type = 'normal'";
    } elseif ($category === 'rof') {
        $where_conditions[] = "q.quiz_type = 'rof'";
    } elseif ($category === 'decision_maker') {
        $where_conditions[] = "q.quiz_type = 'decision_maker'";
    }
}

if (!empty($search)) {
    $search_param = mysqli_real_escape_string($koneksi, $search);
    $where_conditions[] = "(q.title LIKE '%$search_param%' OR q.description LIKE '%$search_param%')";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Main query to get quizzes with stats
$query = "
    SELECT 
        q.quiz_id,
        q.title,
        q.description,
        q.quiz_type,
        q.created_at,
        u.username as creator,
        COUNT(DISTINCT qs.session_id) as total_sessions,
        COUNT(DISTINCT p.participant_id) as total_participants,
        COUNT(DISTINCT questions.question_id) as total_questions
    FROM quizzes q
    LEFT JOIN users u ON q.created_by = u.user_id
    LEFT JOIN quiz_sessions qs ON q.quiz_id = qs.quiz_id
    LEFT JOIN participants p ON qs.session_id = p.session_id
    LEFT JOIN questions ON q.quiz_id = questions.quiz_id
    $where_clause
    GROUP BY q.quiz_id, q.title, q.description, q.quiz_type, q.created_at, u.username
    ORDER BY q.created_at DESC
";

$result = mysqli_query($koneksi, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($koneksi));
}

$quizzes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $quizzes[] = $row;
}

// Get category counts
$count_query = "
    SELECT 
        quiz_type,
        COUNT(*) as count
    FROM quizzes 
    GROUP BY quiz_type
";
$count_result = mysqli_query($koneksi, $count_query);
if (!$count_result) {
    die("Count query failed: " . mysqli_error($koneksi));
}

$counts = [];
while ($row = mysqli_fetch_assoc($count_result)) {
    $counts[$row['quiz_type']] = $row['count'];
}

$total_count = array_sum($counts);
$normal_count = $counts['normal'] ?? 0;
$rof_count = $counts['rof'] ?? 0;
$decision_count = $counts['decision_maker'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management - Quiz System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
            <div class="px-6 py-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="mb-6 lg:mb-0">
                        <div class="flex items-center mb-2">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-3 rounded-lg mr-4">
                                <i class="fas fa-brain text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Quiz Management</h1>
                                <p class="text-gray-600 mt-1">Kelola dan jelajahi semua quiz Anda</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <?php if ($user_role === 'admin'): ?>
                            <a href="create_quiz.php" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Buat Quiz
                            </a>
                        <?php endif; ?>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Selamat datang,</p>
                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($_SESSION['username']) ?></p>
                            </div>
                            <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
            <div class="p-6">
                <!-- Category Tabs -->
                <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
                    <a href="?category=all&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
                       class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <i class="fas fa-th-list mr-2"></i>
                        <span>Semua</span>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'all' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $total_count ?></span>
                    </a>
                    <a href="?category=quiz&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
                       class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'quiz' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <i class="fas fa-brain mr-2"></i>
                        <span>Quiz</span>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'quiz' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $normal_count ?></span>
                    </a>
                    <a href="?category=rof&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
                       class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'rof' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <i class="fas fa-target mr-2"></i>
                        <span>RoF</span>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'rof' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $rof_count ?></span>
                    </a>
                    <a href="?category=decision_maker&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
                       class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'decision_maker' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <i class="fas fa-users-cog mr-2"></i>
                        <span>Decision Maker</span>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'decision_maker' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $decision_count ?></span>
                    </a>
                </div>

                <!-- Search & View Controls -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <form method="GET" class="flex-1 max-w-md">
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" name="search" placeholder="Cari quiz..." 
                                   value="<?= htmlspecialchars($search) ?>"
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                        </div>
                    </form>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 mr-2">Tampilan:</span>
                        <a href="?category=<?= $category ?>&search=<?= urlencode($search) ?>&view=grid" 
                           class="p-2 rounded-lg transition-colors duration-200 <?= $view_mode === 'grid' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                            <i class="fas fa-th"></i>
                        </a>
                        <a href="?category=<?= $category ?>&search=<?= urlencode($search) ?>&view=list" 
                           class="p-2 rounded-lg transition-colors duration-200 <?= $view_mode === 'list' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                            <i class="fas fa-list"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Content -->
        <?php if (empty($quizzes)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="text-center py-16">
                    <div class="bg-gray-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Tidak ada quiz ditemukan</h3>
                    <p class="text-gray-600 mb-6">
                        <?php if (!empty($search)): ?>
                            Tidak ada quiz yang sesuai dengan kriteria pencarian Anda.
                        <?php else: ?>
                            <?php if ($user_role === 'admin'): ?>
                                Mulai dengan membuat quiz pertama Anda!
                            <?php else: ?>
                                Belum ada quiz yang tersedia saat ini.
                            <?php endif; ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($user_role === 'admin' && empty($search)): ?>
                        <a href="create_quiz.php" class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Buat Quiz Pertama
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="<?= $view_mode === 'list' ? 'space-y-4' : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' ?>">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200 <?= $view_mode === 'list' ? 'flex' : '' ?>">
                        <!-- Quiz Type Badge -->
                        <div class="<?= $view_mode === 'list' ? 'w-2 bg-gradient-to-b' : 'h-2 bg-gradient-to-r' ?> 
                            <?php
                            $gradients = [
                                'normal' => 'from-blue-500 to-blue-600',
                                'rof' => 'from-green-500 to-green-600',
                                'decision_maker' => 'from-purple-500 to-purple-600'
                            ];
                            echo $gradients[$quiz['quiz_type']] ?? 'from-gray-500 to-gray-600';
                            ?>
                            <?= $view_mode === 'list' ? 'rounded-l-xl' : 'rounded-t-xl' ?>">
                        </div>

                        <div class="p-6 flex-1">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <?php
                                    $icons = [
                                        'normal' => 'fas fa-brain',
                                        'rof' => 'fas fa-target', 
                                        'decision_maker' => 'fas fa-users-cog'
                                    ];
                                    $labels = [
                                        'normal' => 'Quiz',
                                        'rof' => 'RoF',
                                        'decision_maker' => 'Decision Maker'
                                    ];
                                    $colors = [
                                        'normal' => 'text-blue-600 bg-blue-50',
                                        'rof' => 'text-green-600 bg-green-50',
                                        'decision_maker' => 'text-purple-600 bg-purple-50'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $colors[$quiz['quiz_type']] ?>">
                                        <i class="<?= $icons[$quiz['quiz_type']] ?> mr-1"></i>
                                        <?= $labels[$quiz['quiz_type']] ?>
                                    </span>
                                </div>
                                <div class="relative">
                                    <button onclick="toggleDropdown(<?= $quiz['quiz_id'] ?>)" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="dropdown-<?= $quiz['quiz_id'] ?>" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10 hidden">
                                        <div class="py-1">
                                            <a href="quiz-detail.php?id=<?= $quiz['quiz_id'] ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                <i class="fas fa-eye mr-3"></i>
                                                Lihat Detail
                                            </a>
                                            <?php if ($user_role === 'admin'): ?>
                                                <a href="edit-quiz.php?id=<?= $quiz['quiz_id'] ?>" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                    <i class="fas fa-edit mr-3"></i>
                                                    Edit
                                                </a>
                                                <a href="#" onclick="deleteQuiz(<?= $quiz['quiz_id'] ?>)" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                    <i class="fas fa-trash mr-3"></i>
                                                    Hapus
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= htmlspecialchars($quiz['title']) ?></h3>
                                <p class="text-gray-600 text-sm line-clamp-2">
                                    <?= htmlspecialchars(substr($quiz['description'] ?? 'Tidak ada deskripsi tersedia', 0, 100)) ?>
                                    <?= strlen($quiz['description'] ?? '') > 100 ? '...' : '' ?>
                                </p>
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div class="text-center">
                                    <div class="bg-blue-50 p-2 rounded-lg mb-1">
                                        <i class="fas fa-question-circle text-blue-600"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900"><?= $quiz['total_questions'] ?></p>
                                    <p class="text-xs text-gray-600">Pertanyaan</p>
                                </div>
                                <div class="text-center">
                                    <div class="bg-green-50 p-2 rounded-lg mb-1">
                                        <i class="fas fa-users text-green-600"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900"><?= $quiz['total_participants'] ?></p>
                                    <p class="text-xs text-gray-600">Peserta</p>
                                </div>
                                <div class="text-center">
                                    <div class="bg-purple-50 p-2 rounded-lg mb-1">
                                        <i class="fas fa-play-circle text-purple-600"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900"><?= $quiz['total_sessions'] ?></p>
                                    <p class="text-xs text-gray-600">Sesi</p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-user mr-2"></i>
                                        <span><?= htmlspecialchars($quiz['creator']) ?></span>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-calendar mr-2"></i>
                                        <span><?= date('d M Y', strtotime($quiz['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="quiz-detail.php?id=<?= $quiz['quiz_id'] ?>" class="flex-1 bg-gray-100 text-gray-700 text-center py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200 text-sm font-medium">
                                        Lihat Quiz
                                    </a>
                                    <?php if ($user_role === 'admin'): ?>
                                        <a href="create-session.php?quiz_id=<?= $quiz['quiz_id'] ?>" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white text-center py-2 px-4 rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-200 text-sm font-medium">
                                            Mulai Sesi
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Dropdown toggle
        function toggleDropdown(quizId) {
            const dropdown = document.getElementById(`dropdown-${quizId}`);
            const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
            
            // Close all other dropdowns
            allDropdowns.forEach(d => {
                if (d !== dropdown) {
                    d.classList.add('hidden');
                }
            });
            
            dropdown.classList.toggle('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('button[onclick*="toggleDropdown"]')) {
                document.querySelectorAll('[id^="dropdown-"]').forEach(d => {
                    d.classList.add('hidden');
                });
            }
        });

        // Delete quiz function
        function deleteQuiz(quizId) {
            if (confirm('Apakah Anda yakin ingin menghapus quiz ini? Tindakan ini tidak dapat dibatalkan.')) {
                fetch('../delete_quiz.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ quiz_id: quizId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error menghapus quiz: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus quiz.');
                });
            }
        }

        // Auto-submit search form on input
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>