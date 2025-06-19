<?php
require_once '../../assets/php/session_check.php';

// Cek apakah user sudah login
checkUserLogin();
$userInfo = getUserInfo();

// Cek apakah user adalah admin
if ($userInfo['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik - Quizzy Quest Admin</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="flex h-screen">
        <div class="w-64 bg-gradient-to-b from-pink-gradient-start to-pink-gradient-end text-white">
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-8">Quizzy Quest</h1>
                <nav class="space-y-4">
                    <a href="../dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="../quiz/kelola_quiz.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-question-circle"></i>
                        <span>Kelola Quiz</span>
                    </a>
                    <a href="../users/kelola_users.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-users"></i>
                        <span>Pengguna</span>
                    </a>
                    <a href="statistik.php" class="flex items-center space-x-3 p-3 rounded-lg bg-white bg-opacity-20 hover:bg-opacity-30 transition-all">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik</span>
                    </a>
                    <a href="../sessions/kelola_sessions.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-play-circle"></i>
                        <span>Sessions</span>
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
                        <p class="text-sm opacity-75"><?php echo htmlspecialchars($userInfo['email'] ?? 'admin@quizzyquest.com'); ?></p>
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
                        <h2 class="text-2xl font-bold text-gray-800">Statistik & Analytics</h2>
                        <p class="text-gray-600 mt-1">Monitor performa dan insight platform Quizzy Quest</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Date Range Picker -->
                        <select id="dateRange" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="7">7 Hari Terakhir</option>
                            <option value="30" selected>30 Hari Terakhir</option>
                            <option value="90">3 Bulan Terakhir</option>
                            <option value="365">1 Tahun Terakhir</option>
                        </select>
                        
                        <button onclick="exportReport()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all flex items-center">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                        
                        <a href="../../assets/php/logout.php" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-4 py-2 rounded-lg hover:opacity-90 transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </header>

            <!-- Statistics Content -->
            <main class="p-6">
                <!-- Loading Spinner -->
                <div id="loadingSpinner" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-pink-gradient-start"></div>
                </div>
                
                <!-- Error Message -->
                <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <span id="errorText"></span>
                    </div>
                </div>
                
                <!-- Statistics Content -->
                <div id="statsContent" class="hidden">
                    <!-- Key Metrics Overview -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Total Quiz Sessions</p>
                                    <p class="text-3xl font-bold text-gray-800" id="totalSessions">0</p>
                                    <div class="flex items-center mt-2">
                                        <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                                        <span class="text-green-500 text-sm">+12.5%</span>
                                        <span class="text-gray-500 text-sm ml-1">vs bulan lalu</span>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-blue-400 to-blue-600 p-3 rounded-full">
                                    <i class="fas fa-play text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Total Participants</p>
                                    <p class="text-3xl font-bold text-gray-800" id="totalParticipants">0</p>
                                    <div class="flex items-center mt-2">
                                        <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                                        <span class="text-green-500 text-sm">+2.1%</span>
                                        <span class="text-gray-500 text-sm ml-1">vs bulan lalu</span>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-green-400 to-green-600 p-3 rounded-full">
                                    <i class="fas fa-users text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Active Users</p>
                                    <p class="text-3xl font-bold text-gray-800" id="activeUsers">0</p>
                                    <div class="flex items-center mt-2">
                                        <i class="fas fa-arrow-down text-red-500 text-sm mr-1"></i>
                                        <span class="text-red-500 text-sm">-0.8</span>
                                        <span class="text-gray-500 text-sm ml-1">vs bulan lalu</span>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 p-3 rounded-full">
                                    <i class="fas fa-user-check text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Total Quizzes</p>
                                    <p class="text-3xl font-bold text-gray-800" id="totalQuizzes">0</p>
                                    <div class="flex items-center mt-2">
                                        <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                                        <span class="text-green-500 text-sm">+5.2%</span>
                                        <span class="text-gray-500 text-sm ml-1">vs bulan lalu</span>
                                    </div>
                                </div>
                                <div class="bg-gradient-to-r from-purple-400 to-purple-600 p-3 rounded-full">
                                    <i class="fas fa-question-circle text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 1 -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Quiz Activity Chart -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Aktivitas Quiz Harian</h3>
                                <div class="flex space-x-2">
                                    <button class="text-sm px-3 py-1 bg-pink-100 text-pink-600 rounded-full">Quiz</button>
                                    <button class="text-sm px-3 py-1 text-gray-500 hover:bg-gray-100 rounded-full">Pengguna</button>
                                </div>
                            </div>
                            <div class="h-80">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Category Performance -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Performa Kategori Quiz</h3>
                                <button class="text-sm text-pink-600 hover:text-pink-800">Lihat Semua</button>
                            </div>
                            <div class="h-80">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Performers & Recent Activity -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Top Quiz -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Quiz Terpopuler</h3>
                                <button class="text-sm text-pink-600 hover:text-pink-800">Lihat Semua</button>
                            </div>
                            <div class="space-y-4" id="topQuizzesContainer">
                                <div class="flex items-center justify-center p-8">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-gradient-start"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Users -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Top Performers</h3>
                                <button class="text-sm text-pink-600 hover:text-pink-800">Lihat Semua</button>
                            </div>
                            <div class="space-y-4" id="topUsersContainer">
                                <div class="flex items-center justify-center p-8">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-gradient-start"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Analytics Table -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">Analisis Detail Quiz</h3>
                                <div class="flex space-x-2">
                                    <select class="px-3 py-1 border border-gray-300 rounded text-sm">
                                        <option>Semua Tipe</option>
                                        <option>Normal</option>
                                        <option>Decision Maker</option>
                                        <option>Right or False</option>
                                    </select>
                                    <button onclick="exportDetailedReport()" class="text-sm text-pink-600 hover:text-pink-800">Export CSV</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participants</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Questions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="quizDetailsTable">
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center">
                                            <div class="flex items-center justify-center">
                                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-pink-gradient-start"></div>
                                                <span class="ml-2 text-gray-500">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        let activityChart = null;
        let categoryChart = null;
        
        // Load statistics data
        function loadStatistics(range = 30) {
            showLoading();
            hideError();
            
            fetch(`../../assets/php/stats_data.php?range=${range}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateMetrics(data.metrics);
                        updateActivityChart(data.activity);
                        updateTopQuizzes(data.topQuizzes);
                        updateTopUsers(data.topUsers);
                        updateQuizDetails(data.quizDetails);
                        showContent();
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error loading statistics:', error);
                    showError('Gagal memuat data statistik: ' + error.message);
                })
                .finally(() => {
                    hideLoading();
                });
        }
        
        // Update metrics cards
        function updateMetrics(metrics) {
            document.getElementById('totalSessions').textContent = metrics.totalSessions.toLocaleString();
            document.getElementById('totalParticipants').textContent = metrics.totalParticipants.toLocaleString();
            document.getElementById('activeUsers').textContent = metrics.activeUsers.toLocaleString();
            document.getElementById('totalQuizzes').textContent = metrics.totalQuizzes.toLocaleString();
        }
        
        // Update activity chart
        function updateActivityChart(activityData) {
            const ctx = document.getElementById('activityChart').getContext('2d');
            
            // Destroy existing chart if it exists
            if (activityChart) {
                activityChart.destroy();
            }
            
            activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: activityData.labels,
                    datasets: [{
                        label: 'Quiz Sessions',
                        data: activityData.data,
                        borderColor: '#ff6b9d',
                        backgroundColor: 'rgba(255, 107, 157, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        pointBackgroundColor: '#ff6b9d',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Update category chart with dummy data for now
            updateCategoryChart();
        }
        
        // Update category chart
        function updateCategoryChart() {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            
            if (categoryChart) {
                categoryChart.destroy();
            }
            
            categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Normal', 'Decision Maker', 'Right or False'],
                    datasets: [{
                        data: [60, 25, 15],
                        backgroundColor: [
                            '#ff6b9d',
                            '#4ecdc4',
                            '#45b7d1'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }
        
        // Update top quizzes
        function updateTopQuizzes(quizzes) {
            const container = document.getElementById('topQuizzesContainer');
            
            if (quizzes.length === 0) {
                container.innerHTML = `
                    <div class="flex items-center justify-center p-8 text-gray-500">
                        <i class="fas fa-inbox mr-2"></i>
                        Belum ada data quiz
                    </div>
                `;
                return;
            }
            
            container.innerHTML = quizzes.map(quiz => `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-question-circle text-white"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">${escapeHtml(quiz.title)}</p>
                            <p class="text-sm text-gray-500">${quiz.sessions} sessions, ${quiz.participants} participants</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-green-600">${quiz.sessions}</p>
                        <p class="text-sm text-gray-500">Sessions</p>
                    </div>
                </div>
            `).join('');
        }
        
        // Update top users
        function updateTopUsers(users) {
            const container = document.getElementById('topUsersContainer');
            
            if (users.length === 0) {
                container.innerHTML = `
                    <div class="flex items-center justify-center p-8 text-gray-500">
                        <i class="fas fa-inbox mr-2"></i>
                        Belum ada data pengguna
                    </div>
                `;
                return;
            }
            
            container.innerHTML = users.map(user => `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-full flex items-center justify-center mr-3">
                            <span class="text-white font-semibold text-sm">${user.username.substring(0, 2).toUpperCase()}</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">${escapeHtml(user.username)}</p>
                            <p class="text-sm text-gray-500">${user.participations} participations</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-yellow-600">${user.participations}</p>
                        <p class="text-sm text-gray-500">Participations</p>
                    </div>
                </div>
            `).join('');
        }
        
        // Update quiz details table
        function updateQuizDetails(details) {
            const tbody = document.getElementById('quizDetailsTable');
            
            if (details.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox mr-2"></i>
                            Belum ada data quiz
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = details.map(quiz => `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-question-circle text-white text-xs"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">${escapeHtml(quiz.title)}</div>
                                <div class="text-sm text-gray-500">${escapeHtml(quiz.description)}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getQuizTypeBadgeClass(quiz.type)}">
                            ${getQuizTypeLabel(quiz.type)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-blue-600">${quiz.sessions}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-green-600">${quiz.participants}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-600">${quiz.questions}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center text-green-600">
                            <i class="fas fa-check-circle text-sm mr-1"></i>
                            <span class="text-sm">Active</span>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        // Helper functions
        function getQuizTypeBadgeClass(type) {
            switch(type) {
                case 'normal': return 'bg-blue-100 text-blue-800';
                case 'decision_maker': return 'bg-yellow-100 text-yellow-800';
                case 'rof': return 'bg-green-100 text-green-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }
        
        function getQuizTypeLabel(type) {
            switch(type) {
                case 'normal': return 'Normal';
                case 'decision_maker': return 'Decision Maker';
                case 'rof': return 'Right or False';
                default: return 'Unknown';
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showLoading() {
            document.getElementById('loadingSpinner').classList.remove('hidden');
            document.getElementById('statsContent').classList.add('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loadingSpinner').classList.add('hidden');
        }
        
        function showContent() {
            document.getElementById('statsContent').classList.remove('hidden');
        }
        
        function showError(message) {
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').classList.remove('hidden');
            document.getElementById('statsContent').classList.add('hidden');
        }
        
        function hideError() {
            document.getElementById('errorMessage').classList.add('hidden');
        }
        
        // Export functions
        function exportReport() {
            const dateRange = document.getElementById('dateRange').value;
            console.log('Exporting report for', dateRange, 'days');
            
            const csvContent = `Date Range,Total Sessions,Total Participants,Active Users,Total Quizzes
${dateRange} days,${document.getElementById('totalSessions').textContent},${document.getElementById('totalParticipants').textContent},${document.getElementById('activeUsers').textContent},${document.getElementById('totalQuizzes').textContent}`;
            
                       downloadCSV(csvContent, `quizzy-quest-summary-${dateRange}days.csv`);
        }
        
        function exportDetailedReport() {
            const tableRows = document.querySelectorAll('#quizDetailsTable tr');
            let csvContent = 'Quiz Title,Type,Sessions,Participants,Questions,Status\n';
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) {
                    const title = cells[0].querySelector('.font-medium')?.textContent || '';
                    const type = cells[1].querySelector('span')?.textContent || '';
                    const sessions = cells[2].querySelector('span')?.textContent || '';
                    const participants = cells[3].querySelector('span')?.textContent || '';
                    const questions = cells[4].querySelector('span')?.textContent || '';
                    const status = cells[5].querySelector('span')?.textContent || '';
                    
                    csvContent += `"${title}","${type}","${sessions}","${participants}","${questions}","${status}"\n`;
                }
            });
            
            downloadCSV(csvContent, 'quizzy-quest-detailed-report.csv');
        }
        
        function downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
        
        // Date Range Change Handler
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial statistics
            loadStatistics();
            
            // Date range change handler
            document.getElementById('dateRange').addEventListener('change', function(e) {
                loadStatistics(e.target.value);
            });
            
            // Filter functionality for detailed table
            const filterSelect = document.querySelector('.p-6 select');
            if (filterSelect) {
                filterSelect.addEventListener('change', function(e) {
                    const selectedType = e.target.value;
                    const rows = document.querySelectorAll('#quizDetailsTable tr');
                    
                    rows.forEach(row => {
                        const typeCell = row.querySelector('td:nth-child(2) span');
                        if (typeCell) {
                            const rowType = typeCell.textContent.trim();
                            if (selectedType === 'Semua Tipe' || rowType === selectedType) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });
                });
            }
        });
        
        // Debug function (uncomment for debugging)
        function debugFetch() {
            console.log('Fetching statistics data...');
            fetch('../../assets/php/stats_data.php?range=30')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }
        
        // Refresh data every 5 minutes
        setInterval(() => {
            const currentRange = document.getElementById('dateRange').value;
            loadStatistics(currentRange);
        }, 300000); // 5 minutes
        
        console.log('Statistics page loaded successfully with Tailwind CSS');
    </script>
</body>
</html>
