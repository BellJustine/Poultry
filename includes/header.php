<?php
// includes/header.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

start_session();

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$pageTitle   = $pageTitle ?? '';
$siteName    = site_name();
$base        = app_base();

$navItems = [
    'index.php'      => 'Home',
    'chickens.php'   => 'Chicken Catalog',
    'categories.php' => 'Age Categories',
    'about.php'      => 'About Us',
    'contact.php'    => 'Contact Us',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($pageTitle !== '' ? $pageTitle . ' | ' . $siteName : $siteName); ?></title>
<meta name="description" content="<?php echo esc(site_tagline()); ?>">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo esc($base); ?>assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐔</text></svg>">
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
<body class="font-body bg-cream text-charcoal">
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:top-2 focus:left-2 focus:bg-white focus:px-4 focus:py-2 focus:rounded focus:shadow">Skip to content</a>

<nav class="sticky top-0 z-50 bg-white shadow-md" aria-label="Main navigation">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      <a href="<?php echo esc($base); ?>index.php" class="flex items-center gap-2 shrink-0">
        <span class="text-2xl" aria-hidden="true">🐔</span>
        <span class="font-heading font-bold text-primary text-lg sm:text-xl"><?php echo esc($siteName); ?></span>
      </a>

      <div class="hidden md:flex items-center gap-6">
        <?php foreach ($navItems as $href => $label): ?>
          <a href="<?php echo esc($base . $href); ?>"
             class="hover:text-primary transition <?php echo ($currentPage === $href) ? 'text-primary font-semibold' : ''; ?>"><?php echo esc($label); ?></a>
        <?php endforeach; ?>
        <a href="<?php echo esc($base); ?>admin/login.php" class="text-sm text-charcoal/70 hover:text-primary transition">Admin</a>
        <a href="<?php echo esc($base); ?>chickens.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition">Browse Chickens</a>
      </div>

      <button id="menuBtn" class="md:hidden p-2 -mr-2" aria-label="Toggle menu" aria-expanded="false" aria-controls="mobileMenu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </div>

  <div id="mobileMenu" class="hidden md:hidden px-4 pb-4 space-y-2 bg-white shadow-md">
    <?php foreach ($navItems as $href => $label): ?>
      <a href="<?php echo esc($base . $href); ?>" class="block py-2 border-b border-cream <?php echo ($currentPage === $href) ? 'text-primary font-semibold' : ''; ?>"><?php echo esc($label); ?></a>
    <?php endforeach; ?>
    <a href="<?php echo esc($base); ?>admin/login.php" class="block py-2 border-b border-cream text-charcoal/70">Admin Dashboard</a>
    <a href="<?php echo esc($base); ?>chickens.php" class="block bg-primary text-white px-4 py-2 rounded-lg text-center mt-2">Browse Chickens</a>
  </div>
</nav>

<main id="main-content">
