const questions = [
    {
        question: "Tag HTML mana yang digunakan untuk membuat tautan?",
        answers: [
            { text: "<link>", correct: false },
            { text: "<a>", correct: true },
            { text: "<href>", correct: false },
            { text: "<url>", correct: false },
        ]
    },
    {
        question: "Apa singkatan dari HTML?",
        answers: [
            { text: "Hyper Text Markup Language", correct: true },
            { text: "Home Tool Markup Language", correct: false },
            { text: "Hyperlinks and Text Markup Language", correct: false },
            { text: "Highly Text Modified Language", correct: false },
        ]
    },
    {
        question: "Tag HTML mana yang digunakan untuk membuat daftar tak berurut?",
        answers: [
            { text: "<ol>", correct: false },
            { text: "<ul>", correct: true },
            { text: "<dl>", correct: false },
            { text: "<li>", correct: false },
        ]
    },
    {
        question: "Tag HTML mana yang digunakan untuk menambahkan gambar?",
        answers: [
            { text: "<picture>", correct: false },
            { text: "<src>", correct: false },
            { text: "<img>", correct: true },
            { text: "<image>", correct: false },
        ]
    },
    {
        question: "Atribut mana yang digunakan untuk menentukan URL tujuan tautan?",
        answers: [
            { text: "src", correct: false },
            { text: "href", correct: true },
            { text: "link", correct: false },
            { text: "url", correct: false },
        ]
    },
];

const questionElement = document.getElementById("question");
const answerButtons = document.getElementById("answer-buttons");
const nextButton = document.getElementById("next-btn");

let currentQuestionIndex = 0;
let score = 0;

function startQuiz() {
    currentQuestionIndex = 0;
    score = 0;
    nextButton.innerHTML = "Next";
    showQuestion();
}

function showQuestion() {
    resetState();
    let currentQuestion = questions[currentQuestionIndex];
    let questionNo = currentQuestionIndex + 1;
    questionElement.innerHTML = questionNo + ". " + currentQuestion.question;

    currentQuestion.answers.forEach((answer) => {
        const button = document.createElement("button");
        button.innerHTML = answer.text;
        button.classList.add("btn");
        button.dataset.correct = answer.correct;
        button.addEventListener("click", selectAnswer);
        answerButtons.appendChild(button);
    });
}

function resetState() {
    nextButton.style.display = "none";
    while (answerButtons.firstChild) {
        answerButtons.removeChild(answerButtons.firstChild);
    }
}

function selectAnswer(e) {
    const selectedBtn = e.target;
    const isCorrect = selectedBtn.dataset.correct === "true";
    if (isCorrect) {
        selectedBtn.classList.add("correct");
        score++;
    } else {
        selectedBtn.classList.add("incorrect");
    }

    Array.from(answerButtons.children).forEach((button) => {
        button.disabled = true;
    });
    nextButton.style.display = "block";
}

function showScore() {
    resetState();
    questionElement.innerHTML = `Anda mendapatkan ${score} dari ${questions.length} jawaban yang benar!`;
    nextButton.innerHTML = "Main Lagi";
    nextButton.style.display = "block";
    nextButton.addEventListener("click", startQuiz);
}

function handleNextButton() {
    currentQuestionIndex++;
    if (currentQuestionIndex < questions.length) {
        showQuestion();
    } else {
        showScore();
    }
}

nextButton.addEventListener("click", () => {
    if (currentQuestionIndex < questions.length) {
        handleNextButton();
    } else {
        startQuiz();
    }
});

startQuiz();