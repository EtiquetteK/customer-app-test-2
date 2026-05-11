<?php
// Import endpoint for seeding PostgreSQL with customer data
header('Content-Type: application/json');

// Only allow POST from localhost or with valid token
$allowedIPs = ['127.0.0.1', '::1'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

// Get the authorization token from header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = getenv('IMPORT_TOKEN') ?: 'test-import-token';

$isAuthorized = in_array($clientIP, $allowedIPs) || 
                (strpos($authHeader, 'Bearer ') === 0 && substr($authHeader, 7) === $token);

if (!$isAuthorized) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Get JSON data from request
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['customers']) || !is_array($input['customers'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing or invalid customers data']));
}

// Connect to PostgreSQL
$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    http_response_code(500);
    die(json_encode(['error' => 'DATABASE_URL not set']));
}

$parsed = parse_url($databaseUrl);
$postgresHost = $parsed['host'] ?? 'localhost';
$postgresPort = $parsed['port'] ?? 5432;
$postgresDb = ltrim($parsed['path'] ?? '', '/');
$postgresUser = $parsed['user'] ?? '';
$postgresPass = !empty($parsed['pass']) ? urldecode($parsed['pass']) : '';

try {
    $dsn = "pgsql:host=$postgresHost;port=$postgresPort;dbname=$postgresDb";
    $postgres = new PDO($dsn, $postgresUser, $postgresPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'PostgreSQL connection failed: ' . $e->getMessage()]));
}

// Create table if not exists
try {
    $postgres->exec("CREATE TABLE IF NOT EXISTS customer (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Table creation failed: ' . $e->getMessage()]));
}

// Insert data
try {
    $postgres->beginTransaction();
    
    $stmt = $postgres->prepare("INSERT INTO customer (id, name, email, created_at) 
                                 VALUES (?, ?, ?, ?)
                                 ON CONFLICT (id) DO UPDATE 
                                 SET name = EXCLUDED.name,
                                     email = EXCLUDED.email,
                                     created_at = EXCLUDED.created_at");
    
    $count = 0;
    foreach ($input['customers'] as $customer) {
        if (!isset($customer['name']) || !isset($customer['email'])) {
            throw new Exception('Invalid customer data: missing name or email');
        }
        
        $stmt->execute([
            $customer['id'] ?? null,
            $customer['name'],
            $customer['email'],
            $customer['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        $count++;
    }
    
    $postgres->commit();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Successfully imported $count customer records",
        'count' => $count
    ]);
} catch (Exception $e) {
    $postgres->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
}
?>
