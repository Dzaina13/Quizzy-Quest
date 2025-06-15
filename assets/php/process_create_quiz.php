<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../../login.php?error=not_logged_in");
    exit();
}

$logged_user_id = $_SESSION['user_id'];

// Include database connection
include 'koneksi_db.php';

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../create_quiz.php?error=invalid_method");
    exit();
}

// DEBUG: Print POST data to see what's being sent
echo "<pre>POST Data Debug:</pre>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Get form data
$quiz_title = trim($_POST['quiz_title'] ?? '');
$quiz_description = trim($_POST['quiz_description'] ?? '');
$quiz_type = $_POST['quiz_type'] ?? '';
$created_by = $logged_user_id; // From session, not POST

// Validate required fields
if (empty($quiz_title)) {
    header("Location: ../../create_quiz.php?error=empty_title&description=" . urlencode($quiz_description) . "&quiz_type=" . urlencode($quiz_type));
    exit();
}

if (empty($quiz_description)) {
    header("Location: ../../create_quiz.php?error=empty_description&title=" . urlencode($quiz_title) . "&quiz_type=" . urlencode($quiz_type));
    exit();
}

if (empty($quiz_type)) {
    header("Location: ../../create_quiz.php?error=empty_type&title=" . urlencode($quiz_title) . "&description=" . urlencode($quiz_description));
    exit();
}

// Insert quiz into database
try {
    $sql = "INSERT INTO quizzes (title, description, quiz_type, created_by, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $koneksi->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $koneksi->error);
    }
    
    $stmt->bind_param("sssi", $quiz_title, $quiz_description, $quiz_type, $created_by);
    
    if ($stmt->execute()) {
        $quiz_id = $koneksi->insert_id;
        echo "<p>Quiz created successfully with ID: $quiz_id</p>";
        
        // 🔥 ROUTING BERDASARKAN QUIZ TYPE - INI YANG DIPERBAIKI!
        if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
            echo "<p>Processing questions for quiz type: $quiz_type</p>";
            
            if ($quiz_type === 'normal') {
                // Normal quiz → table 'questions'
                insertNormalQuestions($koneksi, $quiz_id, $_POST['questions']);
                
            } elseif ($quiz_type === 'rof') {
                // ROF quiz → table 'rof_questions'
                insertROFQuestions($koneksi, $quiz_id, $_POST['questions'], $created_by);
                
            } elseif ($quiz_type === 'decision_maker') {
                // Decision Maker → table 'decision_maker_questions'
                insertDecisionMakerQuestions($koneksi, $quiz_id, $_POST['questions'], $created_by);
                
            } else {
                echo "<p>⚠️ Unknown quiz type: $quiz_type</p>";
            }
        } else {
            echo "<p>⚠️ No questions data found in POST</p>";
        }
        
        $stmt->close();
        
        // Comment out redirect for debugging
        // header("Location: ../../create_quiz.php?success=created&quiz_id=" . $quiz_id);
        // exit();
        
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Quiz creation error: " . $e->getMessage());
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
    // header("Location: ../../create_quiz.php?error=database_error&title=" . urlencode($quiz_title) . "&description=" . urlencode($quiz_description) . "&quiz_type=" . urlencode($quiz_type));
    // exit();
}

// 🔥 FUNCTION UNTUK NORMAL QUESTIONS
function insertNormalQuestions($koneksi, $quiz_id, $questions) {
    echo "<p>📝 Inserting Normal Questions...</p>";
    
    $question_sql = "INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $question_stmt = $koneksi->prepare($question_sql);
    
    if ($question_stmt) {
        $question_count = 0;
        foreach ($questions as $index => $question) {
            echo "<p>Processing normal question $index:</p>";
            
            $question_text = trim($question['question_text'] ?? '');
            $option_a = trim($question['option_a'] ?? '');
            $option_b = trim($question['option_b'] ?? '');
            $option_c = trim($question['option_c'] ?? '');
            $option_d = trim($question['option_d'] ?? '');
            $correct_answer = $question['correct_answer'] ?? '';
            $explanation = trim($question['explanation'] ?? '');
            
            echo "<pre>";
            echo "Question Text: '$question_text'\n";
            echo "Option A: '$option_a'\n";
            echo "Option B: '$option_b'\n";
            echo "Option C: '$option_c'\n";
            echo "Option D: '$option_d'\n";
            echo "Correct Answer: '$correct_answer'\n";
            echo "Explanation: '$explanation'\n";
            echo "</pre>";
            
            if (!empty($question_text) && !empty($option_a) && !empty($option_b)) {
                $question_stmt->bind_param("isssssss", $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation);
                
                if ($question_stmt->execute()) {
                    $question_count++;
                    echo "<p>✅ Normal question $index inserted successfully!</p>";
                } else {
                    echo "<p>❌ Error inserting normal question $index: " . $question_stmt->error . "</p>";
                }
            } else {
                echo "<p>⚠️ Normal question $index skipped - missing required fields</p>";
            }
        }
        
        echo "<p>Total normal questions inserted: $question_count</p>";
        $question_stmt->close();
    } else {
        echo "<p>❌ Error preparing normal question statement: " . $koneksi->error . "</p>";
    }
}

