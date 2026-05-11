<?php
$pdo = new PDO('mysql:host=localhost;dbname=userdb;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SELECT id, name, email, created_at FROM customer ORDER BY id');
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "MySQL Customer Data:\n";
echo "===================\n";
if (empty($data)) {
    echo "No records found!\n";
} else {
    foreach ($data as $row) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}, Created: {$row['created_at']}\n";
    }
    echo "\nTotal: " . count($data) . " records\n";
}
?>
