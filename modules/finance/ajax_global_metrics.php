<?php
/**
 * AJAX endpoint for global metrics
 * Returns JSON with real-time stock & piutang data
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/finance_global_metrics.php';

header('Content-Type: application/json');

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $conn = getDBConnection();
    $metrics = getGlobalMetrics($conn);
    
    echo json_encode([
        'success' => true,
        'data' => $metrics,
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
