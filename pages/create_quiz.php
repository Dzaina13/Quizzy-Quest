<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=not_logged_in");
    exit();
}

// Ambil informasi user dari session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Quiz Baru - Quizzy Quest</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .quiz-type-info {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        .quiz-type-info.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .question-item {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .btn-hover {
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
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

    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Alert Messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    <div>
                        <?php
                        switch ($_GET['error']) {
                            case 'empty_fields':
                                echo 'Semua field wajib diisi!';
                                break;
                            case 'no_questions':
                                echo 'Minimal harus ada 1 pertanyaan!';
                                break;
                            case 'database_error':
                                echo 'Terjadi kesalahan database. Silakan coba lagi.';
                                break;
                            case 'invalid_quiz_type':
                                echo 'Tipe quiz tidak valid!';
                                break;
                            case 'not_logged_in':
                                echo 'Silakan login terlebih dahulu!';
                                break;
                            case 'invalid_request':
                                echo 'Request tidak valid!';
                                break;
                            default:
                                echo 'Terjadi kesalahan. Silakan coba lagi.';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <div>
                        <?php if ($_GET['success'] === 'created'): ?>
                            Quiz berhasil dibuat! 
                            <a href="dashboard.php" class="underline font-medium">Kembali ke Dashboard</a>
                            <?php if (isset($_GET['quiz_id'])): ?>
                                | <a href="view_quiz.php?id=<?php echo $_GET['quiz_id']; ?>" class="underline font-medium">Lihat Quiz</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Form -->
        <div class="bg-white rounded-xl card-shadow p-8">
            <form id="createQuizForm" method="POST" action="process_create_quiz.php">
                <input type="hidden" name="created_by" value="<?php echo $user_id; ?>">
                
                <!-- Quiz Information Section -->
                <div class="mb-8">
                    <div class="flex items-center mb-6">
                        <div class="bg-blue-100 rounded-full p-3 mr-4">
                            <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Informasi Quiz</h2>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="quiz_title" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-heading mr-2"></i>Judul Quiz
                            </label>
                            <input type="text" id="quiz_title" name="quiz_title" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                   placeholder="Masukkan judul quiz yang menarik..."
                                   value="<?php echo htmlspecialchars($_GET['title'] ?? ''); ?>">
                        </div>

                        <div class="md:col-span-2">
                            <label for="quiz_description" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-align-left mr-2"></i>Deskripsi Quiz
                            </label>
                            <textarea id="quiz_description" name="quiz_description" rows="4"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                                      placeholder="Berikan deskripsi singkat tentang quiz ini..."><?php echo htmlspecialchars($_GET['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label for="quiz_type" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-list-ul mr-2"></i>Tipe Quiz
                            </label>
                            <select id="quiz_type" name="quiz_type" required onchange="changeQuizType()"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <option value="">Pilih tipe quiz</option>
                                <option value="normal" <?php echo (($_GET['type'] ?? '') === 'normal') ? 'selected' : ''; ?>>
                                    📋 Normal Quiz (Pilihan Ganda)
                                </option>
                                <option value="rof" <?php echo (($_GET['type'] ?? '') === 'rof') ? 'selected' : ''; ?>>
                                    ✅ Right or False (Benar/Salah)
                                </option>
                                <option value="decision_maker" <?php echo (($_GET['type'] ?? '') === 'decision_maker') ? 'selected' : ''; ?>>
                                    💭 Decision Maker (Pertanyaan Terbuka)
                                </option>
                            </select>

                            <!-- Quiz Type Info Cards -->
                            <div class="mt-4 space-y-4">
                                <div id="info-normal" class="quiz-type-info bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-blue-800 flex items-center mb-2">
                                        <i class="fas fa-list-ol mr-2"></i>Normal Quiz
                                    </h4>
                                    <p class="text-blue-700 text-sm">Quiz dengan 4 pilihan jawaban (A, B, C, D). Peserta memilih satu jawaban yang paling tepat dari pilihan yang tersedia.</p>
                                </div>

                                <div id="info-rof" class="quiz-type-info bg-green-50 border border-green-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-green-800 flex items-center mb-2">
                                        <i class="fas fa-check-double mr-2"></i>Right or False
                                    </h4>
                                    <p class="text-green-700 text-sm">Quiz dengan 2 pilihan jawaban (Benar/Salah). Peserta menentukan apakah pernyataan yang diberikan benar atau salah.</p>
                                </div>

                                <div id="info-decision_maker" class="quiz-type-info bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-purple-800 flex items-center mb-2">
                                        <i class="fas fa-brain mr-2"></i>Decision Maker
                                    </h4>
                                    <p class="text-purple-700 text-sm">Quiz dengan pertanyaan terbuka. Peserta memberikan jawaban dalam bentuk teks bebas berdasarkan analisis mereka.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 rounded-full p-3 mr-4">
                                <i class="fas fa-question-circle text-green-600 text-xl"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Pertanyaan Quiz</h2>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span id="question-count" class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                                0 Pertanyaan
                            </span>
                            <button type="button" onclick="addQuestion()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg btn-hover flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Tambah Pertanyaan
                            </button>
                        </div>
                    </div>

                    <!-- Questions Container -->
                    <div id="questionsContainer" class="space-y-4">
                        <div id="emptyQuestions" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <i class="fas fa-question-circle text-gray-400 text-4xl mb-4"></i>
                            <h4 class="text-lg font-medium text-gray-600 mb-2">Belum ada pertanyaan</h4>
                            <p class="text-gray-500">Pilih tipe quiz terlebih dahulu, lalu klik tombol <strong>"Tambah Pertanyaan"</strong> untuk menambahkan pertanyaan pertama Anda</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="goBack()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg btn-hover flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Kembali
                    </button>
                    <button type="submit" id="submitBtn"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg btn-hover flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        let questionCount = 0;
        let currentQuizType = '';

        // Fungsi untuk mengubah tipe quiz
        function changeQuizType() {
            const quizType = document.getElementById('quiz_type').value;
            currentQuizType = quizType;
            
            // Hide all info cards
            document.querySelectorAll('.quiz-type-info').forEach(info => {
                info.classList.remove('active');
            });
            
            // Show selected info card
            if (quizType) {
                const infoCard = document.getElementById(`info-${quizType}`);
                if (infoCard) {
                    infoCard.classList.add('active');
                }
            }
            
            // Clear existing questions when changing type
            if (questionCount > 0) {
                if (confirm('Mengubah tipe quiz akan menghapus semua pertanyaan yang sudah dibuat. Lanjutkan?')) {
                    clearAllQuestions();
                } else {
                    // Revert selection
                    document.getElementById('quiz_type').value = currentQuizType;
                    return;
                }
            }
        }

        // Fungsi untuk menambah pertanyaan
        function addQuestion() {
            const quizType = document.getElementById('quiz_type').value;
            
            if (!quizType) {
                alert('Pilih tipe quiz terlebih dahulu!');
                return;
            }
            
            questionCount++;
            updateQuestionCount();
            
            const container = document.getElementById('questionsContainer');
            const emptyState = document.getElementById('emptyQuestions');
            
            if (emptyState) {
                emptyState.style.display = 'none';
            }
            
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-item bg-white border border-gray-200 rounded-lg p-6 shadow-sm';
            questionDiv.id = `question-${questionCount}`;
            
            let questionHTML = '';
            
            if (quizType === 'normal') {
                questionHTML = createNormalQuestionHTML(questionCount);
            } else if (quizType === 'rof') {
                questionHTML = createROFQuestionHTML(questionCount);
            } else if (quizType === 'decision_maker') {
                questionHTML = createDecisionMakerQuestionHTML(questionCount);
            }
            
            questionDiv.innerHTML = questionHTML;
            container.appendChild(questionDiv);
        }

        // Template untuk Normal Quiz
        function createNormalQuestionHTML(num) {
            return `
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                        <span class="bg-blue-100 text-blue-800 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">${num}</span>
                        Pertanyaan ${num}
                    </h4>
                    <button type="button" onclick="removeQuestion(${num})" 
                            class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                    <textarea name="questions[${num}][text]" required 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Masukkan pertanyaan..." rows="3"></textarea>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan A</label>
                        <input type="text" name="questions[${num}][option_a]" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Pilihan A">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan B</label>
                        <input type="text" name="questions[${num}][option_b]" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Pilihan B">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan C</label>
                        <input type="text" name="questions[${num}][option_c]" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Pilihan C">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan D</label>
                        <input type="text" name="questions[${num}][option_d]" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Pilihan D">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Benar</label>
                    <select name="questions[${num}][correct_answer]" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Pilih jawaban benar</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
            `;
        }

        // Template untuk Right or False
        function createROFQuestionHTML(num) {
            return `
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                        <span class="bg-green-100 text-green-800 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">${num}</span>
                        Pertanyaan ${num}
                    </h4>
                    <button type="button" onclick="removeQuestion(${num})" 
                            class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pernyataan</label>
                    <textarea name="questions[${num}][text]" required 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              placeholder="Masukkan pernyataan..." rows="3"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Benar</label>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="questions[${num}][correct_answer]" value="true" required 
                                   class="mr-2 text-green-600 focus:ring-green-500">
                            <span class="text-green-600 font-medium">Benar</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="questions[${num}][correct_answer]" value="false" required 
                                   class="mr-2 text-red-600 focus:ring-red-500">
                            <span class="text-red-600 font-medium">Salah</span>
                        </label>
                    </div>
                </div>
            `;
        }

        // Template untuk Decision Maker
        function createDecisionMakerQuestionHTML(num) {
            return `
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                        <span class="bg-purple-100 text-purple-800 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">${num}</span>
                        Pertanyaan ${num}
                    </h4>
                    <button type="button" onclick="removeQuestion(${num})" 
                            class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan/Skenario</label>
                    <textarea name="questions[${num}][text]" required 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Masukkan pertanyaan atau skenario untuk dianalisis..." rows="4"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Panduan Jawaban (Opsional)</label>
                    <textarea name="questions[${num}][guide]" 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Berikan panduan atau poin-poin yang harus dipertimbangkan..." rows="2"></textarea>
                </div>
            `;
        }

        // Fungsi untuk menghapus pertanyaan
        function removeQuestion(num) {
            if (confirm('Yakin ingin menghapus pertanyaan ini?')) {
                const questionDiv = document.getElementById(`question-${num}`);
                if (questionDiv) {
                    questionDiv.remove();
                    questionCount--;
                    updateQuestionCount();
                    
                    if (questionCount === 0) {
                        document.getElementById('emptyQuestions').style.display = 'block';
                    }
                }
            }
        }

        // Fungsi untuk menghapus semua pertanyaan
        function clearAllQuestions() {
            const container = document.getElementById('questionsContainer');
            const questions = container.querySelectorAll('.question-item');
            questions.forEach(q => q.remove());
            
            questionCount = 0;
            updateQuestionCount();
            document.getElementById('emptyQuestions').style.display = 'block';
        }

        // Update counter pertanyaan
        function updateQuestionCount() {
            const counter = document.getElementById('question-count');
            counter.textContent = `${questionCount} Pertanyaan`;
        }

        // Fungsi untuk kembali
        function goBack() {
            if (questionCount > 0) {
                if (confirm('Yakin ingin kembali? Data yang belum disimpan akan hilang.')) {
                    window.location.href = 'dashboard.php';
                }
            } else {
                window.location.href = 'dashboard.php';
            }
        }

        // Form validation
        document.getElementById('createQuizForm').addEventListener('submit', function(e) {
            if (questionCount === 0) {
                e.preventDefault();
                alert('Minimal harus ada 1 pertanyaan!');
                return false;
            }
        });

        // Auto-show quiz type info jika ada parameter dari URL
        document.addEventListener('DOMContentLoaded', function() {
            const quizType = document.getElementById('quiz_type').value;
            if (quizType) {
                changeQuizType();
            }
        });
    </script>
</body>
</html>