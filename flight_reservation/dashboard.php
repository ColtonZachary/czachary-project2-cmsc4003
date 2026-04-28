<?php
// admin/dashboard.php - List and search customers
require_once '../auth.php';
check_session('admin');

$conn = get_db();
$name     = trim($_GET['name']    ?? '');
$airline  = trim($_GET['airline'] ?? '');
$fnum     = trim($_GET['fnum']    ?? '');
$ctype    = trim($_GET['ctype']   ?? '');
$diamond  = $_GET['diamond'] ?? '';

$where = "WHERE 1=1";
if ($name)    $where .= " AND (LOWER(u.first_name || ' ' || u.last_name) LIKE LOWER('%$name%'))";
if ($airline) $where .= " AND EXISTS (SELECT 1 FROM reservation r JOIN flight f ON r.flight_id=f.flight_id JOIN flight_route fr ON f.route_id=fr.route_id WHERE r.username=c.username AND UPPER(fr.airline_name)=UPPER('$airline'))";
if ($fnum)    $where .= " AND EXISTS (SELECT 1 FROM reservation r JOIN flight f ON r.flight_id=f.flight_id JOIN flight_route fr ON f.route_id=fr.route_id WHERE r.username=c.username AND fr.flight_number=" . (int)$fnum . ")";
if ($ctype)   $where .= " AND c.cust_type = '" . ($ctype === 'domestic' ? 'domestic' : 'foreign') . "'";
if ($diamond !== '') $where .= " AND c.diamond_status = " . ($diamond ? 1 : 0);

$sql = "SELECT u.username, u.first_name, u.last_name, c.phone_number, c.cust_type, c.diamond_status
        FROM users u JOIN customer c ON u.username = c.username
        $where ORDER BY u.last_name, u.first_name";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$customers = [];
while ($r = oci_fetch_assoc($stmt)) $customers[] = $r;
oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Admin Dashboard</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Customer Management</h2>

  <div class="card">
    <form method="get" action="dashboard.php" class="search-form">
      <label>Name <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Substring search"></label>
      <label>Airline <input type="text" name="airline" value="<?= htmlspecialchars($airline) ?>" maxlength="2"></label>
      <label>Flight # <input type="text" name="fnum" value="<?= htmlspecialchars($fnum) ?>"></label>
      <label>Type
        <select name="ctype">
          <option value="">All</option>
          <option value="domestic" <?= $ctype==='domestic'?'selected':'' ?>>Domestic</option>
          <option value="foreign"  <?= $ctype==='foreign' ?'selected':'' ?>>Foreign</option>
        </select>
      </label>
      <label>Diamond
        <select name="diamond">
          <option value="">All</option>
          <option value="1" <?= $diamond==='1'?'selected':'' ?>>Yes</option>
          <option value="0" <?= $diamond==='0'?'selected':'' ?>>No</option>
        </select>
      </label>
      <button type="submit">Search</button>
      <a class="btn" href="dashboard.php">Clear</a>
    </form>
  </div>

  <div class="card">
    <a class="btn" href="add_customer.php">+ Add New Customer</a>
    <table class="data-table" style="margin-top:1rem">
      <thead>
        <tr><th>Username</th><th>Name</th><th>Phone</th><th>Type</th><th>Diamond</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['USERNAME']) ?></td>
          <td><?= htmlspecialchars($c['FIRST_NAME'] . ' ' . $c['LAST_NAME']) ?></td>
          <td><?= htmlspecialchars($c['PHONE_NUMBER']) ?></td>
          <td><?= ucfirst(htmlspecialchars($c['CUST_TYPE'])) ?></td>
          <td><?= $c['DIAMOND_STATUS'] ? '★ Diamond' : 'Standard' ?></td>
          <td>
            <a class="btn-sm" href="edit_customer.php?u=<?= urlencode($c['USERNAME']) ?>">Edit</a>
            <a class="btn-sm danger" href="delete_customer.php?u=<?= urlencode($c['USERNAME']) ?>"
               onclick="return confirm('Delete this customer?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
          <tr><td colspan="6">No customers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <h3>Enter Seating Grade</h3>
    <a class="btn" href="seating_grade.php">Go to Seating Grades</a>
  </div>
</div>
</body>
</html>
