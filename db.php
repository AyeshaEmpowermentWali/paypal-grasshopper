<?php
// Database configuration for hosting service
// Your credentials suggest this is a hosted database, not local

// Try different hosting patterns for your database
$hosting_patterns = [
    'localhost',
    '127.0.0.1',
    'mysql.hostinger.com',
    'mysql.000webhost.com',
    'sql.freehosting.com',
    'mysql.byethost.com',
    'mysql.freehostia.com',
    'mysql.awardspace.com',
    'mysql.x10hosting.com',
    'db4free.net',
    'remotemysql.com',
    'sql.freedb.tech',
    'mysql-' . substr('ugrj543f7lree', 0, 8) . '.alwaysdata.net',
    'mysql-' . substr('dbpr8vdojr6oso', 0, 8) . '.alwaysdata.net'
];

// Your database credentials
$db_credentials = [
    'dbname' => 'dbpr8vdojr6oso',
    'username' => 'ugrj543f7lree',
    'password' => 'cgmq43woifko'
];

class Database {
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private $working_host;

    public function __construct($credentials) {
        $this->db_name = $credentials['dbname'];
        $this->username = $credentials['username'];
        $this->password = $credentials['password'];
    }

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        global $hosting_patterns;
        
        // Try to find working host
        foreach ($hosting_patterns as $host) {
            if ($this->tryConnection($host)) {
                $this->working_host = $host;
                break;
            }
        }

        // If no host worked, try with different ports
        if ($this->conn === null) {
            $ports = [3306, 3307, 3308, 5432];
            foreach ($hosting_patterns as $host) {
                foreach ($ports as $port) {
                    if ($this->tryConnection($host, $port)) {
                        $this->working_host = $host . ':' . $port;
                        break 2;
                    }
                }
            }
        }

        // If still no connection, show detailed error
        if ($this->conn === null) {
            $this->showDetailedError();
        }

        // Create tables if connection successful
        if ($this->conn !== null) {
            $this->setupDatabase();
        }

