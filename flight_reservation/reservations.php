<?php
// customer/reservations.php - Reservation Information + List
require_once '../auth.php';
$username = check_session('customer');
$conn = get_db();

// Total reservations
$s = oci_parse($conn, 'SELECT COUNT(*) AS total FROM reservation WHERE username = :uname');
oci_bind_by_name($s, ':uname', $username); oci_execute($s);
$total = oci_fetch_assoc($s)['TOTAL'];
oci_free_statement($s);

// Upcoming reservations (flight_date >= TRUNC(SYSDATE))
$s = oci_parse($conn,
    'SELECT COUNT(*) AS upcoming
     FROM reservation r JOIN flight f ON r.flight_id = f.flight_id
     WHERE r.username = :uname AND f.flight_date >= TRUNC(SYSDATE)');
oci_bind_by_name($s, ':uname', $username); oci_execute($s);
$upcoming = oci_fetch_assoc($s)['UPCOMING'];
oci_free_statement($s);

// Average monthly flights past 12 months (including current month)
$s = oci_parse($conn,
    "SELECT COUNT(*) AS cnt
     FROM reservation r JOIN flight f ON r.flight_id = f.flight_id
     WHERE r.username = :uname
       AND f.flight_date >= TRUNC(ADD_MONTHS(SYSDATE, -11), 'MM')
       AND f.flight_date <  ADD_MONTHS(TRUNC(SYSDATE, 'MM'), 1)");
oci_bind_by_name($s, ':uname', $username); oci_execute($s);
$past12 = oci_fetch_assoc($s)['CNT'];
oci_free_statement($s);
$avg_monthly = number_format($past12 / 12, 2);

// Diamond Customer Score
$s = oci_parse($conn, 'SELECT AVG(seating_grade) AS score FROM reservation WHERE username = :uname');
oci_bind_by_name($s, ':uname', $username); oci_execute($s);
$row   = oci_fetch_assoc($s);
$score = $row['SCORE'] !== null ? number_format($row['SCORE'], 2) : 'N/A';
oci_free_statement($s);

// Full reservation list
$s = oci_parse($conn,
    'SELECT r.flight_id, fr.airline_name, fr.flight_number, f.flight_date, r.seating_grade
     FROM reservation r
     JOIN flight f       ON r.flight_id  = f.flight_id
     JOIN flight_route fr ON f.route_id  = fr.route_id
     WHERE r.username = :uname
     ORDER BY f.flight_date DESC');
oci_bind_by_name($s, ':uname', $username); oci_execute($s);
$rows = [];
while ($r = oci_fetch_assoc($s)) $rows[] = $r;
oci_free_statement($s);
oci_close($conn);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>My Reservations</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Reservation Information</h2>
  <div class="card">
    <table class="info-table">
      <tr><th>Total Reservations</th>      <td><?= $total ?></td></tr>
      <tr><th>Upcoming Reservations</th>   <td><?= $upcoming ?></td></tr>
      <tr><th>Avg Monthly Flights (12 mo)</th><td><?= $avg_monthly ?></td></tr>
      <tr><th>Diamond Customer Score</th>  <td><?= $score ?></td></tr>
    </table>
  </div>

  <h2>My Flights</h2>
  <?php if (empty($rows)): ?>
    <p>No reservations yet. <a href="book.php">Book a flight</a>.</p>
  <?php else: ?>
  <div class="card">
    <table class="data-table">
      <thead>
        <tr>
          <th>Flight ID</th><th>Airline</th><th>Flight #</th>
          <th>Date</th><th>Seating Grade</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['FLIGHT_ID']) ?></td>
          <td><?= htmlspecialchars($r['AIRLINE_NAME']) ?></td>
          <td><?= htmlspecialchars($r['FLIGHT_NUMBER']) ?></td>
          <td><?= htmlspecialchars(date('Y-m-d', strtotime($r['FLIGHT_DATE']))) ?></td>
          <td><?= htmlspecialchars($r['SEATING_GRADE']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
