<?php
// ---------------------------------------------------------------------------
// Database connection configuration
// Uses environment variables for production, defaults for local development
// ---------------------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'employee_infosys');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log("DB connection failed: " . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:30px;color:#c00">
                    <h2>Database Connection Error</h2>
                    <p>Could not connect to the database. Please run
                    <a href="/Employee_InfoSys/setup.php">setup.php</a> first,
                    then verify your credentials in <code>config/database.php</code>.</p>
                 </div>');
        }
    }
    return $pdo;
}
