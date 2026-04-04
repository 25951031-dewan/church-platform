<?php
/**
 * ============================================================
 * Church Platform - Manual Deployment Trigger
 * ============================================================
 * 
 * SETUP:
 * 1. Upload to: public/deploy-manual.php
 * 2. Add to .env: DEPLOY_SECRET=your_secret_password
 * 3. Access: https://yourdomain.com/deploy-manual.php?secret=your_secret_password
 * 
 * SECURITY:
 * - DELETE THIS FILE after use or protect with .htaccess
 * - Use strong random secret
 * - Only use on HTTPS domains
 * 
 * ============================================================
 */

define('DEPLOY_SCRIPT', __DIR__ . '/../deploy.sh');
define('DEPLOY_LOG', __DIR__ . '/../storage/logs/deploy.log');

// Check secret
$envPath = __DIR__ . '/../.env';
$envContent = file_get_contents($envPath);
preg_match('/DEPLOY_SECRET=(.+)/', $envContent, $matches);
$expectedSecret = trim($matches[1] ?? '');

$providedSecret = $_GET['secret'] ?? '';

if (empty($expectedSecret) || $providedSecret !== $expectedSecret) {
    http_response_code(403);
    die('Access Denied');
}

// HTML header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Deploy Church Platform</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #45a049; }
        .output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            margin-top: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Church Platform Deployment</h1>
        
        <?php
        if (isset($_POST['deploy'])) {
            echo '<div class="output">';
            
            // Check if script exists
            if (!file_exists(DEPLOY_SCRIPT)) {
                echo '<span class="error">ERROR: Deploy script not found!</span>';
            } else {
                // Make executable
                chmod(DEPLOY_SCRIPT, 0755);
                
                // Run deployment
                $command = 'cd ' . escapeshellarg(dirname(DEPLOY_SCRIPT)) . ' && bash ' . escapeshellarg(DEPLOY_SCRIPT) . ' 2>&1';
                
                echo "Executing deployment...\n\n";
                
                // Execute and stream output
                $handle = popen($command, 'r');
                while (!feof($handle)) {
                    $line = fgets($handle);
                    echo htmlspecialchars($line);
                    flush();
                    ob_flush();
                }
                $returnCode = pclose($handle);
                
                echo "\n\n";
                if ($returnCode === 0) {
                    echo '<span class="success">✓ Deployment completed successfully!</span>';
                } else {
                    echo '<span class="error">✗ Deployment failed with exit code: ' . $returnCode . '</span>';
                }
            }
            
            echo '</div>';
            echo '<p><a href="?secret=' . htmlspecialchars($providedSecret) . '" class="btn">Deploy Again</a></p>';
        } else {
            ?>
            <p>Click the button below to deploy the latest changes from GitHub:</p>
            <form method="post">
                <button type="submit" name="deploy" class="btn">Deploy Now</button>
            </form>
            
            <h3>This will:</h3>
            <ol>
                <li>Pull latest code from GitHub (v5-foundation branch)</li>
                <li>Install PHP dependencies (composer)</li>
                <li>Run database migrations</li>
                <li>Build frontend assets (npm)</li>
                <li>Clear and optimize cache</li>
            </ol>
            
            <p style="color: #666; margin-top: 30px;">
                <strong>Note:</strong> This may take 1-2 minutes. Do not close this page.
            </p>
            <?php
        }
        ?>
    </div>
</body>
</html>
