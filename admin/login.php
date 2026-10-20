<?php
// admin/login.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Please enter both your username and password.';
        } elseif (login_admin($username, $password)) {
            flash('success', 'Welcome back, ' . (($_SESSION['admin_username'] ?? 'admin')) . '!');
            redirect('dashboard.php');
        } else {
            $error = 'Invalid credentials. Please check your username and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | <?php echo esc(site_name()); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

  <div class="w-full max-w-md sm:max-w-lg lg:max-w-md">
    <div class="text-center mb-6">
      <a href="../index.php" class="inline-flex items-center gap-2 text-primary">
        <span class="text-3xl" aria-hidden="true">🐔</span>
        <span class="font-heading font-bold text-2xl sm:text-3xl"><?php echo esc(site_name()); ?></span>
      </a>
      <p class="text-gray-500 text-sm mt-1">Administration Dashboard</p>
    </div>

    <form method="post" action="login.php" class="bg-white p-6 sm:p-8 rounded-2xl shadow-xl">
      <h1 class="font-heading font-bold text-2xl text-primary mb-1">Admin Login</h1>
      <p class="text-sm text-gray-500 mb-6">Sign in to manage the catalog and inventory.</p>

      <?php if ($error !== ''): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-5 text-sm" role="alert"><?php echo esc($error); ?></div>
      <?php endif; ?>

      <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">

      <label for="username" class="block text-sm font-medium mb-1">Username or Email</label>
      <input id="username" name="username" value="<?php echo esc($username); ?>" required autocomplete="username"
             class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-5 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">

      <label for="password" class="block text-sm font-medium mb-1">Password</label>
      <input id="password" name="password" type="password" required autocomplete="current-password"
             class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-6 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">

      <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-secondary transition font-medium">
        Login
      </button>

      <p class="text-xs text-gray-400 text-center mt-5">
        Default demo account: <span class="font-semibold text-gray-600">admin</span> /
        <span class="font-semibold text-gray-600">admin123</span>
      </p>
    </form>

    <p class="text-center mt-6">
      <a href="../index.php" class="text-sm text-gray-500 hover:text-primary">&larr; Back to the public website</a>
    </p>
  </div>

</body>
</html>
