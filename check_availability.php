<?php
require 'staff/db.php';

$available_futsals = [];
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    if ($start_time && $end_time) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM futsals f
                WHERE NOT EXISTS (
                    SELECT 1 FROM bookings b 
                    WHERE b.futsal_id = f.id 
                    AND (
                        (b.start_time <= :start_time1 AND b.end_time > :start_time2) OR
                        (b.start_time < :end_time1 AND b.end_time >= :end_time2)
                    )
                )
            ");
            $stmt->execute([
                'start_time1' => $start_time,
                'start_time2' => $start_time,
                'end_time1'   => $end_time,
                'end_time2'   => $end_time
            ]);
            $available_futsals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please provide both start time and end time.";
    }
}
?>
