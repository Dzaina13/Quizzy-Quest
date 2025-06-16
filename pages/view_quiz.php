<?php
// pages/view_quiz.php
session_start();
require_once '../assets/php/view_quiz_data.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Get filter parameters
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$view_mode = $_GET['view'] ?? 'grid';

// Initialize data handler
$dataHandler = new ViewQuizData($koneksi);

try {
  // Get quizzes and counts
  $quizzes = $dataHandler->getQuizzes($category, $search);
  $counts = $dataHandler->getCategoryCounts();
} catch (Exception $e) {
  die("Error loading data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz Management - Quiz System</title>
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
<body class="bg-gray-50 min-h-screen">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <?php include '../assets/php/components/quiz_header.php'; ?>

      <!-- Filters & Search -->
      <?php include '../assets/php/components/quiz_filters.php'; ?>

      <!-- Quiz Content -->
      <?php if (empty($quizzes)): ?>
          <?php include '../assets/php/components/quiz_empty_state.php'; ?>
      <?php else: ?>
          <div class="<?= $view_mode === 'list' ? 'space-y-4' : 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' ?>">
              <?php foreach ($quizzes as $quiz): ?>
                  <?php include '../assets/php/components/quiz_card.php'; ?>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
  </div>

  <!-- Live Room Modal -->
  <?php include '../assets/php/components/live_room_modal.php'; ?>

  <!-- JavaScript -->
  <script src="../assets/js/quiz_management.js"></script>
</body>
</html>