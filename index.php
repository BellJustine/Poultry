<?php
// index.php — Homepage
$pageTitle = '';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$featured = db()->query(
    'SELECT c.*, cat.name AS cat_name
       FROM chickens c
       JOIN categories cat ON c.category_id = cat.id
      WHERE c.is_featured = 1
      ORDER BY c.id DESC
      LIMIT 8'
)->fetchAll();

$cats = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();

$counts = db()->query(
    'SELECT COUNT(*) AS total,
            COALESCE(SUM(age_months = 4), 0) AS c4,
            COALESCE(SUM(age_months = 5), 0) AS c5,
            COALESCE(SUM(age_months >= 6), 0) AS c6,
            COUNT(DISTINCT breed) AS breeds
       FROM chickens'
)->fetch();

$countFor = static function (int $categoryId) use ($counts): int {
    return match ($categoryId) {
        1       => (int)$counts['c4'],
        2       => (int)$counts['c5'],
        3       => (int)$counts['c6'],
        default => 0,
    };
};
?>

<!-- ============================ HERO ============================ -->
<section class="relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-br from-primary via-secondary to-primary opacity-95"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center relative">
    <div class="text-white">
      <span class="inline-block bg-gold/20 text-gold border border-gold/40 rounded-full px-4 py-1 text-xs font-semibold tracking-wide uppercase mb-5">
        Farm-Fresh &middot; Locally Raised
      </span>
      <h1 class="font-heading text-4xl md:text-5xl lg:text-6xl font-bold mb-5 leading-tight">
        Healthy Chickens, <span class="text-gold">Raised with Care.</span>
      </h1>
      <p class="text-lg text-white/90 mb-8 max-w-xl">
        <?php echo esc(site_tagline()); ?> Explore our selection of farm-raised chickens —
        carefully nurtured for health, strength and exceptional quality — and find the perfect
        birds for your flock.
      </p>
      <div class="flex flex-wrap gap-4">
        <a href="<?php echo esc(app_base()); ?>chickens.php" class="bg-gold text-charcoal px-6 py-3 rounded-lg font-semibold hover:bg-white transition">
          Explore Chickens
        </a>
        <a href="<?php echo esc(app_base()); ?>about.php" class="border border-white/70 text-white px-6 py-3 rounded-lg hover:bg-white hover:text-primary transition">
          Learn More
        </a>
      </div>
      <dl class="grid grid-cols-3 gap-4 mt-10 max-w-md">
        <div><dt class="text-white/70 text-xs uppercase tracking-wide">Listings</dt><dd class="font-heading text-2xl font-bold text-white"><?php echo (int)$counts['total']; ?></dd></div>
        <div><dt class="text-white/70 text-xs uppercase tracking-wide">Breeds</dt><dd class="font-heading text-2xl font-bold text-white"><?php echo (int)$counts['breeds']; ?></dd></div>
        <div><dt class="text-white/70 text-xs uppercase tracking-wide">Ages</dt><dd class="font-heading text-2xl font-bold text-white">3</dd></div>
      </dl>
    </div>

    <div class="img-zoom rounded-2xl overflow-hidden shadow-2xl ring-4 ring-white/10">
      <img src="<?php echo esc(image_url('chickens/hero-chickens.jpg')); ?>" alt="Healthy chickens raised at the farm"
           class="w-full h-80 md:h-[26rem] object-cover object-center" width="1200" height="800" fetchpriority="high">
    </div>
  </div>
</section>

