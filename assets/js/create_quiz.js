// create_quiz.js - FINAL FIXED VERSION
// CRITICAL FIX: ROF quiz HARUS kirim T/F ke database

let questionCount = 0;

function changeQuizType() {
    const quizType = document.getElementById('quiz_type').value;
    const infoDivs = document.querySelectorAll('.quiz-type-info');
    
    infoDivs.forEach(div => div.style.display = 'none');
    
    if (quizType) {
        const infoDiv = document.getElementById('info-' + quizType);
        if (infoDiv) {
            infoDiv.style.display = 'block';
        }
    }
    
    clearAllQuestions();
}

function addQuestion() {
    const quizType = document.getElementById('quiz_type').value;
    
    if (!quizType) {
        alert('Pilih tipe quiz terlebih dahulu!');
        return;
    }
    
    questionCount++;
    const container = document.getElementById('questionsContainer');
    const emptyDiv = document.getElementById('emptyQuestions');
    
    if (emptyDiv) {
        emptyDiv.style.display = 'none';
    }
    
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item';
    questionDiv.id = 'question-' + questionCount;
    
    let questionHTML = '';
    
    if (quizType === 'normal') {
        questionHTML = `
            <div class="question-header">
                <h4>📝 Pertanyaan ${questionCount}</h4>
                <button type="button" class="remove-btn" onclick="removeQuestion(${questionCount})">🗑️</button>
            </div>
            
            <div class="form-group">
                <label>Pertanyaan</label>
                <textarea name="questions[${questionCount-1}][question_text]" required 
                          placeholder="Tulis pertanyaan Anda di sini..."></textarea>
            </div>
            
            <div class="options-grid">
                <div class="form-group">
                    <label>Pilihan A</label>
                    <input type="text" name="questions[${questionCount-1}][option_a]" required 
                           placeholder="Pilihan A">
                </div>
                
                <div class="form-group">
                    <label>Pilihan B</label>
                    <input type="text" name="questions[${questionCount-1}][option_b]" required 
                           placeholder="Pilihan B">
                </div>
                
                <div class="form-group">
                    <label>Pilihan C</label>
                    <input type="text" name="questions[${questionCount-1}][option_c]" 
                           placeholder="Pilihan C (opsional)">
                </div>
                
                <div class="form-group">
                    <label>Pilihan D</label>
                    <input type="text" name="questions[${questionCount-1}][option_d]" 
                           placeholder="Pilihan D (opsional)">
                </div>
            </div>
            
            <div class="form-group">
                <label>Jawaban Benar</label>
                <select name="questions[${questionCount-1}][correct_answer]" required>
                    <option value="">Pilih jawaban benar</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Penjelasan (opsional)</label>
                <textarea name="questions[${questionCount-1}][explanation]" 
                          placeholder="Berikan penjelasan untuk jawaban yang benar..."></textarea>
            </div>
        `;
    } else if (quizType === 'rof') {
        // 🚨 CRITICAL: Pastikan value="T" dan value="F" BUKAN "true"/"false"
        questionHTML = `
            <div class="question-header">
                <h4>✅ Pertanyaan ${questionCount}</h4>
                <button type="button" class="remove-btn" onclick="removeQuestion(${questionCount})">🗑️</button>
            </div>
            
            <div class="form-group">
                <label>Pernyataan</label>
                <textarea name="questions[${questionCount-1}][question_text]" required 
                          placeholder="Tulis pernyataan yang akan dinilai benar/salah..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Jawaban Benar</label>
                <select name="questions[${questionCount-1}][correct_answer]" required>
                    <option value="">Pilih jawaban benar</option>
                    <option value="T">✅ Benar</option>
                    <option value="F">❌ Salah</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Penjelasan (opsional)</label>
                <textarea name="questions[${questionCount-1}][explanation]" 
                          placeholder="Berikan penjelasan untuk jawaban yang benar..."></textarea>
            </div>
            
            <!-- Hidden options untuk konsistensi database -->
            <input type="hidden" name="questions[${questionCount-1}][option_a]" value="Benar">
            <input type="hidden" name="questions[${questionCount-1}][option_b]" value="Salah">
            <input type="hidden" name="questions[${questionCount-1}][option_c]" value="">
            <input type="hidden" name="questions[${questionCount-1}][option_d]" value="">
        `;
    } else if (quizType === 'decision_maker') {
        questionHTML = `
            <div class="question-header">
                <h4>💭 Pertanyaan ${questionCount}</h4>
                <button type="button" class="remove-btn" onclick="removeQuestion(${questionCount})">🗑️</button>
            </div>
            
            <div class="form-group">
                <label>Pertanyaan</label>
                <textarea name="questions[${questionCount-1}][question_text]" required 
                          placeholder="Tulis pertanyaan terbuka Anda di sini..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Contoh Jawaban/Panduan</label>
                <textarea name="questions[${questionCount-1}][explanation]" 
                          placeholder="Berikan contoh jawaban atau panduan penilaian..."></textarea>
            </div>
            
            <input type="hidden" name="questions[${questionCount-1}][option_a]" value="Open Answer">
            <input type="hidden" name="questions[${questionCount-1}][option_b]" value="">
            <input type="hidden" name="questions[${questionCount-1}][option_c]" value="">
            <input type="hidden" name="questions[${questionCount-1}][option_d]" value="">
            <input type="hidden" name="questions[${questionCount-1}][correct_answer]" value="open">
        `;
    }
    
    questionDiv.innerHTML = questionHTML;
    container.appendChild(questionDiv);
    
    updateQuestionCount();
}

function removeQuestion(questionId) {
    if (confirm('Yakin ingin menghapus pertanyaan ini?')) {
        const questionDiv = document.getElementById('question-' + questionId);
        if (questionDiv) {
            questionDiv.remove();
            updateQuestionCount();
            
            const remainingQuestions = document.querySelectorAll('.question-item');
            if (remainingQuestions.length === 0) {
                const emptyDiv = document.getElementById('emptyQuestions');
                if (emptyDiv) {
                    emptyDiv.style.display = 'block';
                }
            }
        }
    }
}

function clearAllQuestions() {
    const container = document.getElementById('questionsContainer');
    const questionItems = container.querySelectorAll('.question-item');
    
    questionItems.forEach(item => item.remove());
    
    const emptyDiv = document.getElementById('emptyQuestions');
    if (emptyDiv) {
        emptyDiv.style.display = 'block';
    }
    
    questionCount = 0;
    updateQuestionCount();
}

function updateQuestionCount() {
    const remainingQuestions = document.querySelectorAll('.question-item');
    const countElement = document.getElementById('question-count');
    
    if (countElement) {
        const count = remainingQuestions.length;
        countElement.textContent = count + ' Pertanyaan';
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createQuizForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const questions = document.querySelectorAll('.question-item');
            
            if (questions.length === 0) {
                e.preventDefault();
                alert('Minimal harus ada 1 pertanyaan!');
                return false;
            }
            
            return true;
        });
    }
});

// Debug function - panggil ini sebelum submit untuk cek data
function debugFormData() {
    const formData = new FormData(document.getElementById('createQuizForm'));
    console.log('=== FORM DATA DEBUG ===');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    console.log('======================');
}