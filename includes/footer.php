<?php
// includes/footer.php
$base     = $base ?? app_base();
$siteName = $siteName ?? site_name();
$categories = [];
try {
    $categories = db()->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
} catch (Throwable $e) {
    $categories = [];
}
?>
</main>

<footer class="bg-charcoal text-white mt-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
    <div>
      <h3 class="font-heading font-bold text-xl mb-2 flex items-center gap-2">
        <span aria-hidden="true">🐔</span><?php echo esc($siteName); ?>
      </h3>
      <p class="text-sm text-gray-300"><?php echo esc(site_tagline()); ?></p>
      <p class="text-sm text-gray-400 mt-3">A poultry product catalog and inventory management system.</p>
    </div>

    <div>
      <h4 class="font-semibold mb-3">Quick Links</h4>
      <ul class="space-y-2 text-sm text-gray-300">
        <li><a href="<?php echo esc($base); ?>index.php" class="hover:text-gold">Home</a></li>
        <li><a href="<?php echo esc($base); ?>chickens.php" class="hover:text-gold">Chicken Catalog</a></li>
        <li><a href="<?php echo esc($base); ?>categories.php" class="hover:text-gold">Age Categories</a></li>
        <li><a href="<?php echo esc($base); ?>about.php" class="hover:text-gold">About Us</a></li>
        <li><a href="<?php echo esc($base); ?>contact.php" class="hover:text-gold">Contact Us</a></li>
        <li><a href="<?php echo esc($base); ?>admin/login.php" class="hover:text-gold">Admin Dashboard</a></li>
      </ul>
    </div>

    <div>
      <h4 class="font-semibold mb-3">Age Categories</h4>
      <ul class="space-y-2 text-sm text-gray-300">
        <?php foreach ($categories as $cat): ?>
          <li>
            <a href="<?php echo esc($base); ?>chickens.php?category=<?php echo (int)$cat['id']; ?>" class="hover:text-gold">
              <?php echo esc($cat['name']); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div>
      <h4 class="font-semibold mb-3">Contact</h4>
      <ul class="space-y-2 text-sm text-gray-300">
        <li>📞 <a href="tel:<?php echo esc(preg_replace('/\s+/', '', setting('contact_number'))); ?>" class="hover:text-gold"><?php echo esc(setting('contact_number')); ?></a></li>
        <li>✉️ <a href="mailto:<?php echo esc(setting('email')); ?>" class="hover:text-gold"><?php echo esc(setting('email')); ?></a></li>
        <li>📍 <?php echo esc(setting('address')); ?></li>
        <li>🕐 <?php echo esc(setting('business_hours')); ?></li>
      </ul>
    </div>
  </div>

  <div class="border-t border-gray-700 text-center py-4 text-sm text-gray-400">
    &copy; <?php echo date('Y'); ?> <?php echo esc($siteName); ?>. All rights reserved.
  </div>
</footer>

<script src="<?php echo esc($base); ?>assets/js/main.js" defer></script>
</body>
</html>