// 🔥 FUNCTION UNTUK ROF QUESTIONS
function insertROFQuestions($koneksi, $quiz_id, $questions, $created_by) {
    echo "<p>✅ Inserting ROF Questions...</p>";
    
    // ROF questions masuk ke table rof_questions
    $rof_sql = "INSERT INTO rof_questions (rof_session_id, question_text, correct_answer, created_by) VALUES (?, ?, ?, ?)";
    $rof_stmt = $koneksi->prepare($rof_sql);
    
    if ($rof_stmt) {
        $question_count = 0;
        foreach ($questions as $index => $question) {
            echo "<p>Processing ROF question $index:</p>";
            
            $question_text = trim($question['question_text'] ?? '');
            $correct_answer = $question['correct_answer'] ?? '';
            
            echo "<pre>";
            echo "ROF Question Text: '$question_text'\n";
            echo "ROF Correct Answer: '$correct_answer'\n";
            echo "</pre>";
            
            if (!empty($question_text) && !empty($correct_answer)) {
                // Pastikan correct_answer adalah T atau F
                if ($correct_answer === 'T' || $correct_answer === 'F') {
                    $rof_stmt->bind_param("issi", $quiz_id, $question_text, $correct_answer, $created_by);
                    
                    if ($rof_stmt->execute()) {
                        $question_count++;
                        echo "<p>✅ ROF question $index inserted successfully into rof_questions table!</p>";
                    } else {
                        echo "<p>❌ Error inserting ROF question $index: " . $rof_stmt->error . "</p>";
                    }
                } else {
                    echo "<p>❌ ROF question $index: Invalid correct_answer '$correct_answer' (must be T or F)</p>";
                }
            } else {
                echo "<p>⚠️ ROF question $index skipped - missing required fields</p>";
            }
        }
        
        echo "<p>Total ROF questions inserted: $question_count</p>";
        $rof_stmt->close();
    } else {
        echo "<p>❌ Error preparing ROF question statement: " . $koneksi->error . "</p>";
    }
}

// 🔥 FUNCTION UNTUK DECISION MAKER QUESTIONS
function insertDecisionMakerQuestions($koneksi, $quiz_id, $questions, $created_by) {
    echo "<p>💭 Inserting Decision Maker Questions...</p>";
    
    // Decision Maker questions masuk ke table decision_maker_questions
    // Sesuaikan dengan struktur table yang ada
    $dm_sql = "INSERT INTO decision_maker_questions (quiz_id, question_text, guidance, created_by) VALUES (?, ?, ?, ?)";
    $dm_stmt = $koneksi->prepare($dm_sql);
    
    if ($dm_stmt) {
        $question_count = 0;
        foreach ($questions as $index => $question) {
            echo "<p>Processing Decision Maker question $index:</p>";
            
            $question_text = trim($question['question_text'] ?? '');
            $guidance = trim($question['explanation'] ?? ''); // explanation jadi guidance
            
            echo "<pre>";
            echo "DM Question Text: '$question_text'\n";
            echo "DM Guidance: '$guidance'\n";
            echo "</pre>";
            
            if (!empty($question_text)) {
                $dm_stmt->bind_param("issi", $quiz_id, $question_text, $guidance, $created_by);
                
                if ($dm_stmt->execute()) {
                    $question_count++;
                    echo "<p>✅ Decision Maker question $index inserted successfully into decision_maker_questions table!</p>";
                } else {
                    echo "<p>❌ Error inserting Decision Maker question $index: " . $dm_stmt->error . "</p>";
                }
            } else {
                echo "<p>⚠️ Decision Maker question $index skipped - missing question text</p>";
            }
        }
        
        echo "<p>Total Decision Maker questions inserted: $question_count</p>";
        $dm_stmt->close();
    } else {
        echo "<p>❌ Error preparing Decision Maker question statement: " . $koneksi->error . "</p>";
    }
}

echo "<p>Debug mode - script completed</p>";
?>