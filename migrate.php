<?php
// Migration script to move data from MySQL to PostgreSQL

// MySQL connection details
$mysqlHost = 'localhost';
$mysqlDb   = 'userdb';
$mysqlUser = 'root';
$mysqlPass = '';
$mysqlCharset = 'utf8mb4';

// PostgreSQL connection from environment
$databaseUrl = getenv('DATABASE_URL');

// Check if we're running locally or on Heroku
$isLocal = empty($databaseUrl);

if (!$isLocal) {
    // Parse DATABASE_URL for Heroku
    $parsed = parse_url($databaseUrl);
    $postgresHost = $parsed['host'] ?? 'localhost';
    $postgresPort = $parsed['port'] ?? 5432;
    $postgresDb = ltrim($parsed['path'] ?? '', '/');
    $postgresUser = $parsed['user'] ?? '';
    $postgresPass = !empty($parsed['pass']) ? urldecode($parsed['pass']) : '';
} else {
    // Local environment - PostgreSQL not available
    $isLocal = true; // Set flag for later use
}

echo "========================================\n";
echo "Customer Database Migration Tool\n";
echo "========================================\n\n";

if ($isLocal) {
    echo "📍 LOCAL ENVIRONMENT DETECTED\n\n";
    echo "This script is designed for Heroku PostgreSQL migration.\n";
    echo "For local development:\n";
    echo "1. MySQL tables are auto-created by customers.php\n";
    echo "2. Use 'php sync-to-heroku.php' to transfer data to Heroku\n\n";
    echo "✓ MySQL setup check...\n";

    // Just check MySQL connection and table
    try {
        $dsn = "mysql:host=$mysqlHost;dbname=$mysqlDb;charset=$mysqlCharset";
        $mysql = new PDO($dsn, $mysqlUser, $mysqlPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        echo "✓ MySQL connected successfully\n";

        // Check if table exists and has data
        $stmt = $mysql->query("SELECT COUNT(*) as count FROM customer");
        $count = $stmt->fetch()['count'];
        echo "✓ Customer table exists with $count records\n\n";

        echo "🎯 To migrate data to Heroku, run:\n";
        echo "   php sync-to-heroku.php\n";

    } catch (PDOException $e) {
        echo "✗ MySQL connection failed: " . $e->getMessage() . "\n";
        echo "\n💡 Make sure XAMPP MySQL is running\n";
    }

    exit(0);
}

echo "🌐 HEROKU ENVIRONMENT DETECTED\n\n";
try {
    $dsn = "mysql:host=$mysqlHost;dbname=$mysqlDb;charset=$mysqlCharset";
    $mysql = new PDO($dsn, $mysqlUser, $mysqlPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connected to MySQL successfully\n\n";
} catch (PDOException $e) {
    die("✗ MySQL connection failed: " . $e->getMessage() . "\n");
}

// Step 2: Fetch data from MySQL
echo "[2] Fetching customer data from MySQL...\n";
try {
    $stmt = $mysql->prepare("SELECT id, name, email, created_at FROM customer ORDER BY id");
    $stmt->execute();
    $mysqlData = $stmt->fetchAll();
    echo "✓ Found " . count($mysqlData) . " records in MySQL\n\n";
} catch (PDOException $e) {
    die("✗ Failed to fetch MySQL data: " . $e->getMessage() . "\n");
}

// Step 3: Connect to PostgreSQL
echo "[3] Connecting to PostgreSQL...\n";
try {
    $dsn = "pgsql:host=$postgresHost;port=$postgresPort;dbname=$postgresDb";
    $postgres = new PDO($dsn, $postgresUser, $postgresPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connected to PostgreSQL successfully\n\n";
} catch (PDOException $e) {
    die("✗ PostgreSQL connection failed: " . $e->getMessage() . "\n");
}

// Step 4: Create table in PostgreSQL if it doesn't exist
echo "[4] Creating customer table in PostgreSQL...\n";
try {
    $postgres->exec("CREATE TABLE IF NOT EXISTS customer (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Customer table ready\n\n";
} catch (PDOException $e) {
    die("✗ Table creation failed: " . $e->getMessage() . "\n");
}

// Step 5: Migrate data to PostgreSQL
echo "[5] Migrating data to PostgreSQL...\n";
if (empty($mysqlData)) {
    echo "⚠ No data to migrate\n";
} else {
    try {
        $postgres->beginTransaction();
        
        // Use ON CONFLICT to update existing rows
        $stmt = $postgres->prepare("INSERT INTO customer (id, name, email, created_at) 
                                     VALUES (?, ?, ?, ?)
                                     ON CONFLICT (id) DO UPDATE 
                                     SET name = EXCLUDED.name,
                                         email = EXCLUDED.email,
                                         created_at = EXCLUDED.created_at");
        
        $count = 0;
        foreach ($mysqlData as $row) {
            $stmt->execute([
                $row['id'],
                $row['name'],
                $row['email'],
                $row['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $count++;
        }
        
        $postgres->commit();
        echo "✓ Successfully migrated/updated $count records\n\n";
    } catch (PDOException $e) {
        $postgres->rollBack();
        die("✗ Migration failed: " . $e->getMessage() . "\n");
    }
}

// Step 6: Verify migration
echo "[6] Verifying migration...\n";
try {
    $stmt = $postgres->prepare("SELECT COUNT(*) as count FROM customer");
    $stmt->execute();
    $result = $stmt->fetch();
    $pgCount = $result['count'];
    
    echo "✓ PostgreSQL now has $pgCount records\n";
    echo "✓ Migration completed successfully!\n";
    echo "========================================\n";
} catch (PDOException $e) {
    die("✗ Verification failed: " . $e->getMessage() . "\n");
}
?>
