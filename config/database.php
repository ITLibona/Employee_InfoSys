<?php
// ---------------------------------------------------------------------------
// Database connection configuration
// Supports generic env vars and Railway-provided MySQL env vars.
// ---------------------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'employee_infosys');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
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
