<?php
// Konfigurasi dasar
$page_title = "Quizzy Quest - Beranda";
$current_year = date('Y');

// Data untuk carousel
$carousel_images = [
  'assets/images/carousel/card1.png',
  'assets/images/carousel/card1.png',
  'assets/images/carousel/card1.png'
];

// Data untuk fitur
$features = [
  [
      'title' => 'Membuat Quiz',
      'description' => 'Buat quiz sendiri dan tantang teman-temanmu.'
  ],
  [
      'title' => 'Right or False',
      'description' => 'Tes pengetahuanmu dengan pertanyaan benar atau salah.'
  ],
  [
      'title' => 'Decision Maker',
      'description' => 'Ambil keputusan berdasarkan hasil dari quiz.'
  ]
];

// Data untuk sitemap
$sitemap_links = [
  ['url' => 'index.php', 'title' => 'Beranda'],
  ['url' => 'pages/login.php', 'title' => 'Login'],
  ['url' => 'pages/register.php', 'title' => 'Register'],
  ['url' => 'pages/login.php', 'title' => 'Lobby Quiz']
];

// Informasi kontak
$contact_info = [
  'email' => 'support@quizzyquest.com',
  'phone' => '+123-456-7890'
];

// Function untuk generate HTML
function generateHTML($page_title, $current_year, $carousel_images, $features, $sitemap_links, $contact_info) {
  $html = '<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . $page_title . '</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Header -->
  <header id="site-header">
      <div class="header-container">
          <img src="assets/images/logo.png" alt="Quizzy Quest Logo" class="site-logo">
          <nav class="main-navigation">
              <ul class="nav-links">
                  <li><a href="pages/login.php" class="nav-link">Login</a></li>
                  <li><a href="pages/register.php" class="nav-link">Register</a></li>
              </ul>
          </nav>
      </div>
  </header>

  <!-- Main Content -->
  <main id="main-content">
      <!-- Join Quiz Section -->
      <section class="join-quiz-section">
          <h2 class="section-title">Gabung Sekarang!</h2>
          <p class="section-description">Mulai petualangan kuismu hari ini.</p>
          <a href="pages/login.php" class="join-quiz-button">Masuk ke Lobby Quiz</a>
      </section>

      <!-- Carousel Section -->
      <section class="carousel-section">
          <div class="carousel-container">';
  
  // Generate carousel cards
  foreach ($carousel_images as $index => $image) {
      $active_class = $index === 0 ? 'active' : '';
      $html .= '<div class="carousel-card ' . $active_class . '">
                  <img src="' . $image . '" alt="Carousel Image ' . ($index + 1) . '" class="carousel-image">
              </div>';
  }
  
  $html .= '</div>
      </section>

      <!-- Features Sections -->
      <section class="features-section">
          <h2 class="section-title">Apa yang Kami Tawarkan?</h2>
          <div class="feature-cards">';
  
  // Generate feature cards
  foreach ($features as $feature) {
      $html .= '<div class="feature-card">
                  <h3 class="card-title">' . htmlspecialchars($feature['title']) . '</h3>
                  <p class="card-description">' . htmlspecialchars($feature['description']) . '</p>
              </div>';
  }
  
  $html .= '</div>
      </section>
  </main>

  <!-- Footer -->
  <footer id="site-footer">
      <div class="footer-top">
          <div class="cta-section">
              <h3 class="cta-title">Tertarik? Gabung Sekarang!</h3>
              <a href="pages/register.php" class="cta-button">Daftar Sekarang</a>
          </div>
          <div class="sitemap-section">
              <h3 class="sitemap-title">Peta Situs</h3>
              <ul class="sitemap-links">';
  
  // Generate sitemap links
  foreach ($sitemap_links as $link) {
      $html .= '<li><a href="' . $link['url'] . '">' . htmlspecialchars($link['title']) . '</a></li>';
  }
  
  $html .= '</ul>
          </div>
          <div class="contact-section">
              <h3 class="contact-title">Kontak Kami</h3>
              <p class="contact-info">Email: ' . $contact_info['email'] . '</p>
              <p class="contact-info">Telepon: ' . $contact_info['phone'] . '</p>
          </div>
      </div>
      <div class="footer-bottom">
          <p class="copyright-text">© ' . $current_year . ' Quizzy Quest. All rights reserved.</p>
      </div>
  </footer>

  <!-- JavaScript -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/scroll-effects.js"></script>
</body>
</html>';
  
  return $html;
}

// Generate dan tampilkan HTML
echo generateHTML($page_title, $current_year, $carousel_images, $features, $sitemap_links, $contact_info);
?>