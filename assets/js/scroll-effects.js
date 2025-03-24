// Scroll Effects for Header and Footer
document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('site-header');
    const navlinks = document.querySelectorAll('.nav-link'); // Gunakan querySelectorAll untuk class
    const footer = document.getElementById('site-footer');

    // Fungsi untuk menangani efek scroll
    function handleScroll() {
        const scrollPosition = window.scrollY;

        // Debugging nilai scrollPosition
        console.log('Scroll Position:', scrollPosition);

        // Efek untuk Header
        if (scrollPosition > 0) {
            // Saat scroll ke bawah, ubah header menjadi putih
            header.style.backgroundColor = '#fff';

            // Ubah warna teks semua .nav-link
            navlinks.forEach(link => {
                link.style.color = '#333'; // Gunakan tanda pagar (#) untuk warna hex
            });
        } else {
            // Saat di atas, kembalikan header ke transparan
            header.style.backgroundColor = 'rgba(255,255,255,0  )';

            // Kembalikan warna teks semua .nav-link
            navlinks.forEach(link => {
                link.style.color = '#fff';
            });
        }

        // Efek untuk Footer
        if (scrollPosition > 5) {
            // Saat scroll ke bawah, ubah footer menjadi putih
            footer.style.backgroundColor = '#fff';
            footer.style.color = '#333'; // Ubah teks menjadi hitam
        } else {
            // Saat di atas, kembalikan footer ke transparan
            footer.style.backgroundColor = 'rgba(255, 255, 255, 0)';
            footer.style.color = '#fff'; // Kembalikan teks menjadi putih
        }
    }

    // Tambahkan event listener untuk scroll
    window.addEventListener('scroll', handleScroll);
});