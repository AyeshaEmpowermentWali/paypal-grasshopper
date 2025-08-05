<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 1;

try {
    // Get course info
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND is_active = 1");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        header('Location: dashboard.php');
        exit();
    }

    // Get lessons for this course
    $stmt = $pdo->prepare("
        SELECT l.*, 
               COALESCE(up.status, 'not_started') as progress_status,
               up.points_earned
        FROM lessons l
        LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
        WHERE l.course_id = ? AND l.is_active = 1
        ORDER BY l.lesson_order
    ");
    $stmt->execute([$user_id, $course_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all courses for navigation
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE is_active = 1 ORDER BY id");
    $stmt->execute();
    $all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error loading lessons.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lessons - <?php echo htmlspecialchars($course['title']); ?></title>
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
            max-width: 1200px;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        .course-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .course-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .course-header p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .course-meta {
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .course-selector {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .course-selector select {
            padding: 10px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }

        .lessons-grid {
            display: grid;
            gap: 1.5rem;
        }

        .lesson-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .lesson-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .lesson-card.completed {
            border-left: 5px solid #28a745;
        }

        .lesson-card.in_progress {
            border-left: 5px solid #ffc107;
        }

        .lesson-card.not_started {
            border-left: 5px solid #6c757d;
        }

        .lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .lesson-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .lesson-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .lesson-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .lesson-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.in_progress {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.not_started {
            background: #f8f9fa;
            color: #6c757d;
        }

        .difficulty-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .difficulty-badge.easy {
            background: #e8f5e8;
            color: #2d5a2d;
        }

        .difficulty-badge.medium {
            background: #fff3cd;
            color: #856404;
        }

        .difficulty-badge.hard {
            background: #f8d7da;
            color: #721c24;
        }

        .points-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 1rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
            color: #333;
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
            }

            .course-header h1 {
                font-size: 2rem;
            }

            .course-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
        <div class="course-header">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p><?php echo htmlspecialchars($course['description']); ?></p>
            <div class="course-meta">
                <div class="meta-item">
                    <span>📚</span>
                    <span><?php echo count($lessons); ?> Lessons</span>
                </div>
                <div class="meta-item">
                    <span>⭐</span>
                    <span><?php echo ucfirst($course['difficulty_level']); ?></span>
                </div>
                <div class="meta-item">
                    <span>🎯</span>
                    <span>Interactive Learning</span>
                </div>
            </div>
        </div>

        <div class="course-selector">
            <label for="course-select">Switch Course: </label>
            <select id="course-select" onchange="switchCourse(this.value)">
                <?php foreach ($all_courses as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $course_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (empty($lessons)): ?>
            <div class="empty-state">
                <h3>No lessons available</h3>
                <p>This course is currently being prepared. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="lessons-grid">
                <?php 
                $completed_count = 0;
                foreach ($lessons as $lesson): 
                    if ($lesson['progress_status'] == 'completed') $completed_count++;
                ?>
                    <div class="lesson-card <?php echo $lesson['progress_status']; ?>" 
                         onclick="startLesson(<?php echo $lesson['id']; ?>)">
                        <div class="lesson-header">
                            <div>
                                <h3 class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></h3>
                                <div class="status-badge <?php echo $lesson['progress_status']; ?>">
                                    <?php 
                                    switch($lesson['progress_status']) {
                                        case 'completed': echo '✅ Completed'; break;
                                        case 'in_progress': echo '🔄 In Progress'; break;
                                        default: echo '⭕ Not Started'; break;
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="points-display">
                                <?php echo $lesson['points_earned'] ?: $lesson['points_reward']; ?> pts
                            </div>
                        </div>
                        
                        <p class="lesson-description"><?php echo htmlspecialchars($lesson['description']); ?></p>
                        
                        <div class="lesson-meta">
                            <div class="lesson-stats">
                                <span>⏱️ <?php echo $lesson['estimated_time']; ?> min</span>
                                <span class="difficulty-badge <?php echo $lesson['difficulty']; ?>">
                                    <?php echo ucfirst($lesson['difficulty']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo count($lessons) > 0 ? ($completed_count / count($lessons)) * 100 : 0; ?>%"></div>
            </div>
            <p style="text-align: center; margin-top: 1rem; color: #666;">
                Progress: <?php echo $completed_count; ?>/<?php echo count($lessons); ?> lessons completed
            </p>
        <?php endif; ?>
    </div>

    <script>
        function switchCourse(courseId) {
            window.location.href = 'lessons.php?course_id=' + courseId;
        }

        function startLesson(lessonId) {
            window.location.href = 'coding-challenge.php?lesson_id=' + lessonId;
        }

        // Add hover effects
        document.querySelectorAll('.lesson-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>
