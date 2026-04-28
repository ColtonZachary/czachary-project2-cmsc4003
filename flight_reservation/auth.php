<?php
// auth.php - Include at top of every protected page
// Usage:
//   require_once '../auth.php';          // from admin/ or customer/
//   check_session();                     // any logged-in user
//   check_session('admin');              // admins only
//   check_session('customer');           // customers only

require_once dirname(__FILE__) . '/config.php';

function check_session($role = null) {
    $session_id = $_COOKIE['session_id'] ?? '';
    $username   = $_COOKIE['username']   ?? '';

    if (!$session_id || !$username) {
        header('Location: /login.php');
        exit;
    }

    $conn = get_db();
    $sql  = 'SELECT username FROM user_sessions WHERE session_id = :sid AND username = :uname';
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':sid',   $session_id);
    oci_bind_by_name($stmt, ':uname', $username);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$row) {
        // Invalid session
        setcookie('session_id', '', time() - 3600, '/');
        setcookie('username',   '', time() - 3600, '/');
        header('Location: /login.php');
        exit;
    }

    // Role check
    if ($role === 'admin') {
        $r = oci_parse($conn, 'SELECT COUNT(*) AS cnt FROM admin_users WHERE username = :uname');
        oci_bind_by_name($r, ':uname', $username);
        oci_execute($r);
        $cnt = oci_fetch_assoc($r);
        oci_free_statement($r);
        if ($cnt['CNT'] == 0) {
            header('Location: /customer/info.php');
            exit;
        }
    } elseif ($role === 'customer') {
        $r = oci_parse($conn, 'SELECT COUNT(*) AS cnt FROM customer WHERE username = :uname');
        oci_bind_by_name($r, ':uname', $username);
        oci_execute($r);
        $cnt = oci_fetch_assoc($r);
        oci_free_statement($r);
        if ($cnt['CNT'] == 0) {
            header('Location: /admin/dashboard.php');
            exit;
        }
    }

    oci_close($conn);
    return $username;
}

function logout() {
    $session_id = $_COOKIE['session_id'] ?? '';
    $username   = $_COOKIE['username']   ?? '';
    if ($session_id) {
        $conn = get_db();
        $stmt = oci_parse($conn, 'DELETE FROM user_sessions WHERE session_id = :sid');
        oci_bind_by_name($stmt, ':sid', $session_id);
        oci_execute($stmt);
        oci_commit($conn);
        oci_close($conn);
    }
    setcookie('session_id', '', time() - 3600, '/');
    setcookie('username',   '', time() - 3600, '/');
    header('Location: /login.php');
    exit;
}
?>
