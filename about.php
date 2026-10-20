<?php
// about.php — About the farm
$pageTitle = 'About Us';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$stats = db()->query(
    'SELECT COUNT(*) AS listings, COUNT(DISTINCT breed) AS breeds,
            COALESCE(SUM(quantity), 0) AS heads
       FROM chickens'
)->fetch();
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="text-center mb-12">
    <h1 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-4">About Our Farm</h1>
    <p class="text-gray-600 max-w-3xl mx-auto">
      <?php echo esc(site_name()); ?> — <?php echo esc(site_tagline()); ?>
    </p>
  </div>

  <img src="<?php echo esc(image_url('chickens/6month-mixed-breeds.jpg')); ?>" alt="Mixed breed chickens at the farm"
       class="w-full h-64 md:h-80 object-cover object-center rounded-2xl shadow-xl mb-10" loading="lazy" width="1200" height="600">

  <div class="grid md:grid-cols-3 gap-6 mb-12 text-center">
    <div class="bg-white rounded-2xl shadow p-6">
      <p class="font-heading text-3xl font-bold text-primary"><?php echo (int)$stats['listings']; ?></p>
      <p class="text-sm text-gray-600 mt-1">Active listings</p>
    </div>
    <div class="bg-white rounded-2xl shadow p-6">
      <p class="font-heading text-3xl font-bold text-primary"><?php echo (int)$stats['breeds']; ?></p>
      <p class="text-sm text-gray-600 mt-1">Chicken breeds</p>
    </div>
    <div class="bg-white rounded-2xl shadow p-6">
      <p class="font-heading text-3xl font-bold text-primary"><?php echo (int)$stats['heads']; ?></p>
      <p class="text-sm text-gray-600 mt-1">Birds in stock</p>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow p-6 sm:p-10">
    <h2 class="font-heading text-2xl font-bold text-primary mb-4">Our Story</h2>
    <p class="text-gray-700 leading-relaxed mb-4">
      <?php echo esc(site_name()); ?> is dedicated to raising healthy, farm-fresh chickens with proper
      care and management. We focus on quality breeds, good nutrition, and responsible livestock practices.
    </p>
    <p class="text-gray-700 leading-relaxed mb-4">
      Our mission is to provide reliable poultry products and build trust with our customers through
      quality and transparency. Every chicken published on this website comes straight from our own
      coop records, so what you read online always matches what is physically in our inventory.
    </p>
    <p class="text-gray-700 leading-relaxed mb-8">
      Our flock is grouped by age so buyers can choose exactly what they need — growers for the
      backyard, point-of-lay pullets for immediate egg production, and mature birds for breeding or
      egg-laying farms.
    </p>

    <h2 class="font-heading text-2xl font-bold text-primary mb-4">How We Raise Our Chickens</h2>
    <ul class="grid sm:grid-cols-2 gap-4 mb-8">
      <?php
      $practices = [
        'Clean, ventilated housing'   => 'Every coop is cleaned daily and stocked with fresh bedding.',
        'Balanced feeding program'     => 'Quality pellets, corn and grains with constant clean water.',
        'Regular health monitoring'   => 'Vaccination schedules and routine checks by the farm team.',
        'Free-range daylight hours'   => 'Birds are let out daily for natural foraging and exercise.',
      ];
      foreach ($practices as $title => $text): ?>
        <li class="bg-cream rounded-xl p-4">
          <p class="font-semibold text-primary mb-1"><?php echo esc($title); ?></p>
          <p class="text-sm text-gray-600"><?php echo esc($text); ?></p>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="grid sm:grid-cols-2 gap-4 bg-cream rounded-xl p-6">
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Visit or call us</p>
        <p class="font-semibold text-charcoal">📍 <?php echo esc(setting('address')); ?></p>
        <p class="font-semibold text-charcoal mt-1">📞 <?php echo esc(setting('contact_number')); ?></p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Business hours</p>
        <p class="font-semibold text-charcoal">🕐 <?php echo esc(setting('business_hours')); ?></p>
        <p class="font-semibold text-charcoal mt-1">✉️ <?php echo esc(setting('email')); ?></p>
      </div>
    </div>

    <div class="mt-8 flex flex-wrap gap-3">
      <a href="<?php echo esc(app_base()); ?>chickens.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition">Browse the Catalog</a>
      <a href="<?php echo esc(app_base()); ?>contact.php" class="border border-primary text-primary px-6 py-3 rounded-lg hover:bg-primary hover:text-white transition">Contact Us</a>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
