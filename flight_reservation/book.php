<?php
// customer/book.php - Search flights and make reservations
require_once '../auth.php';
$username = check_session('customer');

$conn    = get_db();
$message = '';
$error   = '';

// ---- Search parameters ----
$search_airline  = trim($_GET['airline']  ?? '');
$search_number   = trim($_GET['flight_no'] ?? '');
$search_date     = trim($_GET['date']     ?? '');

// ---- Build flight search query ----
$where = "WHERE fa.flight_date >= TRUNC(SYSDATE)";
if ($search_airline !== '')
    $where .= " AND UPPER(fa.airline_name) = UPPER('$search_airline')";
if ($search_number !== '')
    $where .= " AND fa.flight_number = " . (int)$search_number;
if ($search_date !== '')
    $where .= " AND TRUNC(fa.flight_date) = TO_DATE('$search_date','YYYY-MM-DD')";

$sql  = "SELECT fa.flight_id, fa.airline_name, fa.flight_number,
                fa.flight_date, fa.capacity, fa.available_seats
         FROM flight_availability fa
         $where
         ORDER BY fa.flight_date, fa.airline_name, fa.flight_number";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$flights = [];
while ($r = oci_fetch_assoc($stmt)) $flights[] = $r;
oci_free_statement($stmt);

// ---- Handle reservation submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'single';

    if ($mode === 'single') {
        $fid = (int)($_POST['flight_id'] ?? 0);
        reserve_single($conn, $username, $fid, $message, $error);
    } else {
        // Multi-flight (up to 3)
        $fids = array_filter(array_map('intval', [
            $_POST['fid1'] ?? 0,
            $_POST['fid2'] ?? 0,
            $_POST['fid3'] ?? 0,
        ]));
        reserve_multi($conn, $username, array_values($fids), $message, $error);
    }
}
oci_close($conn);

// ---- Single flight reservation ----
function reserve_single($conn, $username, $fid, &$msg, &$err) {
    if ($fid <= 0) { $err = 'No flight selected.'; return; }

    // Concurrency: use SELECT FOR UPDATE to lock the flight row
    // then check conditions inside a PL/SQL block
    $plsql = "
    DECLARE
        v_date     DATE;
        v_avail    NUMBER;
        v_dup      NUMBER;
        v_err      VARCHAR2(200);
    BEGIN
        SELECT f.flight_date,
               f.capacity - (SELECT COUNT(*) FROM reservation WHERE flight_id = :fid),
               (SELECT COUNT(*) FROM reservation WHERE username = :uname AND flight_id = :fid2)
        INTO v_date, v_avail, v_dup
        FROM flight f
        WHERE f.flight_id = :fid3
        FOR UPDATE;              -- locks flight row

        IF v_date < TRUNC(SYSDATE) THEN
            v_err := 'Cannot reserve past flights.';
        ELSIF v_dup > 0 THEN
            v_err := 'You have already reserved this flight.';
        ELSIF v_avail <= 0 THEN
            v_err := 'No available seats on this flight.';
        ELSE
            INSERT INTO reservation (username, flight_id, seating_grade)
            VALUES (:uname2, :fid4, 0);
            COMMIT;
            v_err := 'OK';
        END IF;
        :result := v_err;
    EXCEPTION
        WHEN OTHERS THEN
            ROLLBACK;
            :result := 'Database error: ' || SQLERRM;
    END;";

    $stmt = oci_parse($conn, $plsql);
    oci_bind_by_name($stmt, ':fid',   $fid);
    oci_bind_by_name($stmt, ':uname', $username);
    oci_bind_by_name($stmt, ':fid2',  $fid);
    oci_bind_by_name($stmt, ':fid3',  $fid);
    oci_bind_by_name($stmt, ':uname2',$username);
    oci_bind_by_name($stmt, ':fid4',  $fid);
    $result = '';
    oci_bind_by_name($stmt, ':result', $result, 200);
    oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($stmt);

    if ($result === 'OK') {
        $msg = "Flight $fid successfully reserved!";
    } else {
        $err = $result;
    }
}

