<?php
require_once 'db.php';
header('Content-Type: application/json');

$sql = "SELECT e.event_id, e.title, e.event_date, e.max_capacity, COUNT(r.reg_id) AS booked 
        FROM events e 
        LEFT JOIN registrations r ON e.event_id = r.event_id 
        GROUP BY e.event_id 
        ORDER BY e.event_date ASC";
$res = $conn->query($sql);
$events = [];
while ($row = $res->fetch_assoc()) {
    $row['formatted_date'] = date("d M Y", strtotime($row['event_date']));
    $row['remaining'] = (int)$row['max_capacity'] - (int)$row['booked'];
    $events[] = $row;
}
echo json_encode($events);
?>
