<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get user details with stats
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT up.lesson_id) as completed_lessons,
               COUNT(DISTINCT up2.challenge_id) as completed_challenges,
               COUNT(DISTINCT ub.badge_id) as earned_badges
        FROM users u
        LEFT JOIN user_progress up ON u.id = up.user_id AND up.status = 'completed' AND up.lesson_id IS NOT NULL
        LEFT JOIN user_progress up2 ON u.id = up2.user_id AND up2.status = 'completed' AND up2.challenge_id IS NOT NULL
        LEFT JOIN user_badges ub ON u.id = ub.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get user badges
    $stmt = $pdo->prepare("
        SELECT b.*, ub.earned_date
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
        ORDER BY ub.earned_date DESC
    ");
    $stmt->execute([$user_id]);
    $user_badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent activity
    $stmt = $pdo->prepare("
        SELECT 
            'lesson' as type,
            l.title as title,
            c.title as course_title,
            up.completion_date as date,
            up.points_earned as points
        FROM user_progress up
        JOIN lessons l ON up.lesson_id = l.id
        JOIN courses c ON l.course_id = c.id
        WHERE up.user_id = ? AND up.status = 'completed' AND up.lesson_id IS NOT NULL
        
        UNION ALL
        
        SELECT 
            'challenge' as type,
            ch.title as title,
            CONCAT(c.title, ' - ', l.title) as course_title,
            up.completion_date as date,
            up.points_earned as points
        FROM user_progress up
        JOIN challenges ch ON up.challenge_id = ch.id
        JOIN lessons l ON ch.lesson_id = l.id
        JOIN courses c ON l.course_id = c.id
        WHERE up.user_id = ? AND up.status = 'completed' AND up.challenge_id IS NOT NULL
        
        ORDER BY date DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id, $user_id]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error loading profile data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['full_name']); ?></title>
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

        .profile-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            flex-shrink: 0;
        }

        .profile-info h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .section h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .badge-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .badge-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .badge-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .badge-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .badge-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .badge-date {
            font-size: 0.8rem;
            color: #888;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 1rem;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }

        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .activity-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }

        .activity-points {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .level-progress {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .level-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .current-level {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
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

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .badges-grid {
                grid-template-columns: 1fr;
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
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Member since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $user['total_points']; ?></div>
                        <div class="stat-label">Total Points</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $user['completed_lessons']; ?></div>
                        <div class="stat-label">Lessons</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $user['completed_challenges']; ?></div>
                        <div class="stat-label">Challenges</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $user['earned_badges']; ?></div>
                        <div class="stat-label">Badges</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $user['streak_days']; ?></div>
                        <div class="stat-label">Day Streak</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div>
                <!-- Badges Section -->
                <div class="section">
                    <h2>🏆 Achievements</h2>
                    <?php if (!empty($user_badges)): ?>
                        <div class="badges-grid">
                            <?php foreach ($user_badges as $badge): ?>
                                <div class="badge-card">
                                    <div class="badge-icon"><?php echo $badge['icon']; ?></div>
                                    <div class="badge-name"><?php echo htmlspecialchars($badge['name']); ?></div>
                                    <div class="badge-description"><?php echo htmlspecialchars($badge['description']); ?></div>
                                    <div class="badge-date">Earned: <?php echo date('M j, Y', strtotime($badge['earned_date'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No badges yet</h3>
                            <p>Complete lessons and challenges to earn your first badge!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="section">
                    <h2>📈 Recent Activity</h2>
                    <?php if (!empty($recent_activity)): ?>
                        <ul class="activity-list">
                            <?php foreach ($recent_activity as $activity): ?>
                                <li class="activity-item">
                                    <div class="activity-title">
                                        <?php echo $activity['type'] == 'lesson' ? '📚' : '💻'; ?>
                                        <?php echo htmlspecialchars($activity['title']); ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span><?php echo htmlspecialchars($activity['course_title']); ?></span>
                                        <div>
                                            <span class="activity-points">+<?php echo $activity['points']; ?> pts</span>
                                            <span style="margin-left: 1rem;"><?php echo date('M j, Y', strtotime($activity['date'])); ?></span>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No activity yet</h3>
                            <p>Start learning to see your progress here!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <!-- Level Progress -->
                <div class="section">
                    <h2>🎯 Level Progress</h2>
                    <div class="level-progress">
                        <div class="level-info">
                            <span class="current-level">Level <?php echo $user['current_level']; ?></span>
                            <span><?php echo $user['total_points']; ?> / <?php echo $user['current_level'] * 100; ?> pts</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min(100, ($user['total_points'] % 100)); ?>%"></div>
                        </div>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                            <?php echo max(0, ($user['current_level'] * 100) - $user['total_points']); ?> points to next level
                        </p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="section">
                    <h2>📊 Quick Stats</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                            <span>🎯 Accuracy Rate</span>
                            <strong>85%</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                            <span>⏱️ Avg. Time per Challenge</span>
                            <strong>12 min</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                            <span>🔥 Current Streak</span>
                            <strong><?php echo $user['streak_days']; ?> days</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                            <span>📅 Last Active</span>
                            <strong><?php echo $user['last_activity'] ? date('M j', strtotime($user['last_activity'])) : 'Today'; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive animations
        document.querySelectorAll('.badge-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Animate progress bar on load
        window.addEventListener('load', function() {
            const progressBar = document.querySelector('.progress-fill');
            if (progressBar) {
                const width = progressBar.style.width;
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.width = width;
                }, 500);
            }
        });
    </script>
</body>
</html>
