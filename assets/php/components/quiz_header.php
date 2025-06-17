<?php
// assets/php/components/quiz_header.php
?>
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
                      <p class="font-semibold text-gray-900"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
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