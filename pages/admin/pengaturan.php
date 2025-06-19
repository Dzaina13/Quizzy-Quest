<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Quizzy Quest Admin</title>
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
                    <a href="index.html" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="quis.html" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-question-circle"></i>
                        <span>Kelola Quiz</span>
                    </a>
                    <a href="pengguna.html" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-users"></i>
                        <span>Pengguna</span>
                    </a>
                    <a href="statistik.html" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white hover:bg-opacity-20 transition-all">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik</span>
                    </a>
                    <a href="pengaturan.html" class="flex items-center space-x-3 p-3 rounded-lg bg-white bg-opacity-20 hover:bg-opacity-30 transition-all">
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
                        <p class="font-semibold">Admin</p>
                        <p class="text-sm opacity-75">admin@quizzyquest.com</p>
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
                        <h2 class="text-2xl font-bold text-gray-800">Pengaturan</h2>
                        <p class="text-gray-600 mt-1">Kelola pengaturan platform Quizzy Quest</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button onclick="saveSettings()" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-6 py-2 rounded-lg hover:opacity-90 transition-all flex items-center">
                            <i class="fas fa-save mr-2"></i>Simpan
                        </button>
                        
                        <div class="relative">
                            <i class="fas fa-bell text-gray-500 text-xl"></i>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                        </div>
                        
                        <button class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-4 py-2 rounded-lg hover:opacity-90 transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </div>
                </div>
            </header>

            <!-- Settings Content -->
            <main class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Informasi Situs -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-pink-500 mr-2"></i>
                            Informasi Situs
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Situs</label>
                                <input type="text" value="Quizzy Quest" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Admin</label>
                                <input type="email" value="admin@quizzyquest.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                                <textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">Platform quiz interaktif terbaik untuk belajar sambil bermain</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan Quiz -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-question-circle text-pink-500 mr-2"></i>
                            Pengaturan Quiz
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Waktu per Pertanyaan (detik)</label>
                                <input type="number" value="30" min="10" max="300" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Poin per Jawaban Benar</label>
                                <input type="number" value="10" min="1" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Acak Pertanyaan</label>
                                    <p class="text-xs text-gray-500">Acak urutan pertanyaan</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan Pengguna -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-users text-pink-500 mr-2"></i>
                            Pengaturan Pengguna
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Registrasi Terbuka</label>
                                    <p class="text-xs text-gray-500">Izinkan pengguna baru mendaftar</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Verifikasi Email</label>
                                    <p class="text-xs text-gray-500">Wajib verifikasi email</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Panjang Password</label>
                                <input type="number" value="8" min="6" max="32" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            </div>
                        </div>
                    </div>

                    <!-- Notifikasi -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-bell text-pink-500 mr-2"></i>
                            Notifikasi
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email Welcome</label>
                                    <p class="text-xs text-gray-500">Kirim email selamat datang</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Notifikasi Quiz</label>
                                    <p class="text-xs text-gray-500">Notifikasi setelah quiz selesai</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email SMTP</label>
                                <input type="text" value="smtp.gmail.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            </div>
                        </div>
                    </div>

                    <!-- Keamanan -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-shield-alt text-pink-500 mr-2"></i>
                            Keamanan
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Mode Maintenance</label>
                                    <p class="text-xs text-gray-500">Aktifkan mode maintenance</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Auto Backup</label>
                                    <p class="text-xs text-gray-500">Backup otomatis harian</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout (menit)</label>
                                <input type="number" value="30" min="5" max="480" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                            </div>
                        </div>
                    </div>

                    <!-- Tampilan -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-palette text-pink-500 mr-2"></i>
                            Tampilan
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tema Warna</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="relative">
                                        <input type="radio" name="theme" value="pink" id="theme-pink" class="sr-only" checked>
                                        <label for="theme-pink" class="block p-3 border-2 border-pink-500 rounded-lg cursor-pointer hover:bg-pink-50">
                                            <div class="w-full h-6 bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end rounded"></div>
                                            <p class="text-xs text-center mt-2 font-medium">Pink</p>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" name="theme" value="blue" id="theme-blue" class="sr-only">
                                        <label for="theme-blue" class="block p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50">
                                            <div class="w-full h-6 bg-gradient-to-r from-blue-500 to-blue-700 rounded"></div>
                                            <p class="text-xs text-center mt-2 font-medium">Blue</p>
                                        </label>
                                    </div>
                                    <div class="relative">
                                        <input type="radio" name="theme" value="green" id="theme-green" class="sr-only">
                                        <label for="theme-green" class="block p-3 border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-green-50">
                                            <div class="w-full h-6 bg-gradient-to-r from-green-500 to-green-700 rounded"></div>
                                            <p class="text-xs text-center mt-2 font-medium">Green</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Mode Gelap</label>
                                    <p class="text-xs text-gray-500">Aktifkan tampilan gelap</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Backup & Restore -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-database text-pink-500 mr-2"></i>
                            Backup & Restore
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Backup Terakhir</label>
                                <p class="text-sm text-gray-600 bg-gray-50 p-2 rounded">15 Juni 2024, 10:30 WIB</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <button class="bg-blue-100 text-blue-700 px-4 py-2 rounded-md hover:bg-blue-200 transition-all">
                                    <i class="fas fa-download mr-2"></i>Backup Sekarang
                                </button>
                                <button class="bg-orange-100 text-orange-700 px-4 py-2 rounded-md hover:bg-orange-200 transition-all">
                                    <i class="fas fa-upload mr-2"></i>Restore
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jadwal Backup</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-pink-500 focus:border-pink-500">
                                    <option value="daily" selected>Harian</option>
                                    <option value="weekly">Mingguan</option>
                                    <option value="monthly">Bulanan</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sistem Info -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-server text-pink-500 mr-2"></i>
                            Informasi Sistem
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Versi Platform:</span>
                                <span class="text-sm font-medium">v2.1.0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">PHP Version:</span>
                                <span class="text-sm font-medium">8.1.2</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Database:</span>
                                <span class="text-sm font-medium">MySQL 8.0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Storage Used:</span>
                                <span class="text-sm font-medium">2.3 GB / 10 GB</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Uptime:</span>
                                <span class="text-sm font-medium text-green-600">15 hari</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex justify-end space-x-4">
                    <button onclick="resetSettings()" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-all">
                        <i class="fas fa-undo mr-2"></i>Reset
                    </button>
                    <button onclick="saveSettings()" class="bg-gradient-to-r from-pink-gradient-start to-pink-gradient-end text-white px-6 py-2 rounded-lg hover:opacity-90 transition-all">
                        <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                    </button>
                </div>

            </main>
        </div>
    </div>

    <script>
        function saveSettings() {
            // Show loading
            const saveBtn = document.querySelector('button[onclick="saveSettings()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menyimpan...';
            saveBtn.disabled = true;
            
            // Simulate save process
            setTimeout(() => {
                saveBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Tersimpan!';
                saveBtn.classList.remove('from-pink-gradient-start', 'to-pink-gradient-end');
                saveBtn.classList.add('bg-green-500');
                
                setTimeout(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.classList.remove('bg-green-500');
                    saveBtn.classList.add('bg-gradient-to-r', 'from-pink-gradient-start', 'to-pink-gradient-end');
                    saveBtn.disabled = false;
                }, 2000);
            }, 1500);
        }

        function resetSettings() {
            if (confirm('Apakah Anda yakin ingin mereset semua pengaturan ke default?')) {
                alert('Pengaturan telah direset ke default!');
                location.reload();
            }
        }

        // Theme selection
        document.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update theme preview
                document.querySelectorAll('label[for^="theme-"]').forEach(label => {
                    label.classList.remove('border-pink-500', 'border-blue-500', 'border-green-500');
                    label.classList.add('border-gray-300');
                });
                
                const selectedLabel = document.querySelector(`label[for="${this.id}"]`);
                selectedLabel.classList.remove('border-gray-300');
                
                if (this.value === 'pink') {
                    selectedLabel.classList.add('border-pink-500');
                } else if (this.value === 'blue') {
                    selectedLabel.classList.add('border-blue-500');
                } else if (this.value === 'green') {
                    selectedLabel.classList.add('border-green-500');
                }
            });
        });
    </script>
</body>
</html>