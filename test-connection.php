<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #bee5eb;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦗 Database Connection Test</h1>
        
        <?php
        // Your database credentials
        $credentials = [
            'host' => 'localhost',
            'dbname' => 'dbpr8vdojr6oso',
            'username' => 'ugrj543f7lree',
            'password' => 'cgmq43woifkoari'
        ];

        // Alternative hosts to try
        $hosts_to_try = ['localhost', '127.0.0.1', 'mysql', 'db'];
        
        $connection_successful = false;
        $working_host = null;
        $error_messages = [];

        foreach ($hosts_to_try as $host) {
            try {
                $dsn = "mysql:host={$host};dbname={$credentials['dbname']};charset=utf8mb4";
                $pdo = new PDO($dsn, $credentials['username'], $credentials['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // Test the connection
                $stmt = $pdo->query("SELECT 1");
                $result = $stmt->fetch();
                
                if ($result) {
                    $connection_successful = true;
                    $working_host = $host;
                    break;
                }
                
            } catch (PDOException $e) {
                $error_messages[$host] = $e->getMessage();
                
                // Try to create database if it doesn't exist
                if (strpos($e->getMessage(), 'Unknown database') !== false) {
                    try {
                        $dsn_no_db = "mysql:host={$host};charset=utf8mb4";
                        $temp_pdo = new PDO($dsn_no_db, $credentials['username'], $credentials['password'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);
                        
                        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$credentials['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        // Try connecting again
                        $dsn = "mysql:host={$host};dbname={$credentials['dbname']};charset=utf8mb4";
                        $pdo = new PDO($dsn, $credentials['username'], $credentials['password'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);
                        
                        $stmt = $pdo->query("SELECT 1");
                        if ($stmt->fetch()) {
                            $connection_successful = true;
                            $working_host = $host;
                            break;
                        }
                        
                    } catch (PDOException $create_error) {
                        $error_messages[$host] .= " | Create DB Error: " . $create_error->getMessage();
                    }
                }
            }
        }
        ?>

        <?php if ($connection_successful): ?>
            <div class="success">
                <h3>✅ Connection Successful!</h3>
                <p><strong>Working Host:</strong> <?php echo $working_host; ?></p>
                <p><strong>Database:</strong> <?php echo $credentials['dbname']; ?></p>
                <p><strong>Username:</strong> <?php echo $credentials['username']; ?></p>
            </div>
            
            <div class="info">
                <h4>Your application is ready to use!</h4>
                <p>The database connection is working properly. You can now:</p>
                <ul>
                    <li>Sign up for new accounts</li>
                    <li>Login to existing accounts</li>
                    <li>Use all features of the Grasshopper clone</li>
                </ul>
            </div>
            
            <a href="index.php" class="btn">Go to Application</a>
            <a href="signup.php" class="btn">Sign Up</a>

        <?php else: ?>
            <div class="error">
                <h3>❌ Connection Failed</h3>
                <p>Could not connect to the database with the provided credentials.</p>
            </div>
            
            <div class="info">
                <h4>Error Details:</h4>
                <?php foreach ($error_messages as $host => $error): ?>
                    <p><strong><?php echo $host; ?>:</strong> <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
            
            <div class="info">
                <h4>Possible Solutions:</h4>
                <ul>
                    <li><strong>Check Host:</strong> Your database might not be on localhost</li>
                    <li><strong>Verify Credentials:</strong> Double-check username and password</li>
                    <li><strong>Database Server:</strong> Make sure MySQL server is running</li>
                    <li><strong>Firewall:</strong> Check if port 3306 is accessible</li>
                    <li><strong>Remote Access:</strong> Enable remote connections if using external host</li>
                </ul>
            </div>
            
            <div class="info">
                <h4>Try Different Host:</h4>
                <p>If you know your database host is different, update the host in db.php:</p>
                <pre>'host' => 'your_actual_host_here'</pre>
                <p>Common alternatives:</p>
                <ul>
                    <li>mysql.yourhost.com</li>
                    <li>db.yourhost.com</li>
                    <li>IP address (e.g., 192.168.1.100)</li>
                </ul>
            </div>
        <?php endif; ?>

        <hr style="margin: 30px 0;">
        
        <div class="info">
            <h4>Current Configuration:</h4>
            <pre><?php 
                echo "Host: " . $credentials['host'] . "\n";
                echo "Database: " . $credentials['dbname'] . "\n";
                echo "Username: " . $credentials['username'] . "\n";
                echo "Password: " . (empty($credentials['password']) ? '(empty)' : '(provided)') . "\n";
            ?></pre>
        </div>
    </div>
</body>
</html>
