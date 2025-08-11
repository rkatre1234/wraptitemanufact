<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$servername = "mysql80";
$username = "root";
$password = "magento";
$dbname = "wraptitemanufact_tiudemo";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Connected successfully<br>";

/**
 * Execute a query and return results
 * @param mysqli $conn Connection object
 * @param string $sql SQL query to execute
 * @return array|false Array of results or false on error
 */
function executeQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "Error executing query: " . mysqli_error($conn) . "<br>";
        return false;
    }
    
    if (mysqli_num_rows($result) > 0) {
        $rows = [];
        while($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    } else {
        return [];
    }
}

// Example: Get table names from the database
$sql = "SHOW TABLES FROM $dbname";
$tables = executeQuery($conn, $sql);

if ($tables) {
    echo "<h3>Tables in database:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table['Tables_in_' . $dbname] . "</li>";
    }
    echo "</ul>";
} else {
    echo "No tables found or error occurred.";
}

// Close connection
mysqli_close($conn);
?>
