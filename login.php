 <?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        header('Location: index.php');
        exit;
    }

    sleep(1); /* slows down password guessing */
    $error = 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | UEW Innovation Hub</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif;
       background: linear-gradient(135deg, #102a72, #4338ca 55%, #6d28d9); padding: 20px; }
.card { width: 100%; max-width: 400px; background: #fff; border-radius: 18px; padding: 32px; box-shadow: 0 25px 70px rgba(0,0,0,.3); }
h1 { margin: 0 0 4px; color: #102a72; font-size: 24px; }
p.sub { margin: 0 0 24px; color: #6b7280; font-size: 14px; }
label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: bold; color: #111827; }
input { width: 100%; padding: 13px 14px; border: 1px solid #d1d5db; border-radius: 9px; font-size: 15px; margin-bottom: 16px; }
input:focus { outline: none; border-color: #6d28d9; box-shadow: 0 0 0 3px rgba(109,40,217,.1); }
button { width: 100%; padding: 13px; border: none; border-radius: 10px; font-size: 15px; font-weight: bold; color: #fff; cursor: pointer;
         background: linear-gradient(135deg, #102a72, #6d28d9); }
.pw { position: relative; margin-bottom: 16px; }
.pw input { margin-bottom: 0; padding-right: 75px; }
.pw .toggle { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: auto; padding: 6px 11px; background: #eef2ff; color: #4338ca; border-radius: 7px; font-size: 12px; }
.error { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; padding: 11px 13px; border-radius: 7px; margin-bottom: 16px; font-size: 14px; }
</style>
</head>
<body>
<form class="card" method="post" autocomplete="off">
    <h1>UEW Innovation Hub</h1>
    <p class="sub">Sign in to the Project Management System</p>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <label>Username</label>
    <input type="text" name="username" required autofocus>
    <label>Password</label>
    <div class="pw"><input type="password" name="password" id="pw" required><button type="button" class="toggle" onclick="togglePw('pw', this)">Show</button></div>
    <button type="submit">Sign In</button>
</form>
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