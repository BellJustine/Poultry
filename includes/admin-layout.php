<?php
// includes/admin-layout.php
// Shared shell for every page of the admin dashboard.
// Fixes: missing Tailwind brand palette, missing mobile navigation and
// duplicated sidebar markup across the admin pages.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

function admin_nav_items(): array {
    return [
        'dashboard.php'   => ['Dashboard Overview', 'M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z'],
        'chickens.php'    => ['Manage Chickens', 'M12 3c-4.97 0-9 3.58-9 8 0 1.2.3 2.4.85 3.5L3 21l6.7-1.8A8.9 8.9 0 0 0 12 20c4.97 0 9-3.58 9-8s-4.03-9-9-9Zm-3 9a1.3 1.3 0 1 1 0-2.6A1.3 1.3 0 0 1 9 12Zm3 3a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Zm3-3a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Z'],
        'add-chicken.php' => ['Add New Chicken', 'M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5Z'],
        'categories.php'  => ['Age Categories', 'M3 5h18v4H3V5Zm0 6h7v8H3v-8Zm9 0h9v8h-9v-8Z'],
        'inventory.php'   => ['Inventory', 'M3 7l9-4 9 4-9 4-9-4Zm2 5.6L12 16l7-3.4V18l-7 3-7-3v-5.4Z'],
        'messages.php'    => ['Messages', 'M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z'],
        'settings.php'    => ['Settings', 'M12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Zm7.4-2.6.1-.9-.1-.9 1.9-1.5-1.9-3.3-2.3.8a7.3 7.3 0 0 0-1.6-.9L15.2 3h-3.8l-.3 2.3a7.3 7.3 0 0 0-1.6.9l-2.3-.8L5.2 7.7l1.9 1.5-.1.9.1.9-1.9 1.5 1.9 3.3 2.3-.8a7.3 7.3 0 0 0 1.6.9l.3 2.3h3.8l.3-2.3a7.3 7.3 0 0 0 1.6-.9l2.3.8 1.9-3.3-1.9-1.5Z'],
    ];
}

function admin_header(string $title, string $active = ''): void {
    start_session();
    if (!is_logged_in()) {
        redirect('login.php');
    }
    $flashes = take_flashes();
    $base    = app_relative_base();
    $items   = admin_nav_items();
    $user    = $_SESSION['admin_username'] ?? 'admin';

    $unread = 0;
    try {
        $unread = (int)db()->query('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0')->fetch()['c'];
    } catch (Throwable $e) {
        $unread = 0;
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title . ' | Admin — ' . site_name()); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo esc($base); ?>assets/css/style.css">
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
<body class="font-body bg-cream text-charcoal min-h-screen">

<!-- ===================== MOBILE TOP BAR ===================== -->
<header class="md:hidden sticky top-0 z-40 bg-primary text-white shadow-md">
  <div class="flex items-center justify-between px-4 h-14">
    <a href="dashboard.php" class="font-heading font-bold flex items-center gap-2">
      <span aria-hidden="true">🐔</span><?php echo esc(site_name()); ?>
    </a>
    <button id="adminMenuBtn" class="p-2 -mr-2" aria-label="Toggle admin menu" aria-expanded="false" aria-controls="adminSidebar">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
  </div>
</header>

<div class="flex min-h-screen">
  <!-- ===================== SIDEBAR ===================== -->
  <div id="adminSidebar" class="admin-drawer fixed inset-y-0 left-0 z-50 w-64 bg-primary text-white transform -translate-x-full md:translate-x-0 md:static md:z-auto md:shrink-0 md:flex md:flex-col transition-transform duration-200">
    <div class="p-5 border-b border-white/10">
      <h2 class="font-heading font-bold text-xl flex items-center gap-2">
        <span aria-hidden="true">🐔</span> Admin Panel
      </h2>
      <p class="text-xs text-white/60 mt-1">Signed in as <span class="text-gold font-semibold"><?php echo esc($user); ?></span></p>
    </div>

    <nav class="p-3 space-y-1 flex-1 overflow-y-auto" aria-label="Admin navigation">
      <?php foreach ($items as $href => [$label, $icon]): ?>
        <?php $isActive = ($active === $href); ?>
        <a href="<?php echo esc($href); ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition <?php echo $isActive ? 'bg-white/15 font-semibold text-white shadow-inner' : 'text-white/80 hover:bg-white/10 hover:text-white'; ?>">
          <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="<?php echo esc($icon); ?>"/></svg>
          <span class="flex-1"><?php echo esc($label); ?></span>
          <?php if ($href === 'messages.php' && $unread > 0): ?>
            <span class="px-2 py-0.5 rounded-full bg-gold text-charcoal text-xs font-bold"><?php echo $unread; ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-white/10 space-y-1">
      <a href="<?php echo esc($base); ?>index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/80 hover:bg-white/10 hover:text-white transition">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14 12-4-4v8l4-4Z"/></svg>
        View Public Site
      </a>
      <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/80 hover:bg-red-500/40 hover:text-white transition">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/></svg>
        Logout
      </a>
    </div>
  </div>

  <!-- backdrop for the mobile drawer -->
  <div id="adminBackdrop" class="fixed inset-0 z-40 bg-charcoal/50 hidden md:hidden"></div>

  <!-- ===================== CONTENT ===================== -->
  <main class="flex-1 min-w-0">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
      <?php foreach ($flashes as $flash): ?>
        <div class="mb-4 px-4 py-3 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
          <?php echo esc($flash['message']); ?>
        </div>
      <?php endforeach; ?>
    <?php
}

function admin_footer(): void {
    $base = app_relative_base();
    ?>
    </div>
  </main>
</div>

<script>
  (function () {
    var btn = document.getElementById('adminMenuBtn');
    var drawer = document.getElementById('adminSidebar');
    var backdrop = document.getElementById('adminBackdrop');
    if (!btn || !drawer) return;

    function open() {
      drawer.classList.remove('-translate-x-full');
      if (backdrop) backdrop.classList.remove('hidden');
      btn.setAttribute('aria-expanded', 'true');
    }
    function close() {
      drawer.classList.add('-translate-x-full');
      if (backdrop) backdrop.classList.add('hidden');
      btn.setAttribute('aria-expanded', 'false');
    }
    btn.addEventListener('click', function () {
      drawer.classList.contains('-translate-x-full') ? open() : close();
    });
    if (backdrop) backdrop.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 768) {
        drawer.classList.remove('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
      } else {
        close();
      }
    });
  })();
</script>
<script src="<?php echo esc($base); ?>assets/js/main.js" defer></script>
</body>
</html>
    <?php
}
