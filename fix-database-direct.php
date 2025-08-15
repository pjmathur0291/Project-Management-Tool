<?php
echo "<h2>Simple Database Fix</h2>";

// Include database config
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    echo "<p>✅ Connected to database</p>";
    
    // Add completed_at column
    echo "<p>Adding completed_at column...</p>";
    $pdo->exec("ALTER TABLE tasks ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
    echo "<p>✅ completed_at column added</p>";
    
    // Add completed_by column
    echo "<p>Adding completed_by column...</p>";
    $pdo->exec("ALTER TABLE tasks ADD COLUMN completed_by INT(11) NULL DEFAULT NULL AFTER completed_at");
    echo "<p>✅ completed_by column added</p>";
    
    // Show final structure
    echo "<h3>Updated Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE tasks");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th></tr>";
    foreach ($columns as $col) {
        $highlight = '';
        if (in_array($col['Field'], ['completed_at', 'completed_by'])) {
            $highlight = 'background: yellow;';
        }
        echo "<tr style='{$highlight}'>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-size: 18px;'>🎉 Database fixed successfully!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='debug-status-update.php'>Test Status Updates</a></p>";
echo "<p><a href='index.php'>Back to Dashboard</a></p>";
?>
