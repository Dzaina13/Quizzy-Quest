// Carousel Animation
const carouselCards = document.querySelectorAll('.carousel-card');
let currentIndex = 0;

function showNextCard() {
    // Hilangkan kelas 'active' dari slide saat ini
    carouselCards[currentIndex].classList.remove('active');

    // Pindah ke slide berikutnya
    currentIndex = (currentIndex + 1) % carouselCards.length;

    // Tambahkan kelas 'active' ke slide baru
    carouselCards[currentIndex].classList.add('active');
}

// Jalankan animasi setiap 5 detik
setInterval(showNextCard, 5000);