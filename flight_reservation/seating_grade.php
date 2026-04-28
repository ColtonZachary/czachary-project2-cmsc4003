<?php
// admin/seating_grade.php - Enter seating grade; trigger auto-updates Diamond status
require_once '../auth.php';
check_session('admin');
$conn = get_db();
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cust_username = trim($_POST['username']      ?? '');
    $flight_id     = (int)($_POST['flight_id']    ?? 0);
    $grade         = (int)($_POST['seating_grade'] ?? 0);

    if (!$cust_username || !$flight_id || !in_array($grade, [0,1,2])) {
        $error = 'Please fill in all fields. Grade must be 0, 1, or 2.';
    } else {
        $s = oci_parse($conn,
            'UPDATE reservation SET seating_grade = :g
             WHERE username = :u AND flight_id = :f');
        oci_bind_by_name($s, ':g', $grade);
        oci_bind_by_name($s, ':u', $cust_username);
        oci_bind_by_name($s, ':f', $flight_id);
        oci_execute($s);
        $rows = oci_num_rows($s);
        oci_free_statement($s);

        if ($rows == 0) {
            $error = 'Reservation not found for that username + flight ID.';
            oci_rollback($conn);
        } else {
            oci_commit($conn);
            // Fetch updated diamond status (trigger ran)
            $d = oci_parse($conn, 'SELECT diamond_status FROM customer WHERE username=:u');
            oci_bind_by_name($d, ':u', $cust_username);
            oci_execute($d);
            $ds = oci_fetch_assoc($d)['DIAMOND_STATUS'];
            oci_free_statement($d);
            $message = "Seating grade updated to $grade. Diamond status: " . ($ds ? '★ Diamond' : 'Standard');
        }
    }
}

// Load all reservations for display
$s = oci_parse($conn,
    'SELECT r.username, r.flight_id, r.seating_grade,
            fr.airline_name, fr.flight_number, f.flight_date, c.diamond_status
     FROM reservation r
     JOIN flight f        ON r.flight_id  = f.flight_id
     JOIN flight_route fr ON f.route_id   = fr.route_id
     JOIN customer c      ON r.username   = c.username
     ORDER BY r.username, f.flight_date');
oci_execute($s);
$rows = [];
while ($r = oci_fetch_assoc($s)) $rows[] = $r;
oci_free_statement($s);
oci_close($conn);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Seating Grades</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Enter Seating Grade</h2>
  <div class="card">
    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
      <label>Customer Username <input type="text" name="username"   required placeholder="JD000001"></label>
      <label>Flight ID         <input type="number" name="flight_id" required></label>
      <label>Seating Grade
        <select name="seating_grade">
          <option value="0">0</option>
          <option value="1">1</option>
          <option value="2">2</option>
        </select>
      </label>
      <button type="submit">Update Grade</button>
    </form>
  </div>

  <div class="card">
    <h3>All Reservations</h3>
    <table class="data-table">
      <thead>
        <tr><th>Username</th><th>Flight ID</th><th>Airline</th><th>Flight #</th><th>Date</th><th>Grade</th><th>Diamond</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['USERNAME']) ?></td>
          <td><?= $r['FLIGHT_ID'] ?></td>
          <td><?= htmlspecialchars($r['AIRLINE_NAME']) ?></td>
          <td><?= $r['FLIGHT_NUMBER'] ?></td>
          <td><?= date('Y-m-d', strtotime($r['FLIGHT_DATE'])) ?></td>
          <td><?= $r['SEATING_GRADE'] ?></td>
          <td><?= $r['DIAMOND_STATUS'] ? '★' : '' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
