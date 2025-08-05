<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Database Host</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .testing { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .progress { width: 100%; background: #e9ecef; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background: #007bff; border-radius: 5px; transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Host Finder</h1>
        
        <?php
        $credentials = [
            'dbname' => 'dbpr8vdojr6oso',
            'username' => 'ugrj543f7lree',
            'password' => 'cgmq43woifko'
        ];

        // Extended list of possible hosts
        $possible_hosts = [
            // Common hosting providers
            'mysql.hostinger.com',
            'mysql.000webhost.com',
            'sql200.infinityfree.com',
            'sql201.infinityfree.com',
            'sql202.infinityfree.com',
            'mysql.byethost.com',
            'mysql.freehostia.com',
            'mysql.awardspace.com',
            'mysql.x10hosting.com',
            'db4free.net',
            'remotemysql.com',
            'sql.freedb.tech',
            
            // AlwaysData patterns
            'mysql-' . substr($credentials['username'], 0, 8) . '.alwaysdata.net',
            'mysql-' . $credentials['username'] . '.alwaysdata.net',
            
            // Generic patterns
            'localhost',
            '127.0.0.1',
            'mysql',
            'db',
            'database',
            
            // IP-based patterns (common ranges)
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
        ];

        $working_hosts = [];
        $failed_hosts = [];
        $total_hosts = count($possible_hosts);
        $tested = 0;

        echo "<div class='info'><h3>🔄 Testing Database Connections...</h3></div>";
        echo "<div class='progress'><div class='progress-bar' id='progress' style='width: 0%'></div></div>";
        echo "<div id='status'>Starting tests...</div>";
        
        flush();
        ob_flush();

        foreach ($possible_hosts as $host) {
            $tested++;
            $progress = ($tested / $total_hosts) * 100;
            
            echo "<script>
                document.getElementById('progress').style.width = '{$progress}%';
                document.getElementById('status').innerHTML = 'Testing: {$host} ({$tested}/{$total_hosts})';
            </script>";
            flush();
            ob_flush();
            
            $ports = [3306, 3307, 3308];
            
            foreach ($ports as $port) {
                try {
                    $dsn = "mysql:host={$host};port={$port};dbname={$credentials['dbname']};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 3
                    ];

                    $pdo = new PDO($dsn, $credentials['username'], $credentials['password'], $options);
                    $stmt = $pdo->query("SELECT 1");
                    
                    if ($stmt->fetch()) {
                        $working_hosts[] = [
                            'host' => $host,
                            'port' => $port,
                            'full' => $port == 3306 ? $host : "{$host}:{$port}"
                        ];
                        break; // Found working port, no need to test others
                    }
                    
                } catch (PDOException $e) {
                    $failed_hosts[] = [
                        'host' => $host,
                        'port' => $port,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Small delay to prevent overwhelming the server
            usleep(100000); // 0.1 second
        }

        echo "<script>document.getElementById('status').innerHTML = 'Testing complete!';</script>";
        ?>

        <?php if (!empty($working_hosts)): ?>
            <div class="success">
                <h3>✅ Found Working Database Host(s)!</h3>
                <?php foreach ($working_hosts as $host_info): ?>
                    <div class="code">
                        <strong>Host:</strong> <?php echo $host_info['full']; ?><br>
                        <strong>Port:</strong> <?php echo $host_info['port']; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="info">
                <h3>🔧 How to Fix Your Application:</h3>
                <p>Update your <code>db.php</code> file with this configuration:</p>
                <div class="code">
                    // Replace the $hosting_patterns array in db.php with:<br>
                    $hosting_patterns = [<br>
                    <?php foreach ($working_hosts as $host_info): ?>
                        &nbsp;&nbsp;&nbsp;&nbsp;'<?php echo $host_info['full']; ?>',<br>
                    <?php endforeach; ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;'localhost' // Keep as fallback<br>
                    ];
                </div>
            </div>

            <a href="index.php" class="btn">✅ Test Application Now</a>
            <a href="signup.php" class="btn">📝 Try Sign Up</a>

        <?php else: ?>
            <div class="error">
                <h3>❌ No Working Host Found</h3>
                <p>None of the common database hosts worked with your credentials.</p>
            </div>

            <div class="info">
                <h3>🔍 What to do next:</h3>
                <ol>
                    <li><strong>Check your hosting control panel</strong> for the exact database host</li>
                    <li><strong>Contact your hosting provider</strong> for database connection details</li>
                    <li><strong>Verify your credentials</strong> are correct</li>
                    <li><strong>Check if remote connections are enabled</strong></li>
                </ol>
            </div>

            <div class="info">
                <h3>📋 Failed Connection Details:</h3>
                <details>
                    <summary>Click to see all tested hosts</summary>
                    <?php foreach (array_slice($failed_hosts, 0, 10) as $failed): ?>
                        <div style="margin: 5px 0; font-size: 0.9em;">
                            <strong><?php echo $failed['host']; ?>:<?php echo $failed['port']; ?></strong> - 
                            <?php echo substr($failed['error'], 0, 100); ?>...
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($failed_hosts) > 10): ?>
                        <p><em>... and <?php echo count($failed_hosts) - 10; ?> more</em></p>
                    <?php endif; ?>
                </details>
            </div>
        <?php endif; ?>

        <div class="info">
            <h3>💡 Manual Configuration</h3>
            <p>If you know your database host, you can manually update <code>db.php</code>:</p>
            <div class="code">
                // Add your host at the beginning of $hosting_patterns array:<br>
                $hosting_patterns = [<br>
                &nbsp;&nbsp;&nbsp;&nbsp;'YOUR_ACTUAL_HOST_HERE',<br>
                &nbsp;&nbsp;&nbsp;&nbsp;'localhost',<br>
                &nbsp;&nbsp;&nbsp;&nbsp;// ... other hosts<br>
                ];
            </div>
        </div>
    </div>
</body>
</html>
