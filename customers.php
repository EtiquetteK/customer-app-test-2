<?php
$defaultHost = 'localhost';
$defaultDb   = 'userdb';
$defaultUser = 'root';
$defaultPass = '';
$defaultCharset = 'utf8mb4';

$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl !== false) {
    $parsed = parse_url($databaseUrl);

    if ($parsed === false || !isset($parsed['scheme'])) {
        die('Invalid DATABASE_URL environment variable');
    }

    $scheme = $parsed['scheme'];
    if ($scheme === 'postgres' || $scheme === 'pgsql') {
        $dbHost = $parsed['host'] ?? $defaultHost;
        $dbPort = $parsed['port'] ?? 5432;
        $dbName = ltrim($parsed['path'] ?? '', '/');
        $dbUser = $parsed['user'] ?? '';
        $dbPass = $parsed['pass'] ?? '';

        $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
    } else {
        die('Unsupported DATABASE_URL scheme: ' . htmlspecialchars($scheme));
    }
} else {
    $dbHost = $defaultHost;
    $dbName = $defaultDb;
    $dbUser = $defaultUser;
    $dbPass = $defaultPass;
    $charset = $defaultCharset;
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // Detect database type and create table if not exists
    if ($scheme === 'postgres' || $scheme === 'pgsql') {
        // PostgreSQL table creation
        $pdo->exec("CREATE TABLE IF NOT EXISTS customer (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert sample data if table is empty
        $checkStmt = $pdo->query("SELECT COUNT(*) as count FROM customer");
        $count = $checkStmt->fetch()['count'];
        if ($count == 0) {
            $pdo->exec("INSERT INTO customer (name, email) VALUES 
                ('John Doe', 'john@example.com'),
                ('Jane Smith', 'jane@example.com'),
                ('Bob Johnson', 'bob@example.com')");
        }
    } else {
        // MySQL table creation
        $pdo->exec("CREATE TABLE IF NOT EXISTS customer (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert sample data if table is empty
        $checkStmt = $pdo->query("SELECT COUNT(*) as count FROM customer");
        $count = $checkStmt->fetch()['count'];
        if ($count == 0) {
            $pdo->exec("INSERT INTO customer (name, email) VALUES 
                ('John Doe', 'john@example.com'),
                ('Jane Smith', 'jane@example.com'),
                ('Bob Johnson', 'bob@example.com')");
        }
    }

    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM customer ORDER BY created_at ASC");
    $stmt->execute();
    $customers = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Connection failure: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customers</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 80%; margin: auto; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Customer Records Info</h2>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Created At</th>
        </tr>
        <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?= htmlspecialchars($customer['id']) ?></td>
            <td><?= htmlspecialchars($customer['name']) ?></td>
            <td><?= htmlspecialchars($customer['email']) ?></td>
            <td><?= htmlspecialchars($customer['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
