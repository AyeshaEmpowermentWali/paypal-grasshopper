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
        .config-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            border-left: 4px solid #007bff;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🦗 Grasshopper Clone - Database Test</h1>
        
        <?php
        // Test different database configurations
        $configs = [
            'XAMPP/WAMP' => [
                'host' => 'localhost',
                'username' => 'root',
                'password' => ''
            ],
            'MAMP' => [
                'host' => 'localhost',
                'username' => 'root',
                'password' => 'root'
            ],
            'LAMP (Ubuntu)' => [
                'host' => 'localhost',
                'username' => 'root',
                'password' => 'password'
            ]
        ];

        $working_config = null;
        $test_results = [];

        foreach ($configs as $name => $config) {
            try {
                $dsn = "mysql:host={$config['host']};charset=utf8mb4";
                $pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                $test_results[$name] = [
                    'status' => 'success',
                    'message' => 'Connection successful!'
                ];
                
                if (!$working_config) {
                    $working_config = $config;
                    $working_config['name'] = $name;
                }
                
            } catch (PDOException $e) {
                $test_results[$name] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        ?>

        <h2>Connection Test Results:</h2>
        
        <?php foreach ($test_results as $name => $result): ?>
            <div class="<?php echo $result['status']; ?>">
                <strong><?php echo $name; ?>:</strong> <?php echo $result['message']; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($working_config): ?>
            <div class="success">
                <h3>✅ Recommended Configuration Found!</h3>
                <p>Use <strong><?php echo $working_config['name']; ?></strong> configuration in your db.php file:</p>
            </div>
            
            <div class="config-box">
$db_config = [<br>
&nbsp;&nbsp;&nbsp;&nbsp;'host' => '<?php echo $working_config['host']; ?>',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'dbname' => 'grasshopper_clone',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'username' => '<?php echo $working_config['username']; ?>',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'password' => '<?php echo $working_config['password']; ?>'<br>
];
            </div>

            <div class="info">
                <h4>Next Steps:</h4>
                <ol>
                    <li>Copy the configuration above</li>
                    <li>Open <code>db.php</code> file</li>
                    <li>Replace the <code>$db_config</code> array with the one above</li>
                    <li>Save the file and test your application</li>
                </ol>
            </div>

            <a href="index.php" class="btn">Test Application</a>
            <a href="setup.php" class="btn">Run Setup</a>

        <?php else: ?>
            <div class="error">
                <h3>❌ No Working Configuration Found</h3>
                <p>Please check:</p>
                <ul>
                    <li>MySQL/MariaDB server is running</li>
                    <li>You have the correct username and password</li>
                    <li>The MySQL service is started</li>
                </ul>
            </div>

            <div class="info">
                <h4>Manual Configuration:</h4>
                <p>If you know your database credentials, update the <code>$db_config</code> array in <code>db.php</code>:</p>
            </div>
            
            <div class="config-box">
$db_config = [<br>
&nbsp;&nbsp;&nbsp;&nbsp;'host' => 'your_host',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'dbname' => 'grasshopper_clone',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'username' => 'your_username',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'password' => 'your_password'<br>
];
            </div>
        <?php endif; ?>

        <hr style="margin: 30px 0;">
        
        <h3>Common Solutions:</h3>
        <div class="info">
            <ul>
                <li><strong>XAMPP Users:</strong> Start Apache and MySQL from XAMPP Control Panel</li>
                <li><strong>WAMP Users:</strong> Make sure WAMP is running (green icon)</li>
                <li><strong>MAMP Users:</strong> Start MAMP servers</li>
                <li><strong>Linux Users:</strong> Run <code>sudo service mysql start</code></li>
                <li><strong>Windows Users:</strong> Check if MySQL service is running in Services</li>
            </ul>
        </div>
    </div>
</body>
</html>
