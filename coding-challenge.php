<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 1;

try {
    // Get lesson info
    $stmt = $pdo->prepare("
        SELECT l.*, c.title as course_title 
        FROM lessons l 
        JOIN courses c ON l.course_id = c.id 
        WHERE l.id = ? AND l.is_active = 1
    ");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) {
        header('Location: lessons.php');
        exit();
    }

    // Get challenges for this lesson
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COALESCE(up.status, 'not_started') as progress_status
        FROM challenges c
        LEFT JOIN user_progress up ON c.id = up.challenge_id AND up.user_id = ?
        WHERE c.lesson_id = ?
        ORDER BY c.challenge_order
    ");
    $stmt->execute([$user_id, $lesson_id]);
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get current challenge (first incomplete or first one)
    $current_challenge = null;
    foreach ($challenges as $challenge) {
        if ($challenge['progress_status'] != 'completed') {
            $current_challenge = $challenge;
            break;
        }
    }
    if (!$current_challenge && !empty($challenges)) {
        $current_challenge = $challenges[0];
    }

} catch (PDOException $e) {
    $error = "Error loading challenge data.";
}

// Handle code submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_code'])) {
    $challenge_id = (int)$_POST['challenge_id'];
    $user_code = $_POST['user_code'];
    
    try {
        // Get challenge details
        $stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
        $stmt->execute([$challenge_id]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($challenge) {
            // Simple code validation (in real app, you'd use a proper code execution sandbox)
            $is_correct = trim($user_code) === trim($challenge['solution_code']);
            
            if ($is_correct) {
                // Update progress
                $stmt = $pdo->prepare("
                    INSERT INTO user_progress (user_id, course_id, lesson_id, challenge_id, status, completion_date, points_earned) 
                    VALUES (?, ?, ?, ?, 'completed', NOW(), ?)
                    ON DUPLICATE KEY UPDATE 
                    status = 'completed', completion_date = NOW(), points_earned = ?
                ");
                $stmt->execute([$user_id, $lesson['course_id'], $lesson_id, $challenge_id, $challenge['points_reward'], $challenge['points_reward']]);
                
                // Update user points
                $stmt = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
                $stmt->execute([$challenge['points_reward'], $user_id]);
                
                $success_message = "Congratulations! You solved the challenge and earned " . $challenge['points_reward'] . " points!";
            } else {
                $error_message = "Not quite right. Try again!";
            }
        }
    } catch (PDOException $e) {
        $error_message = "Error submitting code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coding Challenge - <?php echo htmlspecialchars($lesson['title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .nav-menu a:hover {
            opacity: 0.8;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            height: calc(100vh - 120px);
        }

        .challenge-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }

        .panel-header h2 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .panel-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .panel-content {
            padding: 2rem;
            flex: 1;
            overflow-y: auto;
        }

        .challenge-info {
            margin-bottom: 2rem;
        }

        .challenge-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .challenge-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .challenge-instructions {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 1.5rem;
        }

        .challenge-instructions h4 {
            color: #333;
            margin-bottom: 1rem;
        }

        .challenge-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .meta-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .difficulty-easy {
            background: #e8f5e8;
            color: #2d5a2d;
        }

        .difficulty-medium {
            background: #fff3cd;
            color: #856404;
        }

        .difficulty-hard {
            background: #f8d7da;
            color: #721c24;
        }

        .points-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .code-panel {
            display: flex;
            flex-direction: column;
        }

        .code-editor {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .editor-tabs {
            display: flex;
            gap: 0.5rem;
        }

        .tab {
            padding: 8px 16px;
            background: #e9ecef;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .tab.active {
            background: #667eea;
            color: white;
        }

        .code-textarea {
            flex: 1;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            resize: none;
            background: #f8f9fa;
            line-height: 1.5;
            min-height: 300px;
        }

        .code-textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        .code-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-hint {
            background: #ffc107;
            color: #212529;
        }

        .btn-hint:hover {
            background: #e0a800;
        }

        .output-section {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #28a745;
        }

        .output-section h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .output-content {
            font-family: 'Courier New', monospace;
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            white-space: pre-wrap;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .hints-section {
            margin-top: 1rem;
            padding: 1rem;
            background: #fff3cd;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
            display: none;
        }

        .hints-section.show {
            display: block;
        }

        .challenge-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .challenge-panel {
                order: 1;
            }
            
            .code-panel {
                order: 2;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .container {
                padding: 1rem 10px;
                gap: 1rem;
            }

            .code-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">🦗 Grasshopper</div>
            <nav class="nav-menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="lessons.php">Lessons</a>
                <a href="coding-challenge.php">Challenges</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Challenge Instructions Panel -->
        <div class="challenge-panel">
            <div class="panel-header">
                <h2><?php echo htmlspecialchars($lesson['course_title']); ?></h2>
                <p><?php echo htmlspecialchars($lesson['title']); ?></p>
            </div>
            
            <div class="panel-content">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if ($current_challenge): ?>
                    <div class="challenge-info">
                        <h3 class="challenge-title"><?php echo htmlspecialchars($current_challenge['title']); ?></h3>
                        
                        <div class="challenge-meta">
                            <span class="meta-badge difficulty-<?php echo $current_challenge['difficulty']; ?>">
                                <?php echo ucfirst($current_challenge['difficulty']); ?>
                            </span>
                            <span class="meta-badge points-badge">
                                <?php echo $current_challenge['points_reward']; ?> points
                            </span>
                        </div>
                        
                        <p class="challenge-description"><?php echo htmlspecialchars($current_challenge['description']); ?></p>
                        
                        <div class="challenge-instructions">
                            <h4>Instructions:</h4>
                            <p><?php echo nl2br(htmlspecialchars($current_challenge['instructions'])); ?></p>
                        </div>

                        <?php if ($current_challenge['expected_output']): ?>
                            <div class="output-section">
                                <h4>Expected Output:</h4>
                                <div class="output-content"><?php echo htmlspecialchars($current_challenge['expected_output']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($current_challenge['hints']): ?>
                            <button class="btn btn-hint" onclick="toggleHints()">💡 Show Hints</button>
                            <div class="hints-section" id="hints-section">
                                <h4>Hints:</h4>
                                <?php 
                                $hints = json_decode($current_challenge['hints'], true);
                                if ($hints && is_array($hints)) {
                                    foreach ($hints as $hint) {
                                        echo "<p>• " . htmlspecialchars($hint) . "</p>";
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="challenge-info">
                        <h3>No challenges available</h3>
                        <p>This lesson doesn't have any coding challenges yet.</p>
                    </div>
                <?php endif; ?>

                <div class="challenge-navigation">
                    <a href="lessons.php?course_id=<?php echo $lesson['course_id']; ?>" class="btn btn-secondary">← Back to Lessons</a>
                    <?php if (count($challenges) > 1): ?>
                        <span><?php echo array_search($current_challenge, $challenges) + 1; ?> of <?php echo count($challenges); ?> challenges</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Code Editor Panel -->
        <div class="challenge-panel code-panel">
            <div class="panel-header">
                <h2>Code Editor</h2>
                <p>Write your solution below</p>
            </div>
            
            <div class="panel-content">
                <?php if ($current_challenge): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="challenge_id" value="<?php echo $current_challenge['id']; ?>">
                        
                        <div class="code-editor">
                            <div class="editor-header">
                                <div class="editor-tabs">
                                    <button type="button" class="tab active">JavaScript</button>
                                </div>
                            </div>
                            
                            <textarea name="user_code" class="code-textarea" placeholder="Write your code here..."><?php echo htmlspecialchars($current_challenge['starter_code']); ?></textarea>
                            
                            <div class="code-actions">
                                <button type="submit" name="submit_code" class="btn btn-primary">🚀 Run Code</button>
                                <button type="button" class="btn btn-secondary" onclick="resetCode()">🔄 Reset</button>
                                <button type="button" class="btn btn-hint" onclick="showSolution()">👁️ Show Solution</button>
                            </div>
                        </div>
                    </form>

                    <div class="output-section">
                        <h4>Console Output:</h4>
                        <div class="output-content" id="console-output">Ready to run your code...</div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <h3>No coding challenge available</h3>
                        <p>Go back to lessons to find challenges to solve.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const starterCode = <?php echo json_encode($current_challenge['starter_code'] ?? ''); ?>;
        const solutionCode = <?php echo json_encode($current_challenge['solution_code'] ?? ''); ?>;

        function toggleHints() {
            const hintsSection = document.getElementById('hints-section');
            hintsSection.classList.toggle('show');
        }

        function resetCode() {
            document.querySelector('.code-textarea').value = starterCode;
        }

        function showSolution() {
            if (confirm('Are you sure you want to see the solution? This will replace your current code.')) {
                document.querySelector('.code-textarea').value = solutionCode;
            }
        }

        // Simple code execution simulation (for demo purposes)
        function runCode() {
            const code = document.querySelector('.code-textarea').value;
            const output = document.getElementById('console-output');
            
            try {
                // This is a very basic simulation - in a real app you'd use a proper sandbox
                let result = '';
                
                // Capture console.log outputs
                const originalLog = console.log;
                console.log = function(...args) {
                    result += args.join(' ') + '\n';
                };
                
                // Execute the code (WARNING: This is unsafe in production!)
                eval(code);
                
                // Restore console.log
                console.log = originalLog;
                
                output.textContent = result || 'Code executed successfully (no output)';
            } catch (error) {
                output.textContent = 'Error: ' + error.message;
            }
        }

        // Add syntax highlighting effect
        document.querySelector('.code-textarea').addEventListener('input', function() {
            // Simple syntax highlighting could be added here
        });

        // Auto-resize textarea
        document.querySelector('.code-textarea').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(300, this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>
