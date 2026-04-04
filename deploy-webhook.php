<?php
/**
 * ============================================================
 * Church Platform - Automated Deployment Webhook
 * ============================================================
 * 
 * SETUP:
 * 1. Upload this file to: public/deploy-webhook.php
 * 2. Generate a secret token: openssl rand -hex 32
 * 3. Add to .env: DEPLOY_SECRET=your_generated_token
 * 4. Set webhook URL in GitHub: https://yourdomain.com/deploy-webhook.php
 * 5. Set webhook secret in GitHub to match DEPLOY_SECRET
 * 
 * SECURITY:
 * - Validates GitHub signature
 * - Requires secret token
 * - Logs all deployment attempts
 * - Only accepts push events on specified branch
 * 
 * ============================================================
 */

// Configuration
define('DEPLOY_LOG', __DIR__ . '/../storage/logs/deploy.log');
define('DEPLOY_SCRIPT', __DIR__ . '/../deploy.sh');
define('ALLOWED_BRANCH', 'v5-foundation'); // Change if needed

/**
 * Log deployment events
 */
function logDeploy($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents(DEPLOY_LOG, $log, FILE_APPEND);
}

/**
 * Send JSON response
 */
function respond($message, $status = 200, $success = true) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit;
}

/**
 * Verify GitHub webhook signature
 */
function verifyGitHubSignature($payload, $signature) {
    // Load .env to get secret
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        logDeploy('ERROR: .env file not found', 'ERROR');
        return false;
    }
    
    $envContent = file_get_contents($envPath);
    if (!preg_match('/DEPLOY_SECRET=(.+)/', $envContent, $matches)) {
        logDeploy('ERROR: DEPLOY_SECRET not set in .env', 'ERROR');
        return false;
    }
    
    $secret = trim($matches[1]);
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    
    return hash_equals($expectedSignature, $signature);
}

// ============================================================
// Main Execution
// ============================================================

try {
    logDeploy('Webhook received from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Get request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond('Only POST requests allowed', 405, false);
    }
    
    // Get payload
    $payload = file_get_contents('php://input');
    if (empty($payload)) {
        respond('Empty payload', 400, false);
    }
    
    // Verify signature
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (empty($signature)) {
        logDeploy('Missing signature', 'ERROR');
        respond('Missing signature', 401, false);
    }
    
    if (!verifyGitHubSignature($payload, $signature)) {
        logDeploy('Invalid signature', 'ERROR');
        respond('Invalid signature', 401, false);
    }
    
    // Parse payload
    $data = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        respond('Invalid JSON payload', 400, false);
    }
    
    // Check if it's a push event
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    if ($event !== 'push') {
        logDeploy("Ignoring event: $event");
        respond("Event '$event' ignored. Only 'push' events trigger deployment.", 200);
    }
    
    // Check branch
    $ref = $data['ref'] ?? '';
    $branch = str_replace('refs/heads/', '', $ref);
    
    if ($branch !== ALLOWED_BRANCH) {
        logDeploy("Ignoring push to branch: $branch (allowed: " . ALLOWED_BRANCH . ")");
        respond("Branch '$branch' ignored. Only '" . ALLOWED_BRANCH . "' triggers deployment.", 200);
    }
    
    // Log commit info
    $commits = $data['commits'] ?? [];
    $commitCount = count($commits);
    $pusher = $data['pusher']['name'] ?? 'unknown';
    $headCommit = $data['head_commit']['message'] ?? 'No message';
    
    logDeploy("Deploying $commitCount commit(s) from $pusher on branch $branch");
    logDeploy("Latest commit: $headCommit");
    
    // Check if deploy script exists
    if (!file_exists(DEPLOY_SCRIPT)) {
        logDeploy('ERROR: Deploy script not found at ' . DEPLOY_SCRIPT, 'ERROR');
        respond('Deploy script not found', 500, false);
    }
    
    // Make script executable
    chmod(DEPLOY_SCRIPT, 0755);
    
    // Run deployment in background
    $output = [];
    $returnCode = 0;
    
    $command = 'cd ' . escapeshellarg(dirname(DEPLOY_SCRIPT)) . ' && bash ' . escapeshellarg(DEPLOY_SCRIPT) . ' 2>&1';
    
    // Execute deployment
    exec($command, $output, $returnCode);
    
    $outputStr = implode("\n", $output);
    
    if ($returnCode === 0) {
        logDeploy("Deployment successful!\n$outputStr", 'SUCCESS');
        respond('Deployment completed successfully', 200);
    } else {
        logDeploy("Deployment failed with code $returnCode\n$outputStr", 'ERROR');
        respond('Deployment failed. Check logs for details.', 500, false);
    }
    
} catch (Exception $e) {
    logDeploy('EXCEPTION: ' . $e->getMessage(), 'ERROR');
    respond('Internal error: ' . $e->getMessage(), 500, false);
}
