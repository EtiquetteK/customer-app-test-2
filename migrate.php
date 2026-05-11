<?php
/**
 * Migration script: Migrate customer data from local MySQL to Heroku PostgreSQL
 * 
 * Usage:
 *   1. Get Heroku Postgres URL: heroku pg:credentials:url --app customers-green
 *   2. Set DATABASE_URL: $env:DATABASE_URL = "postgres://user:pass@host:port/dbname"
 *   3. Run: php migrate.php
 */

echo "=== Customer Data Migration (MySQL to PostgreSQL) ===\n\n";

// Step 1: Connect to local MySQL
echo "[1] Connecting to local MySQL (useddb)...\n";
try {
    $mysql = new PDO("mysql:host=localhost;dbname=userdb;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connected to MySQL successfully\n\n";
} catch (PDOException $e) {
    die("✗ MySQL Connection failed: " . $e->getMessage() . "\n");
}

// Step 2: Connect to Heroku PostgreSQL
echo "[2] Connecting to Heroku PostgreSQL...\n";
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl === false) {
    die("✗ DATABASE_URL environment variable not set!\n");
}

$parsed = parse_url($databaseUrl);
if ($parsed === false || !isset($parsed['scheme'])) {
    die("✗ Invalid DATABASE_URL format\n");
}

try {
    $dsn = "pgsql:host=" . $parsed['host'] . ";port=" . ($parsed['port'] ?? 5432) . ";dbname=" . ltrim($parsed['path'], '/');
    $postgres = new PDO($dsn, $parsed['user'], $parsed['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connected to PostgreSQL successfully\n\n";
} catch (PDOException $e) {
    die("✗ PostgreSQL Connection failed: " . $e->getMessage() . "\n");
}

// Step 3: Create table in PostgreSQL
echo "[3] Creating customer table in PostgreSQL...\n";
try {
    $postgres->exec("CREATE TABLE IF NOT EXISTS customer (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Table created/verified\n\n";
} catch (PDOException $e) {
    die("✗ Table creation failed: " . $e->getMessage() . "\n");
}

// Step 4: Fetch data from MySQL
echo "[4] Fetching customer data from MySQL...\n";
try {
    $stmt = $mysql->query("SELECT * FROM customer ORDER BY id");
    $mysqlData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Retrieved " . count($mysqlData) . " records\n\n";
} catch (PDOException $e) {
    die("✗ MySQL query failed: " . $e->getMessage() . "\n");
}

// Step 5: Migrate data to PostgreSQL
echo "[5] Migrating data to PostgreSQL...\n";
if (empty($mysqlData)) {
    echo "⚠ No data to migrate\n";
} else {
    try {
        $postgres->beginTransaction();
        
        $stmt = $postgres->prepare("INSERT INTO customer (id, name, email, created_at) 
                                     VALUES (?, ?, ?, ?)");
        
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
        echo "✓ Successfully migrated $count records\n\n";
    } catch (PDOException $e) {
        $postgres->rollBack();
        die("✗ Migration failed: " . $e->getMessage() . "\n");
    }
}

// Step 6: Verify migration
echo "[6] Verifying migration...\n";
try {
    $verifyStmt = $postgres->query("SELECT COUNT(*) as count FROM customer");
    $result = $verifyStmt->fetch();
    $count = $result['count'];
    echo "✓ PostgreSQL now contains $count customer records\n\n";
    
    // Show sample data
    $sampleStmt = $postgres->query("SELECT id, name, email FROM customer LIMIT 3");
    $samples = $sampleStmt->fetchAll();
    
    echo "Sample records:\n";
    echo str_repeat("-", 70) . "\n";
    foreach ($samples as $row) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Email: {$row['email']}\n";
    }
    echo str_repeat("-", 70) . "\n\n";
    
} catch (PDOException $e) {
    die("✗ Verification failed: " . $e->getMessage() . "\n");
}

echo "✅ Migration complete! Your Heroku app will now display your actual customer data.\n";
?>
