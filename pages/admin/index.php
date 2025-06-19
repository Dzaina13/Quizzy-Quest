<?php
require_once '../../assets/php/koneksi_db.php';
require_once '../../assets/php/session_check.php';
require_once '../../assets/php/dashboard_handler.php';

// Cek apakah user sudah login
checkUserLogin();

// Ambil informasi user
$userInfo = getUserInfo();

// Inisialisasi dashboard handler
$dashboardHandler = new DashboardHandler($koneksi);

// Ambil data statistik
$totalQuizzes = $dashboardHandler->getTotalQuizzes();
$totalUsers = $dashboardHandler->getTotalUsers();
$totalSessions = $dashboardHandler->getTotalQuizSessions();
$averageScore = $dashboardHandler->getAverageScore();
$popularQuizzes = $dashboardHandler->getPopularQuizzes();
$recentActivities = $dashboardHandler->getRecentActivities();
$growthStats = $dashboardHandler->getGrowthStats();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Quizzy Quest</title>
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
                    <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg bg-white bg-opacity-20 hover:bg-opacity-30 transition-all">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="quis.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
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
                    <h2 class="text-2xl font-bold text-gray-800">Dashboard Admin</h2>
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

            <!-- Dashboard Content -->
            <main class="p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Quiz</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalQuizzes); ?></p>
                            </div>
                            <div class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end p-3 rounded-full">
                                <i class="fas fa-question-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-500 text-sm"><?php echo $growthStats['quiz_growth']; ?> dari bulan lalu</span>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Pengguna</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalUsers); ?></p>
                            </div>
                            <div class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end p-3 rounded-full">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-500 text-sm"><?php echo $growthStats['user_growth']; ?> dari bulan lalu</span>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Quiz Dimainkan</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($totalSessions); ?></p>
                            </div>
                            <div class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end p-3 rounded-full">
                                <i class="fas fa-play text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-500 text-sm"><?php echo $growthStats['session_growth']; ?> dari bulan lalu</span>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Rata-rata Skor</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $averageScore; ?>%</p>
                            </div>
                            <div class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end p-3 rounded-full">
                                <i class="fas fa-chart-line text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-red-500 text-sm"><?php echo $growthStats['score_growth']; ?> dari bulan lalu</span>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Quiz Populer -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quiz Terpopuler</h3>
                        <div class="space-y-4">
                            <?php foreach ($popularQuizzes as $index => $quiz): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold"><?php echo htmlspecialchars($quiz['title']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo number_format($quiz['play_count']); ?> dimainkan</p>
                                    </div>
                                </div>
                                <span class="text-green-500 font-semibold">+<?php echo rand(5, 20); ?>%</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Aktivitas Terbaru -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h3>
                        <div class="space-y-4">
                            <?php foreach ($recentActivities as $activity): ?>
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-<?php echo $activity['color']; ?>-500 rounded-full flex items-center justify-center">
                                    <i class="fas <?php echo $activity['icon']; ?> text-white text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-sm"><?php echo $activity['message']; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $activity['time']; ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="quis.php" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white p-4 rounded-lg hover:opacity-90 transition-all text-center">
                            <i class="fas fa-plus mb-2 text-xl"></i>
                            <p class="font-semibold">Buat Quiz Baru</p>
                        </a>
                        <a href="pengguna.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-lg hover:opacity-90 transition-all text-center">
                            <i class="fas fa-users mb-2 text-xl"></i>
                            <p class="font-semibold">Kelola Pengguna</p>
                        </a>
                        <button onclick="exportData()" class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-lg hover:opacity-90 transition-all">
                            <i class="fas fa-download mb-2 text-xl"></i>
                            <p class="font-semibold">Export Data</p>
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function exportData() {
            alert('Fitur export data akan segera tersedia!');
        }
    </script>
</body>
</html>
