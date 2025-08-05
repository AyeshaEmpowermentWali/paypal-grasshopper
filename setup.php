<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Grasshopper Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .setup-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #28a745;
        }

        .setup-info h3 {
            color: #333;
            margin-bottom: 1rem;
        }

        .setup-info ul {
            margin-left: 1.5rem;
            color: #666;
        }

        .setup-info li {
            margin-bottom: 0.5rem;
        }

        .config-section {
            background: #fff3cd;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #ffc107;
        }

        .config-section h4 {
            color: #856404;
            margin-bottom: 1rem;
        }

        .config-code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .status {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <h1>🦗 Database Setup</h1>
            <p>Grasshopper Clone Configuration</p>
        </div>

        <?php
        // Test database connection
        $connection_status = '';
        $error_message = '';
        
        try {
            require_once 'db.php';
            $connection_status = 'success';
        } catch (Exception $e) {
            $connection_status = 'error';
            $error_message = $e->getMessage();
        }
        ?>

        <?php if ($connection_status == 'success'): ?>
            <div class="status success">
                ✅ Database connection successful! Your Grasshopper clone is ready to use.
            </div>
            <div style="text-align: center;">
                <a href="index.php" class="btn">Go to Homepage</a>
            </div>
        <?php else: ?>
            <div class="status error">
                ❌ Database connection failed: <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="setup-info">
            <h3>Setup Instructions:</h3>
            <ul>
                <li>Make sure MySQL/MariaDB server is running</li>
                <li>Update database credentials in <code>db.php</code></li>
                <li>The system will automatically create the database and tables</li>
                <li>Sample data will be inserted automatically</li>
            </ul>
        </div>

        <div class="config-section">
            <h4>Common Database Configurations:</h4>
            
            <p><strong>For XAMPP/WAMP (Local Development):</strong></p>
            <div class="config-code">
define('DB_HOST', 'localhost');
define('DB_NAME', 'grasshopper_clone');
define('DB_USER', 'root');
define('DB_PASS', '');
            </div>
            
            <br>
            
            <p><strong>For MAMP:</strong></p>
            <div class="config-code">
define('DB_HOST', 'localhost');
define('DB_NAME', 'grasshopper_clone');
define('DB_USER', 'root');
define('DB_PASS', 'root');
            </div>
            
            <br>
            
            <p><strong>For Web Hosting:</strong></p>
            <div class="config-code">
define('DB_HOST', 'your_host_here');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <p style="color: #666; font-size: 0.9rem;">
                After updating the configuration, refresh this page to test the connection.
            </p>
        </div>
    </div>
</body>
</html>