        return $this->conn;
    }

    private function tryConnection($host, $port = 3306) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5, // 5 second timeout
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Test connection with a simple query
            $stmt = $this->conn->query("SELECT 1");
            $result = $stmt->fetch();
            
            return $result !== false;
            
        } catch (PDOException $e) {
            // Try to create database if it doesn't exist
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $dsn_no_db = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $temp_conn = new PDO($dsn_no_db, $this->username, $this->password, $options);
                    $temp_conn->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Try connecting to the created database
                    $dsn = "mysql:host={$host};port={$port};dbname={$this->db_name};charset=utf8mb4";
                    $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                    
                    $stmt = $this->conn->query("SELECT 1");
                    return $stmt->fetch() !== false;
                    
                } catch (PDOException $create_error) {
                    return false;
                }
            }
            return false;
        }
    }

    private function showDetailedError() {
        $error_html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Connection Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .solution { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .info { background: #d1ecf1; color: #0c5460; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; border-left: 4px solid #007bff; }
                .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
                .btn:hover { background: #0056b3; }
                ul { margin-left: 20px; }
                li { margin: 8px 0; }
                h3 { margin-top: 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🦗 Database Connection Failed</h1>
                
                <div class="error">
                    <h3>❌ Could not connect to database</h3>
                    <p>Tried multiple hosts and ports but none worked with your credentials.</p>
                </div>
                
                <div class="info">
                    <h3>📋 Your Database Information:</h3>
                    <div class="code">
                        Database Name: ' . $this->db_name . '<br>
                        Username: ' . $this->username . '<br>
                        Password: ' . (empty($this->password) ? '(empty)' : '(provided)') . '
                    </div>
                </div>
                
                <div class="solution">
                    <h3>🔧 Quick Fix Instructions:</h3>
                    <p><strong>You need to find your correct database host!</strong></p>
                    
                    <h4>Step 1: Check Your Hosting Provider</h4>
                    <ul>
                        <li><strong>Hostinger:</strong> Usually <code>mysql.hostinger.com</code></li>
                        <li><strong>000webhost:</strong> Usually <code>mysql.000webhost.com</code></li>
                        <li><strong>InfinityFree:</strong> Usually <code>sql200.infinityfree.com</code></li>
                        <li><strong>Byethost:</strong> Usually <code>mysql.byethost.com</code></li>
                        <li><strong>AwardSpace:</strong> Usually <code>mysql.awardspace.com</code></li>
                        <li><strong>AlwaysData:</strong> Usually <code>mysql-[username].alwaysdata.net</code></li>
                    </ul>
                    
                    <h4>Step 2: Find Your Database Host</h4>
                    <ul>
                        <li>Login to your hosting control panel (cPanel/hPanel)</li>
                        <li>Go to "MySQL Databases" or "Database" section</li>
                        <li>Look for "Database Host" or "MySQL Hostname"</li>
                        <li>Copy the exact hostname</li>
                    </ul>
                    
                    <h4>Step 3: Update db.php</h4>
                    <p>Replace the host in db.php with your actual database host:</p>
                    <div class="code">
                        // Find this line in db.php and update it:<br>
                        $hosting_patterns = [<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;\'<strong>YOUR_ACTUAL_HOST_HERE</strong>\',  // Add your real host here<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;\'localhost\',<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;// ... other hosts<br>
                        ];
                    </div>
                </div>
                
                <div class="info">
                    <h3>🔍 Common Database Hosts by Provider:</h3>
                    <div class="code">
                        Hostinger: mysql.hostinger.com<br>
                        000webhost: mysql.000webhost.com<br>
                        InfinityFree: sql200.infinityfree.com (or similar)<br>
                        Byethost: mysql.byethost.com<br>
                        FreeHostia: mysql.freehostia.com<br>
                        AwardSpace: mysql.awardspace.com<br>
                        AlwaysData: mysql-[username].alwaysdata.net<br>
                        RemoteMySQL: remotemysql.com<br>
                        DB4Free: db4free.net
                    </div>
                </div>
                
                <div class="solution">
                    <h3>💡 Alternative Solution:</h3>
                    <p>If you can\'t find your database host, create a simple PHP file with this code:</p>
                    <div class="code">
                        &lt;?php<br>
                        phpinfo();<br>
                        ?&gt;
                    </div>
                    <p>Upload it to your server and run it. Look for MySQL/Database information.</p>
                </div>
                
                <a href="find-host.php" class="btn">🔍 Auto-Find Database Host</a>
                <a href="manual-config.php" class="btn">⚙️ Manual Configuration</a>
            </div>
        </body>
        </html>';
        
        die($error_html);
    }

    private function setupDatabase() {
        // Create tables
        $this->createTables();
        $this->insertSampleData();
    }

    private function createTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
                total_points INT DEFAULT 0,
                current_level INT DEFAULT 1,
                streak_days INT DEFAULT 0,
                last_activity DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
                total_lessons INT DEFAULT 0,
                course_image VARCHAR(255),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS lessons (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                content LONGTEXT,
                lesson_order INT,
                points_reward INT DEFAULT 10,
                difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'easy',
                estimated_time INT DEFAULT 5,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS challenges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lesson_id INT,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                instructions LONGTEXT,
                starter_code TEXT,
                solution_code TEXT,
                expected_output TEXT,
                hints TEXT,
                difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'easy',
                points_reward INT DEFAULT 15,
                challenge_order INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS user_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                course_id INT,
                lesson_id INT,
                challenge_id INT NULL,
                status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
                completion_date TIMESTAMP NULL,
                attempts INT DEFAULT 0,
                points_earned INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS badges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                description TEXT,
                icon VARCHAR(100),
                badge_type ENUM('achievement', 'streak', 'completion', 'special') DEFAULT 'achievement',
                requirement_value INT,
                points_required INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            "CREATE TABLE IF NOT EXISTS user_badges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                badge_id INT,
                earned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];

        foreach ($tables as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Table creation error: " . $e->getMessage());
            }
        }
    }

    private function insertSampleData() {
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) FROM courses");
            $count = $stmt->fetchColumn();
            
            if ($count == 0) {
                $this->conn->exec("
                    INSERT INTO courses (title, description, difficulty_level, total_lessons) VALUES
                    ('JavaScript Fundamentals', 'Learn the basics of JavaScript programming', 'beginner', 10),
                    ('HTML & CSS Basics', 'Master the fundamentals of web development', 'beginner', 8),
                    ('Python for Beginners', 'Start your programming journey with Python', 'beginner', 12)
                ");

                $this->conn->exec("
                    INSERT INTO lessons (course_id, title, description, lesson_order, points_reward) VALUES
                    (1, 'Variables and Data Types', 'Learn about JavaScript variables', 1, 10),
                    (1, 'Functions', 'Understanding JavaScript functions', 2, 15),
                    (2, 'HTML Structure', 'Learn HTML document structure', 1, 10),
                    (3, 'Python Basics', 'Introduction to Python', 1, 10)
                ");

                $this->conn->exec("
                    INSERT INTO challenges (lesson_id, title, description, instructions, starter_code, solution_code, points_reward, challenge_order) VALUES
                    (1, 'Create Your First Variable', 'Create a variable and assign a value', 'Create a variable named \"message\" and assign it the value \"Hello World\"', 'let message = \"\";', 'let message = \"Hello World\";', 15, 1),
                    (2, 'Write Your First Function', 'Create a simple function', 'Create a function named \"greet\" that returns \"Hello!\"', 'function greet() {\n  // Your code here\n}', 'function greet() {\n  return \"Hello!\";\n}', 20, 1)
                ");

                $this->conn->exec("
                    INSERT INTO badges (name, description, icon, badge_type, requirement_value) VALUES
                    ('First Steps', 'Complete your first lesson', '🎯', 'achievement', 1),
                    ('Code Warrior', 'Complete 10 challenges', '⚔️', 'achievement', 10),
                    ('Streak Master', 'Maintain a 7-day streak', '🔥', 'streak', 7)
                ");
            }
        } catch (PDOException $e) {
            error_log("Sample data insertion error: " . $e->getMessage());
        }
    }

    public function getWorkingHost() {
        return $this->working_host;
    }
}

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize database
try {
    $database = new Database($db_credentials);
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Database initialization failed: " . $e->getMessage());
}
?>
