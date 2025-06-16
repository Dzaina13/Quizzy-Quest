<?php
// assets/php/components/quiz_filters.php
?>
<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
  <div class="p-6">
      <!-- Category Tabs -->
      <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
          <a href="?category=all&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
             class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
              <i class="fas fa-th-list mr-2"></i>
              <span>Semua</span>
              <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'all' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $counts['total'] ?></span>
          </a>
          <a href="?category=quiz&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
             class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'quiz' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
              <i class="fas fa-brain mr-2"></i>
              <span>Quiz</span>
              <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'quiz' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $counts['normal'] ?></span>
          </a>
          <a href="?category=rof&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
             class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'rof' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
              <i class="fas fa-target mr-2"></i>
              <span>RoF</span>
              <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'rof' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $counts['rof'] ?></span>
          </a>
          <a href="?category=decision_maker&search=<?= urlencode($search) ?>&view=<?= $view_mode ?>" 
             class="flex items-center px-4 py-2 rounded-lg font-medium transition-all duration-200 <?= $category === 'decision_maker' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
              <i class="fas fa-users-cog mr-2"></i>
              <span>Decision Maker</span>
              <span class="ml-2 px-2 py-1 text-xs rounded-full <?= $category === 'decision_maker' ? 'bg-white/20' : 'bg-gray-200' ?>"><?= $counts['decision_maker'] ?></span>
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