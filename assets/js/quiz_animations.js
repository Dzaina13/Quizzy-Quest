// assets/js/quiz_animations.js - Complete Final Version (No Hearts, Simplified)

class QuizAnimations {
  constructor() {
      this.isRoFMode = false;
      this.selectedOption = null;
      this.isAnswering = false;
      this.currentQuestionId = null;
      this.currentQuestionNumber = 1;
      
      this.init();
  }
  
  init() {
      this.detectQuizType();
      this.setupEventListeners();
      this.initializeUI();
  }
  
  detectQuizType() {
      // Detect quiz type from data attribute
      const quizTypeElement = document.querySelector('[data-quiz-type]');
      
      if (quizTypeElement) {
          this.isRoFMode = quizTypeElement.dataset.quizType === 'rof';
      }
      
      if (this.isRoFMode) {
          document.body.classList.add('rof-mode');
      } else {
          document.body.classList.add('regular-mode');
      }
      
      console.log(`🎯 Quiz mode: ${this.isRoFMode ? 'Ring of Fire 🔥' : 'Regular Quiz 📝'}`);
  }
  
  setupEventListeners() {
      // Option selection
      document.addEventListener('click', (e) => {
          if (e.target.closest('.option-btn') && !this.isAnswering) {
              this.selectOption(e.target.closest('.option-btn'));
          }
      });
      
      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
          if (this.isAnswering) return;
          
          const options = document.querySelectorAll('.option-btn:not(.disabled)');
          const currentIndex = Array.from(options).findIndex(opt => opt.classList.contains('selected'));
          
          switch(e.key) {
              case '1':
              case '2':
              case '3':
              case '4':
                  e.preventDefault();
                  const optionIndex = parseInt(e.key) - 1;
                  if (options[optionIndex]) {
                      this.selectOption(options[optionIndex]);
                  }
                  break;
              case 'ArrowUp':
              case 'ArrowDown':
              case 'ArrowLeft':
              case 'ArrowRight':
                  e.preventDefault();
                  let nextIndex;
                  if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                      nextIndex = currentIndex > 0 ? currentIndex - 1 : options.length - 1;
                  } else {
                      nextIndex = currentIndex < options.length - 1 ? currentIndex + 1 : 0;
                  }
                  if (options[nextIndex]) {
                      this.selectOption(options[nextIndex]);
                  }
                  break;
              case 'Enter':
              case ' ':
                  e.preventDefault();
                  if (this.selectedOption) {
                      this.submitAnswer();
                  }
                  break;
              case 'Escape':
                  e.preventDefault();
                  this.hideFeedback();
                  break;
          }
      });
      
      // Close feedback on click outside
      document.addEventListener('click', (e) => {
          if (e.target.classList.contains('answer-feedback')) {
              this.hideFeedback();
          }
      });
  }
  
  initializeUI() {
      // Add quiz type badge
      this.addQuizTypeBadge();
      
      // Add option letters
      this.addOptionLetters();
      
      // Update question counter
      this.updateQuestionCounter();
  }
  
  addQuizTypeBadge() {
      const quizTitle = document.querySelector('.quiz-info h1');
      if (quizTitle && !quizTitle.querySelector('.quiz-type-badge')) {
          const badge = document.createElement('span');
          badge.className = `quiz-type-badge ${this.isRoFMode ? 'rof' : 'regular'}`;
          badge.textContent = this.isRoFMode ? 'Ring of Fire' : 'Regular Quiz';
          quizTitle.appendChild(badge);
      }
  }
  
  addOptionLetters() {
      const options = document.querySelectorAll('.option-btn');
      const letters = ['A', 'B', 'C', 'D'];
      
      options.forEach((option, index) => {
          if (!option.querySelector('.option-letter')) {
              const letter = document.createElement('div');
              letter.className = 'option-letter';
              letter.textContent = letters[index] || (index + 1);
              option.insertBefore(letter, option.firstChild);
          }
      });
  }
  
  updateQuestionCounter() {
      const counter = document.querySelector('.question-counter');
      if (counter) {
          counter.textContent = `Question ${this.currentQuestionNumber}`;
      }
  }
  
  selectOption(optionElement) {
      if (this.isAnswering) return;
      
      // Remove previous selection
      document.querySelectorAll('.option-btn').forEach(btn => {
          btn.classList.remove('selected');
      });
      
      // Add selection to clicked option
      optionElement.classList.add('selected');
      optionElement.classList.add(this.isRoFMode ? 'rof' : 'regular');
      this.selectedOption = optionElement;
      
      // Play selection sound effect
      this.playSound('select');
      
      // Add selection animation
      this.animateSelection(optionElement);
      
      // Auto submit after selection (or you can add a submit button)
      setTimeout(() => {
          this.submitAnswer();
      }, 500);
  }
  
  animateSelection(element) {
      element.style.transform = 'translateY(-4px) scale(1.02)';
      setTimeout(() => {
          element.style.transform = 'translateY(-4px) scale(1)';
      }, 150);
  }
  
  submitAnswer() {
      if (!this.selectedOption || this.isAnswering) return;
      
      this.isAnswering = true;
      const selectedValue = this.selectedOption.dataset.value;
      
      // Disable all options
      document.querySelectorAll('.option-btn').forEach(btn => {
          btn.classList.add('disabled');
      });
      
      // Show loading state
      this.showLoadingState();
      
      // Submit to server
      this.sendAnswerToServer(selectedValue);
  }
  
  showLoadingState() {
      const overlay = document.createElement('div');
      overlay.className = 'loading-overlay';
      overlay.innerHTML = `
          <div class="loading-spinner"></div>
      `;
      document.body.appendChild(overlay);
  }
  
  hideLoadingState() {
      const overlay = document.querySelector('.loading-overlay');
      if (overlay) {
          overlay.remove();
      }
  }
  
  showAnswerFeedback(isCorrect, correctAnswer = null, explanation = null) {
      this.hideLoadingState();
      
      // Show correct/incorrect on options first
      this.showOptionFeedback(isCorrect, correctAnswer);
      
      // Then show modal feedback
      setTimeout(() => {
          this.showModalFeedback(isCorrect, correctAnswer, explanation);
      }, 800);
  }
  
  showOptionFeedback(isCorrect, correctAnswer) {
      const options = document.querySelectorAll('.option-btn');
      const selectedOption = this.selectedOption;
      
      // Mark selected option as correct or incorrect
      if (selectedOption) {
          selectedOption.classList.add(isCorrect ? 'correct' : 'incorrect');
      }
      
      // If answer was wrong, show the correct answer
      if (!isCorrect && correctAnswer) {
          options.forEach(option => {
              if (option.dataset.value === correctAnswer) {
                  option.classList.add('correct');
              }
          });
      }
      
      // Play sound effect
      this.playSound(isCorrect ? 'correct' : 'incorrect');
  }
  
  showModalFeedback(isCorrect, correctAnswer, explanation) {
      const feedbackOverlay = document.createElement('div');
      feedbackOverlay.className = 'answer-feedback';
      
      const feedbackContent = document.createElement('div');
      feedbackContent.className = `feedback-content ${isCorrect ? 'correct' : 'incorrect'}`;
      
      const icon = isCorrect ? '🎉' : (this.isRoFMode ? '💥' : '❌');
      const title = isCorrect ? 'Correct!' : 'Incorrect!';
      const subtitle = isCorrect 
          ? 'Great job! Keep it up!' 
          : 'Better luck next time!';
      
      feedbackContent.innerHTML = `
          <div class="feedback-icon">${icon}</div>
          <div class="feedback-text ${isCorrect ? 'correct' : 'incorrect'}">${title}</div>
          <div class="feedback-subtext">${subtitle}</div>
          ${correctAnswer && !isCorrect ? `<div class="correct-answer">Correct answer: ${correctAnswer}</div>` : ''}
          ${explanation ? `<div class="explanation" style="margin-top: 1rem; padding: 1rem; background: rgba(0,0,0,0.05); border-radius: 0.5rem; font-style: italic;">${explanation}</div>` : ''}
          <div style="margin-top: 1.5rem; font-size: 0.875rem; color: #6b7280;">
              Click anywhere or press ESC to continue
          </div>
      `;
      
      feedbackOverlay.appendChild(feedbackContent);
      document.body.appendChild(feedbackOverlay);
      
      // Show feedback with animation
      setTimeout(() => {
          feedbackOverlay.classList.add('show');
      }, 100);
      
      // Auto hide after 4 seconds
      setTimeout(() => {
          this.hideFeedback();
      }, 4000);
  }
  
  hideFeedback() {
      const feedback = document.querySelector('.answer-feedback');
      if (feedback) {
          feedback.classList.remove('show');
          setTimeout(() => {
              feedback.remove();
              this.resetForNextQuestion();
          }, 300);
      }
  }
  
  resetForNextQuestion() {
      this.isAnswering = false;
      this.selectedOption = null;
      
      // Re-enable options and remove states
      document.querySelectorAll('.option-btn').forEach(btn => {
          btn.classList.remove('selected', 'regular', 'rof', 'correct', 'incorrect', 'disabled');
      });
  }
  
  playSound(type) {
      // You can add sound effects here
      try {
          const audio = new Audio();
          switch(type) {
              case 'select':
                  // audio.src = '/assets/sounds/select.mp3';
                  console.log('🔊 Sound: Select');
                  break;
              case 'correct':
                  // audio.src = '/assets/sounds/correct.mp3';
                  console.log('🔊 Sound: Correct!');
                  break;
              case 'incorrect':
                  // audio.src = '/assets/sounds/incorrect.mp3';
                  console.log('🔊 Sound: Incorrect');
                  break;
          }
          // audio.play().catch(e => console.log('Sound play failed:', e));
      } catch (e) {
          console.log('Sound not available');
      }
  }
  
  sendAnswerToServer(selectedValue) {
      // This will be implemented in your PHP integration
      const sessionId = this.getSessionId();
      const questionId = this.getCurrentQuestionId();
      
      // For demo purposes, simulate server response
      setTimeout(() => {
          // Simulate random correct/incorrect for demo
          const isCorrect = Math.random() > 0.4; // 60% chance correct
          const correctAnswer = isCorrect ? selectedValue : ['A', 'B', 'C', 'D'][Math.floor(Math.random() * 4)];
          const explanation = isCorrect 
              ? 'Well done! You got it right.' 
              : 'The correct answer is ' + correctAnswer + '. Better luck next time!';
          
          this.showAnswerFeedback(isCorrect, correctAnswer, explanation);
      }, 1000);
      
      /* 
      // Real implementation:
      fetch('submit_answer.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              session_id: sessionId,
              question_id: questionId,
              selected_answer: selectedValue,
              quiz_type: this.isRoFMode ? 'rof' : 'regular'
          })
      })
      .then(response => response.json())
      .then(data => {
          this.showAnswerFeedback(
              data.is_correct, 
              data.correct_answer, 
              data.explanation
          );
      })
      .catch(error => {
          console.error('Error submitting answer:', error);
          this.hideLoadingState();
          this.resetForNextQuestion();
      });
      */
  }
  
  getSessionId() {
      // Get session ID from URL or data attribute
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get('session_id') || document.querySelector('[data-session-id]')?.dataset.sessionId;
  }
  
  getCurrentQuestionId() {
      // Get current question ID from data attribute
      return document.querySelector('[data-question-id]')?.dataset.questionId;
  }
  
  // Public methods for external use
  nextQuestion(questionData) {
      this.resetForNextQuestion();
      this.currentQuestionId = questionData.question_id;
      this.currentQuestionNumber = questionData.question_number || this.currentQuestionNumber + 1;
      
      // Update question display
      this.updateQuestionDisplay(questionData);
      
      // Update question counter
      this.updateQuestionCounter();
  }
  
  updateQuestionDisplay(questionData) {
      // Update question text
      const questionText = document.querySelector('.question-text');
      if (questionText) {
          questionText.textContent = questionData.question_text;
      }
      
      // Update options
      const options = document.querySelectorAll('.option-btn');
      const optionTexts = [
          questionData.option_a,
          questionData.option_b,
          questionData.option_c,
          questionData.option_d
      ];
      
      options.forEach((option, index) => {
          const textElement = option.querySelector('.option-text');
          if (textElement && optionTexts[index]) {
              textElement.textContent = optionTexts[index];
              option.dataset.value = ['A', 'B', 'C', 'D'][index];
          }
      });
      
      // Re-add option letters
      this.addOptionLetters();
      
      // Update question ID attribute
      const questionSection = document.querySelector('.question-section');
      if (questionSection) {
          questionSection.dataset.questionId = questionData.question_id;
      }
  }
  
  // Method to manually show feedback (for testing)
  testFeedback(isCorrect = true) {
      this.showAnswerFeedback(
          isCorrect, 
          isCorrect ? null : 'A', 
          isCorrect ? 'Perfect! You nailed it!' : 'Oops! The correct answer was A. Keep trying!'
      );
  }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  window.quizAnimations = new QuizAnimations();
  
  // Add keyboard shortcuts info to console
  console.log('🎮 Keyboard Shortcuts:');
  console.log('1-4: Select options A-D');
  console.log('↑↓←→: Navigate options');
  console.log('Enter/Space: Submit answer');
  console.log('ESC: Close feedback');
  console.log('');
  console.log('🧪 Test commands:');
  console.log('quizAnimations.testFeedback(true) - Test correct answer');
  console.log('quizAnimations.testFeedback(false) - Test incorrect answer');
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = QuizAnimations;
}