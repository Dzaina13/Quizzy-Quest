<?php
// assets/php/components/live_room_modal.php
?>
<div id="liveRoomModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
  <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
      <div class="p-6">
          <div class="text-center mb-6">
              <div class="bg-gradient-to-r from-red-500 to-pink-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                  <i class="fas fa-broadcast-tower text-white text-2xl"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-900 mb-2">🔴 Live Room Dibuat!</h3>
              <p class="text-gray-600" id="quiz-title-modal">Quiz berhasil dibuat sebagai live room</p>
          </div>
          
          <div class="bg-gray-50 rounded-lg p-4 mb-6">
              <div class="text-center">
                  <p class="text-sm text-gray-600 mb-2">Room Code:</p>
                  <div class="text-3xl font-bold text-blue-600 tracking-wider" id="room-code-display">
                      ------
                  </div>
                  <p class="text-xs text-gray-500 mt-2">Bagikan kode ini kepada peserta</p>
              </div>
          </div>
          
          <div class="flex gap-3">
              <button onclick="copyRoomCode()" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200 text-sm font-medium">
                  <i class="fas fa-copy mr-2"></i>
                  Copy Code
              </button>
              <button onclick="goToWaitingRoom()" class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white py-2 px-4 rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-200 text-sm font-medium">
                  <i class="fas fa-arrow-right mr-2"></i>
                  Masuk Room
              </button>
          </div>
          
          <button onclick="closeLiveRoomModal()" class="w-full mt-3 text-gray-500 hover:text-gray-700 text-sm">
              Tutup
          </button>
      </div>
  </div>
</div>