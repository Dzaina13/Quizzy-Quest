<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$password = "13Juni2**5";
$database = "quizuas";

$koneksi = new mysqli($host, $user, $password, $database);

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];
$page_title = "Dashboard - Quizzy-Quest";

// Get user statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM quizzes WHERE created_by = ?) as quizzes_created,
    (SELECT COUNT(*) FROM quiz_sessions WHERE created_by = ?) as sessions_hosted,
    (SELECT COUNT(*) FROM participants WHERE user_id = ?) as sessions_joined,
    (SELECT AVG(score) FROM points p JOIN participants pt ON p.participant_id = pt.participant_id WHERE pt.user_id = ?) as avg_score";

$stmt = $koneksi->prepare($stats_query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Set default values if null
$stats['quizzes_created'] = $stats['quizzes_created'] ?? 0;
$stats['sessions_hosted'] = $stats['sessions_hosted'] ?? 0;
$stats['sessions_joined'] = $stats['sessions_joined'] ?? 0;
$stats['avg_score'] = $stats['avg_score'] ?? 0;

// Get recent activities
$activities_query = "
    SELECT 'joined' as type, qs.session_name as title, p.join_time as date, q.title as quiz_title
    FROM participants p 
    JOIN quiz_sessions qs ON p.session_id = qs.session_id 
    JOIN quizzes q ON qs.quiz_id = q.quiz_id 
    WHERE p.user_id = ?
    UNION ALL
    SELECT 'created' as type, title, created_at as date, title as quiz_title
    FROM quizzes 
    WHERE created_by = ?
    ORDER BY date DESC 
    LIMIT 5";

$stmt2 = $koneksi->prepare($activities_query);
$stmt2->bind_param("ii", $user_id, $user_id);
$stmt2->execute();
$activities_result = $stmt2->get_result();
$recent_activities = [];
while ($row = $activities_result->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Get active sessions that user can join
$sessions_query = "SELECT qs.*, q.title as quiz_title, q.quiz_type, u.username as creator,
          COUNT(p.participant_id) as participant_count
          FROM quiz_sessions qs
          JOIN quizzes q ON qs.quiz_id = q.quiz_id
          LEFT JOIN users u ON qs.created_by = u.user_id
          LEFT JOIN participants p ON qs.session_id = p.session_id
          WHERE qs.start_time IS NOT NULL AND qs.end_time IS NULL
          GROUP BY qs.session_id
          ORDER BY qs.start_time DESC
          LIMIT 3";

$sessions_result = $koneksi->query($sessions_query);
$active_sessions = [];
if ($sessions_result) {
    while ($row = $sessions_result->fetch_assoc()) {
        $active_sessions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div class="bg-gradient-to-r from-purple-500 to-blue-600 p-2 rounded-lg mr-3">
                            <i class="fas fa-brain text-white text-xl"></i>
                        </div>
                        <a href="dashboard.php" class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                            Quizzy Quest
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-6">
                        <a href="view_quiz.php" class="text-gray-600 hover:text-purple-600 font-medium transition-colors">
                            <i class="fas fa-list mr-2"></i>Quiz
                        </a>
                        <a href="create_quiz.php" class="text-gray-600 hover:text-purple-600 font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Buat Quiz
                        </a>
                        <a href="join_session.php" class="text-gray-600 hover:text-purple-600 font-medium transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i>Join Session
                        </a>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm text-gray-600">Selamat datang,</p>
                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($username); ?></p>
                        </div>
                        <div class="relative">
                            <button onclick="toggleUserMenu()" class="flex items-center space-x-2 bg-gray-100 rounded-full p-2 hover:bg-gray-200 transition-colors">
                                <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-blue-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </div>
                                <i class="fas fa-chevron-down text-gray-600 text-xs"></i>
                            </button>
                            <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                                <div class="py-1">
                                    <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-user mr-3"></i>Profile
                                    </a>
                                    <a href="history.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <i class="fas fa-history mr-3"></i>Riwayat
                                    </a>
                                    <hr class="my-1">
                                    <a href="../assets/php/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt mr-3"></i>Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-purple-500 to-blue-600 rounded-xl shadow-lg p-8 text-white mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Selamat datang kembali, <?php echo htmlspecialchars($username); ?>! 🎉</h1>
                    <p class="text-purple-100 text-lg">Siap untuk membuat quiz yang menakjubkan atau bergabung dalam sesi yang menarik?</p>
                </div>
                <div class="hidden md:block">
                    <i class="fas fa-rocket text-6xl opacity-20"></i>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-clipboard-list text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Quiz Dibuat</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['quizzes_created']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-chalkboard-teacher text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Sesi Dihosting</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['sessions_hosted']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Sesi Diikuti</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['sessions_joined']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-trophy text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Rata-rata Skor</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $stats['avg_score'] > 0 ? number_format($stats['avg_score'], 1) . '%' : 'N/A'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="create_quiz.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-200 group">
                <div class="text-center">
                    <div class="p-4 rounded-full bg-purple-100 text-purple-600 inline-block group-hover:bg-purple-200 transition-colors">
                        <i class="fas fa-plus text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mt-4">Buat Quiz</h3>
                    <p class="text-gray-600 text-sm">Buat quiz Anda sendiri</p>
                </div>
            </a>

            <a href="view_quiz.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-200 group">
                <div class="text-center">
                    <div class="p-4 rounded-full bg-blue-100 text-blue-600 inline-block group-hover:bg-blue-200 transition-colors">
                        <i class="fas fa-list text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mt-4">Lihat Quiz</h3>
                    <p class="text-gray-600 text-sm">Kelola quiz Anda</p>
                </div>
            </a>

            <a href="join_session.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-200 group">
                <div class="text-center">
                    <div class="p-4 rounded-full bg-green-100 text-green-600 inline-block group-hover:bg-green-200 transition-colors">
                        <i class="fas fa-sign-in-alt text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mt-4">Join Session</h3>
                    <p class="text-gray-600 text-sm">Masukkan kode sesi</p>
                </div>
            </a>

            <a href="browse_sessions.php" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all duration-200 group">
                <div class="text-center">
                    <div class="p-4 rounded-full bg-yellow-100 text-yellow-600 inline-block group-hover:bg-yellow-200 transition-colors">
                        <i class="fas fa-search text-3xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mt-4">Jelajahi Sesi</h3>
                    <p class="text-gray-600 text-sm">Temukan sesi aktif</p>
                </div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Activities -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-800">Aktivitas Terbaru</h2>
                            <a href="history.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-12">
                                <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-clock text-gray-400 text-2xl"></i>
                                </div>
                                <p class="text-gray-500 font-medium mb-2">Belum ada aktivitas terbaru</p>
                                <p class="text-sm text-gray-400">Mulai dengan membuat quiz atau bergabung dalam sesi!</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="p-3 rounded-full <?php echo $activity['type'] == 'created' ? 'bg-purple-100 text-purple-600' : 'bg-green-100 text-green-600'; ?>">
                                            <i class="fas <?php echo $activity['type'] == 'created' ? 'fa-plus' : 'fa-play'; ?>"></i>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800">
                                                <?php if ($activity['type'] == 'created'): ?>
                                                    Membuat quiz: <?php echo htmlspecialchars($activity['title']); ?>
                                                <?php else: ?>
                                                    Bergabung dalam sesi: <?php echo htmlspecialchars($activity['title']); ?>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-sm text-gray-500"><?php echo date('d M Y, H:i', strtotime($activity['date'])); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Active Sessions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-800">Sesi Aktif</h2>
                            <a href="browse_sessions.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($active_sessions)): ?>
                            <div class="text-center py-8">
                                <div class="bg-gray-100 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-users text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-gray-500 text-sm">Tidak ada sesi aktif</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($active_sessions as $session): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                                        <h4 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($session['quiz_title']); ?></h4>
                                        <p class="text-sm text-gray-600 mb-3">oleh <?php echo htmlspecialchars($session['creator']); ?></p>
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                                <i class="fas fa-users mr-1"></i><?php echo $session['participant_count']; ?> bergabung
                                            </span>
                                            <a href="join_session.php" class="bg-green-500 text-white px-3 py-1 rounded-lg text-xs hover:bg-green-600 transition-colors">
                                                Bergabung
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800">Tautan Cepat</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <a href="profile.php" class="flex items-center text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all">
                                <i class="fas fa-user mr-3 w-4"></i>
                                <span>Profil Saya</span>
                            </a>
                            <a href="history.php" class="flex items-center text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all">
                                <i class="fas fa-history mr-3 w-4"></i>
                                <span>Riwayat Quiz</span>
                            </a>
                            <a href="view_quiz.php" class="flex items-center text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all">
                                <i class="fas fa-clipboard-list mr-3 w-4"></i>
                                <span>Kelola Quiz</span>
                            </a>
                            <a href="browse_sessions.php" class="flex items-center text-gray-600 hover:text-purple-600 p-2 rounded-lg hover:bg-purple-50 transition-all">
                                <i class="fas fa-search mr-3 w-4"></i>
                                <span>Cari Sesi</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white shadow-lg mt-12 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="bg-gradient-to-r from-purple-500 to-blue-600 p-2 rounded-lg mr-3">
                        <i class="fas fa-brain text-white"></i>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                        Quizzy Quest
                    </span>
                </div>
                <p class="text-gray-600">&copy; 2024 Quizzy Quest. Dibuat dengan ❤️ untuk pembelajaran interaktif.</p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle user menu
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }

        // Close user menu when clicking outside
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('userMenu');
            const button = e.target.closest('button[onclick="toggleUserMenu()"]');
            
            if (!button && !menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>