<!-- ====================== AGE CATEGORIES ======================= -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10">
      <h2 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-3">Explore Chickens by Age</h2>
      <p class="text-gray-600 max-w-2xl mx-auto">Every bird is sorted automatically into the correct age group, so you always know exactly what you are buying.</p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      <?php foreach ($cats as $c):
          $cid   = (int)$c['id'];
          $total = $countFor($cid);
      ?>
      <div class="card-hover bg-cream rounded-2xl overflow-hidden shadow flex flex-col">
        <div class="img-zoom">
          <img src="<?php echo esc(image_url(category_image($cid))); ?>" alt="<?php echo esc($c['name']); ?>"
               class="w-full h-48 object-cover object-center" loading="lazy" width="600" height="400">
        </div>
        <div class="p-6 flex flex-col flex-1">
          <h3 class="font-heading text-xl font-semibold text-primary mb-2"><?php echo esc($c['name']); ?></h3>
          <p class="text-sm text-gray-600 mb-4 flex-1"><?php echo esc($c['description']); ?></p>
          <p class="text-sm text-gray-500 mb-4">
            <span class="font-semibold text-primary"><?php echo $total; ?></span> listing<?php echo $total === 1 ? '' : 's'; ?> available
          </p>
          <a href="<?php echo esc(app_base()); ?>chickens.php?category=<?php echo $cid; ?>"
             class="inline-block text-center bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition">
            View Chickens
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ======================= FEATURED BIRDS ======================= -->
<section class="py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4 mb-10">
      <div>
        <h2 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-3">Our Featured Chickens</h2>
        <p class="text-gray-600">Hand-picked birds from this season's flock.</p>
      </div>
      <a href="<?php echo esc(app_base()); ?>chickens.php" class="text-primary font-semibold hover:underline">View the full catalog &rarr;</a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php if (empty($featured)): ?>
        <p class="col-span-full text-center text-gray-500 bg-white rounded-2xl py-12 shadow">No featured chickens yet. Add some from the admin dashboard.</p>
      <?php else: ?>
        <?php foreach ($featured as $ch): ?>
        <div class="card-hover bg-white rounded-2xl overflow-hidden shadow flex flex-col">
          <div class="img-zoom">
            <img src="<?php echo esc(image_url($ch['image_path'])); ?>" alt="<?php echo esc($ch['name']); ?>"
                 class="w-full h-48 object-cover object-center" loading="lazy" width="600" height="400">
          </div>
          <div class="p-4 flex flex-col flex-1">
            <h3 class="font-heading font-semibold text-charcoal"><?php echo esc($ch['name']); ?></h3>
            <p class="text-sm text-gray-600"><?php echo esc($ch['breed']); ?> &bull; <?php echo (int)$ch['age_months']; ?> months</p>
            <p class="text-primary font-bold mt-2 text-lg"><?php echo esc(format_price($ch['price'])); ?></p>
            <div class="mt-2">
              <span class="inline-block px-2 py-1 rounded text-xs font-medium <?php echo esc(availability_class((int)$ch['quantity'])); ?>">
                <?php echo esc(availability_label((int)$ch['quantity'])); ?>
              </span>
            </div>
            <a href="<?php echo esc(app_base()); ?>chicken-details.php?id=<?php echo (int)$ch['id']; ?>"
               class="block mt-3 text-center bg-primary text-white py-2 rounded-lg hover:bg-secondary transition">
              View Details
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ========================== WHY US =========================== -->
<section class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="font-heading text-3xl md:text-4xl font-bold text-center text-primary mb-10">Why Choose <?php echo esc(site_name()); ?>?</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php
      $benefits = [
        ['🐣', 'Healthy Farm-Raised Chickens', 'Grown slowly under careful supervision with clean housing and daily care.'],
        ['🧬', 'Quality Poultry Breeds', 'Only well-known, hardy breeds selected for local conditions.'],
        ['📋', 'Properly Managed Livestock', 'Regular health checks, vaccination and honest stock records.'],
        ['🤝', 'Trusted Local Poultry Supplier', 'Straight from our own farm to your backyard flock.'],
      ];
      foreach ($benefits as [$icon, $title, $text]): ?>
      <div class="text-center p-6 bg-cream rounded-2xl">
        <div class="text-4xl mb-3" aria-hidden="true"><?php echo esc($icon); ?></div>
        <h3 class="font-heading font-semibold text-primary"><?php echo esc($title); ?></h3>
        <p class="text-sm text-gray-600 mt-2"><?php echo esc($text); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========================== ABOUT =========================== -->
<section class="py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-10 items-center">
    <img src="<?php echo esc(image_url('chickens/6month-mixed-breeds.jpg')); ?>" alt="Mixed breed chickens from the farm"
         class="rounded-2xl shadow-xl w-full h-80 object-cover object-center" loading="lazy" width="900" height="600">
    <div>
      <h2 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-4">About Our Farm</h2>
      <p class="mb-4 text-gray-700">
        <?php echo esc(site_name()); ?> is dedicated to raising healthy, farm-fresh chickens with proper care
        and management. We focus on quality breeds, good nutrition, and responsible livestock practices.
      </p>
      <p class="text-gray-600 mb-6">
        Our mission is to provide reliable poultry products and build trust with our customers through
        quality and transparency. Every listing on this site is backed by our live inventory records.
      </p>
      <a href="<?php echo esc(app_base()); ?>about.php" class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition">
        More About Us
      </a>
    </div>
  </div>
</section>

<!-- ========================== CONTACT =========================== -->
<section class="py-16 bg-white">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <h2 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-6">Contact Us</h2>
    <div class="grid sm:grid-cols-2 gap-4 text-left">
      <div class="bg-cream rounded-xl p-5">
        <p class="text-sm text-gray-500 uppercase tracking-wide">Phone</p>
        <p class="font-semibold text-charcoal mt-1">📞 <?php echo esc(setting('contact_number')); ?></p>
      </div>
      <div class="bg-cream rounded-xl p-5">
        <p class="text-sm text-gray-500 uppercase tracking-wide">Email</p>
        <p class="font-semibold text-charcoal mt-1">✉️ <?php echo esc(setting('email')); ?></p>
      </div>
      <div class="bg-cream rounded-xl p-5">
        <p class="text-sm text-gray-500 uppercase tracking-wide">Address</p>
        <p class="font-semibold text-charcoal mt-1">📍 <?php echo esc(setting('address')); ?></p>
      </div>
      <div class="bg-cream rounded-xl p-5">
        <p class="text-sm text-gray-500 uppercase tracking-wide">Business Hours</p>
        <p class="font-semibold text-charcoal mt-1">🕐 <?php echo esc(setting('business_hours')); ?></p>
      </div>
    </div>
    <a href="<?php echo esc(app_base()); ?>contact.php" class="inline-block mt-8 bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition">
      Send Us a Message
    </a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
