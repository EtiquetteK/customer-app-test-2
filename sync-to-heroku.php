<?php
// Export MySQL data and import to Heroku

echo "======================================\n";
echo "Customer Data Import to Heroku\n";
echo "======================================\n\n";

// Step 1: Fetch from local MySQL
echo "[1] Fetching customer data from MySQL...\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=userdb;charset=utf8mb4', 'root', '');
    $stmt = $pdo->query('SELECT id, name, email, created_at FROM customer ORDER BY id');
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Found " . count($customers) . " records\n\n";
} catch (PDOException $e) {
    die("✗ MySQL connection failed: " . $e->getMessage() . "\n");
}

if (empty($customers)) {
    echo "⚠ No data to import\n";
    exit(0);
}

// Step 2: Prepare data for import
echo "[2] Preparing data for import...\n";
$importData = json_encode(['customers' => $customers]);
echo "✓ Data prepared\n\n";

// Step 3: Send to Heroku
echo "[3] Sending to Heroku app...\n";
$herokuUrl = 'https://customers-green-211c4ebbf814.herokuapp.com/import.php';
$importToken = getenv('IMPORT_TOKEN') ?: 'test-import-token';

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $importToken
        ],
        'content' => $importData,
        'timeout' => 30
    ]
]);

try {
    $response = @file_get_contents($herokuUrl, false, $context);
    
    if ($response === false) {
        echo "⚠ Could not connect to Heroku. Trying with HTTP fallback...\n";
        // Try HTTP if HTTPS fails
        $herokuUrl = str_replace('https://', 'http://', $herokuUrl);
        $response = @file_get_contents($herokuUrl, false, $context);
    }
    
    if ($response === false) {
        die("✗ Failed to connect to Heroku app at $herokuUrl\n");
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['success']) && $result['success']) {
        echo "✓ " . $result['message'] . "\n";
        echo "========================================\n";
        echo "✓ Import completed successfully!\n";
    } else {
        echo "✗ Import failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    die("✗ Error: " . $e->getMessage() . "\n");
}
?>
