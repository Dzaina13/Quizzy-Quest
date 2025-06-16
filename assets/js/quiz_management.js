// assets/js/quiz_management.js

let currentSessionId = null;
let currentRoomCode = null;

// Dropdown functions
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
      fetch('../assets/php/delete_quiz.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({ quiz_id: quizId })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              showNotification('Quiz berhasil dihapus', 'success');
              setTimeout(() => location.reload(), 1000);
          } else {
              showNotification('Error menghapus quiz: ' + data.message, 'error');
          }
      })
      .catch(error => {
          console.error('Error:', error);
          showNotification('Terjadi kesalahan saat menghapus quiz', 'error');
      });
  }
}

// Create Live Room function
function createLiveRoom(quizId, quizTitle) {
  const button = document.getElementById(`live-btn-${quizId}`);
  const originalContent = button.innerHTML;
  
  // Show loading state
  button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Membuat Room...';
  button.disabled = true;
  
  fetch('../assets/php/create_live_room.php', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
      },
      body: JSON.stringify({ quiz_id: quizId })
  })
  .then(response => response.json())
  .then(data => {
      if (data.success) {
          currentSessionId = data.session_id;
          currentRoomCode = data.room_code;
          
          // Update modal content
          document.getElementById('quiz-title-modal').textContent = quizTitle;
          document.getElementById('room-code-display').textContent = data.room_code;
          
          // Show modal
          document.getElementById('liveRoomModal').classList.remove('hidden');
          
          showNotification('Live room berhasil dibuat!', 'success');
      } else {
          showNotification('Error: ' + data.message, 'error');
      }
  })
  .catch(error => {
      console.error('Error:', error);
      showNotification('Terjadi kesalahan saat membuat live room', 'error');
  })
  .finally(() => {
      // Restore button
      button.innerHTML = originalContent;
      button.disabled = false;
  });
}

// Live Room Modal functions
function closeLiveRoomModal() {
  document.getElementById('liveRoomModal').classList.add('hidden');
  currentSessionId = null;
  currentRoomCode = null;
}

function copyRoomCode() {
  if (currentRoomCode) {
      navigator.clipboard.writeText(currentRoomCode).then(() => {
          showNotification('Room code berhasil disalin!', 'success');
      }).catch(() => {
          // Fallback for older browsers
          const textArea = document.createElement('textarea');
          textArea.value = currentRoomCode;
          document.body.appendChild(textArea);
          textArea.select();
          document.execCommand('copy');
          document.body.removeChild(textArea);
          showNotification('Room code berhasil disalin!', 'success');
      });
  }
}

function goToWaitingRoom() {
  if (currentSessionId) {
      window.location.href = `live_waiting.php?session_id=${currentSessionId}`;
  }
}

// Search functionality
const searchInput = document.querySelector('input[name="search"]');
if (searchInput) {
  let searchTimeout;
  
  searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
          this.form.submit();
      }, 500);
  });
}

// Notification system
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
  
  const colors = {
      success: 'bg-green-500 text-white',
      error: 'bg-red-500 text-white',
      info: 'bg-blue-500 text-white',
      warning: 'bg-yellow-500 text-black'
  };
  
  notification.className += ` ${colors[type] || colors.info}`;
  notification.textContent = message;
  
  document.body.appendChild(notification);
  
  // Animate in
  setTimeout(() => {
      notification.classList.remove('translate-x-full');
  }, 100);
  
  // Animate out and remove
  setTimeout(() => {
      notification.classList.add('translate-x-full');
      setTimeout(() => {
          document.body.removeChild(notification);
      }, 300);
  }, 3000);
}

// Close modal when clicking outside
document.getElementById('liveRoomModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
      closeLiveRoomModal();
  }
});