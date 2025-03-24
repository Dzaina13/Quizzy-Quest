// Fungsi untuk membaca dan memproses file CSV
function parseCSV(csvData) {
    const rows = csvData.split('\n'); // Pisahkan baris berdasarkan newline (\n)
    const headers = rows[0].split(',').map(header => header.trim()); // Ambil header (baris pertama) dan bersihkan spasi
    const parsedData = [];

    // Proses setiap baris (mulai dari baris kedua)
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        if (!row.trim()) continue; // Lewati baris kosong

        const values = row.split(','); // Pisahkan nilai berdasarkan koma
        const rowData = {};

        // Pasangkan header dengan nilai kolom
        headers.forEach((header, index) => {
            rowData[header] = values[index]?.trim() || ''; // Jika nilai kosong, tetapkan sebagai string kosong
        });

        parsedData.push(rowData); // Tambahkan ke array hasil parsing
    }

    return parsedData;
}

// Fungsi untuk mendapatkan pertanyaan berdasarkan nomor room
function getQuestionsByRoom(questions, roomNumber) {
    // Pastikan roomNumber adalah string
    const roomNumberStr = String(roomNumber);

    // Filter pertanyaan berdasarkan room_id yang dikonversi ke string
    return questions.filter(question => String(question.room_id) === roomNumberStr);
}

// Eksport fungsi jika menggunakan modul (opsional)
if (typeof module !== 'undefined') {
    module.exports = { parseCSV, getQuestionsByRoom };
}