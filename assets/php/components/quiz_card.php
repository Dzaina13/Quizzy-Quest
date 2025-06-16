<?php
// assets/php/components/quiz_card.php
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
$gradients = [
  'normal' => 'from-blue-500 to-blue-600',
  'rof' => 'from-green-500 to-green-600',
  'decision_maker' => 'from-purple-500 to-purple-600'
];
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200 <?= $view_mode === 'list' ? 'flex' : '' ?>">
  <!-- Quiz Type Badge -->
  <div class="<?= $view_mode === 'list' ? 'w-2 bg-gradient-to-b' : 'h-2 bg-gradient-to-r' ?> 
      <?= $gradients[$quiz['quiz_type']] ?? 'from-gray-500 to-gray-600' ?>
      <?= $view_mode === 'list' ? 'rounded-l-xl' : 'rounded-t-xl' ?>">
  </div>

  <div class="p-6 flex-1">
      <!-- Header -->
      <div class="flex items-start justify-between mb-4">
          <div class="flex items-center">
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
          
          <!-- Action Buttons -->
          <div class="space-y-2">
              <!-- Primary Actions -->
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
              
              <!-- Live Room Button -->
              <?php if ($quiz['total_questions'] > 0): ?>
                  <button 
                      onclick="createLiveRoom(<?= $quiz['quiz_id'] ?>, '<?= htmlspecialchars($quiz['title']) ?>')" 
                      class="w-full bg-gradient-to-r from-red-500 to-pink-600 text-white py-2 px-4 rounded-lg hover:from-red-600 hover:to-pink-700 transition-all duration-200 text-sm font-medium flex items-center justify-center"
                      id="live-btn-<?= $quiz['quiz_id'] ?>"
                  >
                      <i class="fas fa-broadcast-tower mr-2"></i>
                      <span>🔴 Buat Live Room</span>
                  </button>
              <?php else: ?>
                  <div class="w-full bg-gray-200 text-gray-500 py-2 px-4 rounded-lg text-sm font-medium text-center">
                      <i class="fas fa-exclamation-triangle mr-2"></i>
                      Tambahkan pertanyaan untuk Live Room
                  </div>
              <?php endif; ?>
          </div>
      </div>
  </div>
</div>