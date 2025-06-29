<?php
require_once 'includes/db.php';

echo "<h2>Database Migration Script</h2>";
echo "<p>Adding profile_pic column to users table...</p>";

try {
    $db = new Database();
    
    // Check if profile_pic column exists
    $db->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    $result = $db->single();
    
    if (!$result) {
        // Add profile_pic column
        $db->query("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
        $db->execute();
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>✓ Successfully added profile_pic column to users table</div>";
    } else {
        echo "<div style='color: blue; padding: 10px; border: 1px solid blue; margin: 10px 0;'>ℹ profile_pic column already exists</div>";
    }
    
    // Check if role column exists
    $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $result = $db->single();
    
    if (!$result) {
        // Add role column
        $db->query("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'");
        $db->execute();
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>✓ Successfully added role column to users table</div>";
    } else {
        echo "<div style='color: blue; padding: 10px; border: 1px solid blue; margin: 10px 0;'>ℹ role column already exists</div>";
    }
    
    // Check if notifications column exists
    $db->query("SHOW COLUMNS FROM users LIKE 'notifications'");
    $result = $db->single();
    
    if (!$result) {
        // Add notifications column
        $db->query("ALTER TABLE users ADD COLUMN notifications BOOLEAN DEFAULT TRUE");
        $db->execute();
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>✓ Successfully added notifications column to users table</div>";
    } else {
        echo "<div style='color: blue; padding: 10px; border: 1px solid blue; margin: 10px 0;'>ℹ notifications column already exists</div>";
    }
    
    // Check if updated_at column exists
    $db->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
    $result = $db->single();
    
    if (!$result) {
        // Add updated_at column
        $db->query("ALTER TABLE users ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $db->execute();
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>✓ Successfully added updated_at column to users table</div>";
    } else {
        echo "<div style='color: blue; padding: 10px; border: 1px solid blue; margin: 10px 0;'>ℹ updated_at column already exists</div>";
    }
    
    echo "<div style='color: green; padding: 15px; border: 2px solid green; margin: 20px 0; background-color: #f0fff0;'>";
    echo "<h3>✓ Migration completed successfully!</h3>";
    echo "<p>You can now use the profile picture functionality.</p>";
    echo "<p><a href='profile.php'>Go to Profile Page</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>✗ Error: " . $e->getMessage() . "</div>";
}
?>
