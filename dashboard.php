<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user stats
try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT up.lesson_id) as completed_lessons,
               COUNT(DISTINCT ub.badge_id) as earned_badges
        FROM users u
        LEFT JOIN user_progress up ON u.id = up.user_id AND up.status = 'completed'
        LEFT JOIN user_badges ub ON u.id = ub.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent courses
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE is_active = 1 ORDER BY id LIMIT 6");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user's recent badges
    $stmt = $pdo->prepare("
        SELECT b.name, b.description, b.icon, ub.earned_date
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_date DESC
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error loading dashboard data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Grasshopper Clone</title>
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .welcome-section h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            color: #666;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .course-card {
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .course-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }

        .course-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .course-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #888;
        }

        .difficulty {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .difficulty.beginner {
            background: #e8f5e8;
            color: #2d5a2d;
        }

        .difficulty.intermediate {
            background: #fff3cd;
            color: #856404;
        }

        .difficulty.advanced {
            background: #f8d7da;
            color: #721c24;
        }

        .badges-section {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
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
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</span>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
            <p>Ready to continue your coding journey? Let's build something amazing today!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-number"><?php echo $user['total_points']; ?></div>
                <div class="stat-label">Total Points</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?php echo $user['completed_lessons']; ?></div>
                <div class="stat-label">Lessons Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-number"><?php echo $user['earned_badges']; ?></div>
                <div class="stat-label">Badges Earned</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-number"><?php echo $user['streak_days']; ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
        </div>

        <?php if (!empty($recent_badges)): ?>
        <div class="section">
            <h2>Recent Achievements 🎉</h2>
            <div class="badges-section">
                <?php foreach ($recent_badges as $badge): ?>
                    <div class="badge">
                        <span><?php echo $badge['icon']; ?></span>
                        <span><?php echo htmlspecialchars($badge['name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>Available Courses 📖</h2>
            <div class="courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card" onclick="redirectTo('lessons.php?course_id=<?php echo $course['id']; ?>')">
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p><?php echo htmlspecialchars($course['description']); ?></p>
                        <div class="course-meta">
                            <span class="difficulty <?php echo $course['difficulty_level']; ?>">
                                <?php echo ucfirst($course['difficulty_level']); ?>
                            </span>
                            <span><?php echo $course['total_lessons']; ?> lessons</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <h2>Quick Actions 🚀</h2>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="lessons.php" class="btn">Start Learning</a>
                <a href="coding-challenge.php" class="btn">Practice Coding</a>
                <a href="profile.php" class="btn">View Profile</a>
            </div>
        </div>
    </div>

    <script>
        function redirectTo(url) {
            window.location.href = url;
        }

        // Add some interactive animations
        document.querySelectorAll('.stat-card').forEach(card => {
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