// ---- Multi-flight reservation ----
function reserve_multi($conn, $username, $fids, &$msg, &$err) {
    if (count($fids) < 2) { $err = 'Please enter at least 2 flight IDs.'; return; }

    $plsql = "
    DECLARE
        TYPE id_array IS TABLE OF NUMBER INDEX BY PLS_INTEGER;
        v_fids   id_array;
        v_date   DATE;
        v_prev   DATE;
        v_avail  NUMBER;
        v_dup    NUMBER;
        v_prec   NUMBER;
        v_rid1   NUMBER;
        v_rid2   NUMBER;
        v_err    VARCHAR2(200) := 'OK';
        v_n      NUMBER := :n;
    BEGIN
        v_fids(1) := :f1;
        v_fids(2) := :f2;
        v_fids(3) := :f3;

        -- Check each flight
        FOR i IN 1..v_n LOOP
            SELECT f.flight_date,
                   f.capacity - (SELECT COUNT(*) FROM reservation WHERE flight_id = v_fids(i)),
                   (SELECT COUNT(*) FROM reservation WHERE username = :uname AND flight_id = v_fids(i)),
                   f.route_id
            INTO v_date, v_avail, v_dup, v_rid1
            FROM flight f
            WHERE f.flight_id = v_fids(i)
            FOR UPDATE;

            IF i = 1 THEN v_prev := v_date; END IF;
            IF v_date < TRUNC(SYSDATE) THEN
                v_err := 'Flights must be today or a future date.'; EXIT;
            END IF;
            IF TRUNC(v_date) != TRUNC(v_prev) THEN
                v_err := 'All flights must be on the same date.'; EXIT;
            END IF;
            IF v_dup > 0 THEN
                v_err := 'You already reserved flight ' || v_fids(i) || '.'; EXIT;
            END IF;
            IF v_avail <= 0 THEN
                v_err := 'No seats available on flight ' || v_fids(i) || '.'; EXIT;
            END IF;
            v_prev := v_date;
        END LOOP;

        -- Check preceding-flight rule
        IF v_err = 'OK' THEN
            FOR i IN 1..v_n - 1 LOOP
                SELECT f1.route_id, f2.route_id
                INTO   v_rid1, v_rid2
                FROM   flight f1, flight f2
                WHERE  f1.flight_id = v_fids(i)
                  AND  f2.flight_id = v_fids(i+1);

                SELECT COUNT(*) INTO v_prec
                FROM preceding_route
                WHERE preceding_route_id = v_rid1 AND following_route_id = v_rid2;

                IF v_prec = 0 THEN
                    v_err := 'Flight ' || v_fids(i) || ' does not precede flight ' || v_fids(i+1) || '.';
                    EXIT;
                END IF;
            END LOOP;
        END IF;

        -- Insert all reservations if all checks passed
        IF v_err = 'OK' THEN
            FOR i IN 1..v_n LOOP
                INSERT INTO reservation (username, flight_id, seating_grade)
                VALUES (:uname2, v_fids(i), 0);
            END LOOP;
            COMMIT;
        ELSE
            ROLLBACK;
        END IF;
        :result := v_err;
    EXCEPTION
        WHEN OTHERS THEN
            ROLLBACK;
            :result := 'Database error: ' || SQLERRM;
    END;";

    $n    = count($fids);
    $f1   = $fids[0] ?? 0;
    $f2   = $fids[1] ?? 0;
    $f3   = $fids[2] ?? 0;

    $stmt = oci_parse($conn, $plsql);
    oci_bind_by_name($stmt, ':n',     $n);
    oci_bind_by_name($stmt, ':f1',    $f1);
    oci_bind_by_name($stmt, ':f2',    $f2);
    oci_bind_by_name($stmt, ':f3',    $f3);
    oci_bind_by_name($stmt, ':uname', $username);
    oci_bind_by_name($stmt, ':uname2',$username);
    $result = '';
    oci_bind_by_name($stmt, ':result', $result, 200);
    oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    oci_free_statement($stmt);

    if ($result === 'OK') {
        $msg = "Successfully reserved " . count($fids) . " flights!";
    } else {
        $err = $result;
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Book Flights</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Book a Flight</h2>

  <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

  <!-- Search form -->
  <div class="card">
    <h3>Search Flights</h3>
    <form method="get" action="book.php">
      <div class="form-row">
        <label>Airline <input type="text" name="airline"   value="<?= htmlspecialchars($search_airline) ?>" maxlength="2" placeholder="AA"></label>
        <label>Flight # <input type="text" name="flight_no" value="<?= htmlspecialchars($search_number) ?>" placeholder="100"></label>
        <label>Date <input type="date" name="date" value="<?= htmlspecialchars($search_date) ?>"></label>
        <button type="submit">Search</button>
      </div>
    </form>
  </div>

  <!-- Flight list -->
  <?php if (!empty($flights)): ?>
  <div class="card">
    <table class="data-table">
      <thead>
        <tr><th>Flight ID</th><th>Airline</th><th>Flight #</th><th>Date</th><th>Capacity</th><th>Available</th><th>Reserve</th></tr>
      </thead>
      <tbody>
        <?php foreach ($flights as $f): ?>
        <tr>
          <td><?= $f['FLIGHT_ID'] ?></td>
          <td><?= htmlspecialchars($f['AIRLINE_NAME']) ?></td>
          <td><?= $f['FLIGHT_NUMBER'] ?></td>
          <td><?= date('Y-m-d', strtotime($f['FLIGHT_DATE'])) ?></td>
          <td><?= $f['CAPACITY'] ?></td>
          <td><?= $f['AVAILABLE_SEATS'] ?></td>
          <td>
            <form method="post" action="book.php">
              <input type="hidden" name="mode"      value="single">
              <input type="hidden" name="flight_id" value="<?= $f['FLIGHT_ID'] ?>">
              <button type="submit" <?= $f['AVAILABLE_SEATS'] <= 0 ? 'disabled' : '' ?>>Reserve</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($search_airline || $search_number || $search_date)): ?>
    <p>No flights found matching your search.</p>
  <?php endif; ?>

  <!-- Multi-flight reservation -->
  <div class="card">
    <h3>Multi-Flight Reservation (up to 3)</h3>
    <form method="post" action="book.php">
      <input type="hidden" name="mode" value="multi">
      <label>Flight ID 1 <input type="number" name="fid1" required></label>
      <label>Flight ID 2 <input type="number" name="fid2" required></label>
      <label>Flight ID 3 (optional) <input type="number" name="fid3"></label>
      <button type="submit">Reserve Sequence</button>
    </form>
  </div>
</div>
</body>
</html>
