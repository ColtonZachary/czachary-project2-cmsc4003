<?php
// admin/add_customer.php - Add new customer with auto-generated username
require_once '../auth.php';
check_session('admin');

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first    = trim($_POST['first_name']    ?? '');
    $last     = trim($_POST['last_name']     ?? '');
    $password = trim($_POST['password']      ?? '');
    $phone    = trim($_POST['phone']         ?? '');
    $ctype    = $_POST['ctype']              ?? '';
    $location = trim($_POST['location']      ?? ''); // state or country

    if (!$first || !$last || !$password || !$phone || !$ctype || !$location) {
        $error = 'All fields are required.';
    } else {
        $conn = get_db();

        /*  Concurrency control for username generation:
         *  We use a PL/SQL loop with DUP_VAL_ON_INDEX handling
         *  (same idea as PLSQL_exception.txt from the course notes).
         *  Two admins can try to generate a username for the same initials
         *  at the same time; the UNIQUE constraint on users.username
         *  will reject the second insert.  We simply increment and retry.
         */
        $plsql = "
        DECLARE
            v_initials  VARCHAR2(2)  := UPPER(SUBSTR(:first,1,1)) || UPPER(SUBSTR(:last,1,1));
            v_max_seq   NUMBER;
            v_new_seq   NUMBER;
            v_username  VARCHAR2(30);
        BEGIN
            LOOP
                SELECT NVL(MAX(TO_NUMBER(SUBSTR(username, 3))), 0)
                INTO   v_max_seq
                FROM   users
                WHERE  REGEXP_LIKE(username, '^' || v_initials || '[0-9]{6}$');

                v_new_seq  := v_max_seq + 1;
                v_username := v_initials || LPAD(TO_CHAR(v_new_seq), 6, '0');

                BEGIN
                    INSERT INTO users (username, password, first_name, last_name)
                    VALUES (v_username, :pass, :first2, :last2);
                    COMMIT;
                    EXIT;  -- success, leave loop
                EXCEPTION
                    WHEN DUP_VAL_ON_INDEX THEN
                        ROLLBACK;
                        -- Another admin just used this username; retry with incremented seq
                END;
            END LOOP;
            :out_username := v_username;
        END;";

        $stmt = oci_parse($conn, $plsql);
        oci_bind_by_name($stmt, ':first',  $first);
        oci_bind_by_name($stmt, ':last',   $last);
        oci_bind_by_name($stmt, ':pass',   $password);
        oci_bind_by_name($stmt, ':first2', $first);
        oci_bind_by_name($stmt, ':last2',  $last);
        $new_username = '';
        oci_bind_by_name($stmt, ':out_username', $new_username, 30);
        $ok = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmt);

        if (!$ok || !$new_username) {
            $error = 'Failed to generate username.';
        } else {
            // Insert customer record
            $s = oci_parse($conn,
                'INSERT INTO customer (username, phone_number, cust_type, diamond_status, reg_date)
                 VALUES (:u, :ph, :ct, 0, SYSDATE)');
            oci_bind_by_name($s, ':u',  $new_username);
            oci_bind_by_name($s, ':ph', $phone);
            oci_bind_by_name($s, ':ct', $ctype);
            oci_execute($s);
            oci_free_statement($s);

            if ($ctype === 'domestic') {
                $s = oci_parse($conn, 'INSERT INTO domestic_customer VALUES (:u, :loc)');
            } else {
                $s = oci_parse($conn, 'INSERT INTO foreign_customer VALUES (:u, :loc)');
            }
            oci_bind_by_name($s, ':u',   $new_username);
            oci_bind_by_name($s, ':loc', strtoupper(substr($location, 0, 2)));
            oci_execute($s);
            oci_free_statement($s);
            oci_commit($conn);
            $message = "Customer added! Auto-generated username: <strong>$new_username</strong>";
        }
        oci_close($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Add Customer</title><link rel="stylesheet" href="../style.css"></head>
<body>
<div class="container">
  <?php include 'nav.php'; ?>
  <h2>Add New Customer</h2>
  <div class="card">
    <?php if ($message): ?><p class="success"><?= $message ?></p><?php endif; ?>
    <?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <p class="hint">Username is auto-generated as <em>Initials + 6-digit sequence</em> (e.g. JD000001).</p>
    <form method="post">
      <label>First Name    <input type="text"     name="first_name" required></label>
      <label>Last Name     <input type="text"     name="last_name"  required></label>
      <label>Password      <input type="password" name="password"   required></label>
      <label>Phone         <input type="text"     name="phone"      required></label>
      <label>Type
        <select name="ctype" id="ctype" onchange="toggleLabel(this.value)">
          <option value="domestic">Domestic</option>
          <option value="foreign">Foreign</option>
        </select>
      </label>
      <label id="loc-label">State (2-letter) <input type="text" name="location" maxlength="2" required></label>
      <button type="submit">Add Customer</button>
    </form>
  </div>
</div>
<script>
function toggleLabel(v) {
  document.getElementById('loc-label').firstChild.nodeValue =
    v === 'domestic' ? 'State (2-letter) ' : 'Country (2-letter) ';
}
</script>
</body>
</html>
