<?php
// admin/create-admin.php — bootstrap or add an administrator account
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/config.php';

$isLoggedIn = is_logged_in();
$count = (int)db()->query('SELECT COUNT(*) c FROM admins')->fetch()['c'];
$msg   = '';
$error = '';

/* The very first administrator can be created without being signed in.
   Extra accounts require an existing admin session. */
$mayCreate = ($count === 0) || $isLoggedIn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mayCreate) {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['confirm_password'] ?? '');

        if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $error = 'Please choose a username of 3–50 letters, numbers, dots, dashes or underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (mb_strlen($password) < 8) {
            $error = 'The password must be at least 8 characters long.';
        } elseif ($password !== $confirm) {
            $error = 'The password and confirmation do not match.';
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)');
                $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $count++;
                $msg = 'Administrator “' . $username . '” was created successfully.';
            } catch (Throwable $e) {
                $error = 'The account could not be created: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Admin | <?php echo esc(site_name()); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: '#234B36',
          secondary: '#477A53',
          cream: '#F8F6EF',
          gold: '#D8A441',
          charcoal: '#252B26'
        },
        fontFamily: { heading: ['Poppins', 'sans-serif'], body: ['Inter', 'sans-serif'] }
      }
    }
  }
</script>
</head>
<body class="font-body bg-cream min-h-screen flex items-center justify-center px-4 py-10">

  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <a href="../index.php" class="inline-flex items-center gap-2 text-primary">
        <span class="text-3xl" aria-hidden="true">🐔</span>
        <span class="font-heading font-bold text-2xl"><?php echo esc(site_name()); ?></span>
      </a>
      <p class="text-gray-500 text-sm mt-1">Administration Dashboard</p>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-xl">
      <h1 class="font-heading font-bold text-2xl text-primary mb-1">
        <?php echo $count === 0 ? 'Create Initial Admin' : 'Create Administrator'; ?>
      </h1>
      <p class="text-sm text-gray-500 mb-6">
        <?php echo $count === 0
            ? 'No administrator account exists yet. Create one to access the dashboard.'
            : $count . ' administrator account(s) already exist.'; ?>
      </p>

      <?php if ($msg !== ''): ?>
        <div class="bg-green-100 text-green-800 p-3 rounded-lg mb-5 text-sm" role="status"><?php echo esc($msg); ?></div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-5 text-sm" role="alert"><?php echo esc($error); ?></div>
      <?php endif; ?>

      <?php if ($mayCreate): ?>
        <form method="post" action="create-admin.php" class="space-y-4">
          <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">

          <div>
            <label for="username" class="block text-sm font-medium mb-1">Username *</label>
            <input id="username" name="username" required minlength="3" maxlength="50"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </div>

          <div>
            <label for="email" class="block text-sm font-medium mb-1">Email Address *</label>
            <input id="email" name="email" type="email" required maxlength="100"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </div>

          <div>
            <label for="password" class="block text-sm font-medium mb-1">Password *</label>
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters.</p>
          </div>

          <div>
            <label for="confirm_password" class="block text-sm font-medium mb-1">Confirm Password *</label>
            <input id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </div>

          <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-secondary transition font-medium">
            Create Admin
          </button>
        </form>
      <?php else: ?>
        <p class="text-sm text-gray-600">
          An administrator account already exists. Sign in to manage the dashboard, or sign in first and
          come back here to add another account.
        </p>
      <?php endif; ?>

      <p class="text-center mt-6">
        <a href="login.php" class="text-sm text-primary hover:underline">Back to login</a>
      </p>
    </div>
  </div>

</body>
</html>
