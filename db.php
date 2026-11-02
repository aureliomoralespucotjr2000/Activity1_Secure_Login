<?php
// db.php - database connection (change the credentials to match your setup)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli('localhost', 'root', '', 'wst2_auth');
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());          // log details, never show them to users
    http_response_code(500);
    exit('Service unavailable.');
}
