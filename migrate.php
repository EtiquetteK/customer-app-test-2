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
