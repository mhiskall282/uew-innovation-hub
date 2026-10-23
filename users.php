 <?php
require_once __DIR__ . '/auth.php';
require_admin();
$conn = db();

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $result = '';

    if ($action === 'add') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $r = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'viewer';
        if ($u === '' || strlen($p) < 8) {
            $result = 'Username required and password must be at least 8 characters.';
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            try {
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $u, $hash, $r);
                $stmt->execute();
                $result = "User '$u' added.";
            } catch (mysqli_sql_exception $e) {
                $result = ($e->getCode() === 1062)
                    ? "The username '$u' already exists. Choose a different one."
                    : 'Could not add the user: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        if ($id === (int)$_SESSION['user_id']) {
            $result = 'You cannot delete your own account.';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = 'User deleted.';
        }
    } elseif ($action === 'reset') {
        $p = $_POST['password'] ?? '';
        if (strlen($p) < 8) {
            $result = 'Password must be at least 8 characters.';
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            $result = 'Password updated.';
        }
    }

    $_SESSION['flash'] = $result;
    header('Location: users.php');
    exit;
}

$users = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users | UEW Innovation Hub</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; background: #f5f7fb; color: #374151; }
.bar { background: linear-gradient(135deg, #102a72, #6d28d9); color: #fff; padding: 18px 5%; display: flex; justify-content: space-between; align-items: center; }
.bar a { color: #fff; text-decoration: none; background: rgba(255,255,255,.18); padding: 9px 14px; border-radius: 8px; }
.wrap { max-width: 900px; margin: 30px auto; padding: 0 20px; }
.box { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 22px; margin-bottom: 22px; }
h2 { margin: 0 0 14px; color: #102a72; font-size: 19px; }
input, select { padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
button { padding: 10px 16px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; background: #3730a3; color: #fff; }
button.danger { background: #fee2e2; color: #b91c1c; }
button.light { background: #eef2ff; color: #4338ca; }
.row { display: flex; gap: 10px; flex-wrap: wrap; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 11px; border-bottom: 1px solid #e5e7eb; text-align: left; }
.flash { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; padding: 11px 13px; border-radius: 7px; margin-bottom: 18px; }
.pwwrap { position: relative; display: inline-block; }
.pwwrap input { padding-right: 70px; }
.pwwrap .toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); padding: 5px 9px; font-size: 12px; background: #eef2ff; color: #4338ca; }
.actions { display: flex; gap: 8px; }
</style>
</head>
<body>
<div class="bar"><strong>User Management</strong><a href="index.php">← Back to Projects</a></div>
<div class="wrap">

<?php if ($flash): ?><div class="flash"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

<div class="box">
    <h2>Add user</h2>
    <form method="post" class="row">
        <input type="hidden" name="csrf" value="<?php echo $token; ?>">
        <input type="hidden" name="action" value="add">
        <input name="username" placeholder="Username" autocomplete="off" required>
        <span class="pwwrap"><input type="password" name="password" id="newPw" placeholder="Password (8+ chars)" autocomplete="new-password" required><button type="button" class="toggle" onclick="togglePw('newPw', this)">Show</button></span>
        <select name="role">
            <option value="viewer">Viewer (read-only)</option>
            <option value="admin">Admin (full access)</option>
        </select>
        <button type="submit">Add</button>
    </form>
</div>

<div class="box">
    <h2>Users</h2>
    <table>
        <tr><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo htmlspecialchars($u['username']); ?></td>
            <td><?php echo htmlspecialchars($u['role']); ?></td>
            <td><?php echo htmlspecialchars($u['created_at']); ?></td>
            <td class="actions">
                <form method="post" onsubmit="var p=prompt('New password (8+ characters):'); if(!p){return false;} this.password.value=p;">
                    <input type="hidden" name="csrf" value="<?php echo $token; ?>">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                    <input type="hidden" name="password" value="">
                    <button type="submit" class="light">Reset password</button>
                </form>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                <form method="post" onsubmit="return confirm('Delete this user?');">
                    <input type="hidden" name="csrf" value="<?php echo $token; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                    <button type="submit" class="danger">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</div>
<script>
function togglePw(id, btn) {
    var i = document.getElementById(id);
    var show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Hide' : 'Show';
}
</script>
</body>
</html>