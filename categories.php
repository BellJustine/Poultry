<?php
// categories.php — Public age-category overview
$pageTitle = 'Age Categories';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$cats = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();

$stats = db()->query(
    'SELECT COUNT(*) AS total,
            COALESCE(SUM(age_months = 4), 0) AS c4,
            COALESCE(SUM(age_months = 5), 0) AS c5,
            COALESCE(SUM(age_months >= 6), 0) AS c6
       FROM chickens'
)->fetch();

$countFor = static function (int $categoryId) use ($stats): int {
    return match ($categoryId) {
        1       => (int)$stats['c4'],
        2       => (int)$stats['c5'],
        3       => (int)$stats['c6'],
        default => 0,
    };
};

$samples = [];
foreach ($cats as $c) {
    $cid = (int)$c['id'];
    [$minAge, $maxAge] = category_age_range($cid);
    $st = db()->prepare(
        'SELECT * FROM chickens WHERE age_months BETWEEN ? AND ? ORDER BY price ASC LIMIT 3'
    );
    $st->execute([$minAge, $maxAge]);
    $samples[$cid] = $st->fetchAll();
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <div class="text-center max-w-3xl mx-auto mb-12">
    <h1 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-3">Chicken Age Categories</h1>
    <p class="text-gray-600">
      Every chicken in our catalog is automatically filed into the correct age group based on its
      real age in the database — so what you see is exactly what is in the coop today.
    </p>
  </div>

  <div class="space-y-12">
    <?php foreach ($cats as $c):
        $cid   = (int)$c['id'];
        $total = $countFor($cid);
        [$minAge, $maxAge] = category_age_range($cid);
    ?>
      <section class="bg-white rounded-2xl shadow overflow-hidden lg:flex">
        <div class="lg:w-2/5 img-zoom">
          <img src="<?php echo esc(image_url(category_image($cid))); ?>" alt="<?php echo esc($c['name']); ?>"
               class="w-full h-56 lg:h-full object-cover object-center" loading="lazy" width="800" height="600">
        </div>

        <div class="lg:w-3/5 p-6 sm:p-8">
          <div class="flex flex-wrap items-center gap-3 mb-3">
            <h2 class="font-heading text-2xl font-bold text-primary"><?php echo esc($c['name']); ?></h2>
            <span class="px-3 py-1 rounded-full bg-secondary/15 text-secondary text-sm font-semibold">
              <?php echo $total; ?> listing<?php echo $total === 1 ? '' : 's'; ?>
            </span>
          </div>

          <p class="text-gray-600 mb-4"><?php echo esc($c['description']); ?></p>
          <p class="text-sm text-gray-500 mb-6">
            Age range in this category:
            <strong class="text-charcoal"><?php echo $minAge === $maxAge ? $minAge . ' months' : $minAge . ' months and older'; ?></strong>
          </p>

          <?php if (!empty($samples[$cid])): ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
              <?php foreach ($samples[$cid] as $s): ?>
                <a href="<?php echo esc(app_base()); ?>chicken-details.php?id=<?php echo (int)$s['id']; ?>"
                   class="rounded-xl overflow-hidden border border-cream hover:shadow-md transition bg-cream">
                  <img src="<?php echo esc(image_url($s['image_path'])); ?>" alt="<?php echo esc($s['name']); ?>"
                       class="w-full h-28 object-cover object-center" loading="lazy" width="400" height="300">
                  <div class="p-3">
                    <p class="text-sm font-semibold text-charcoal leading-tight"><?php echo esc($s['name']); ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo esc($s['breed']); ?></p>
                    <p class="text-sm font-bold text-primary mt-1"><?php echo esc(format_price($s['price'])); ?></p>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="text-sm text-gray-500 mb-6">No chickens in this category yet.</p>
          <?php endif; ?>

          <a href="<?php echo esc(app_base()); ?>chickens.php?category=<?php echo $cid; ?>"
             class="inline-block bg-primary text-white px-5 py-2 rounded-lg hover:bg-secondary transition">
            View all <?php echo esc($c['name']); ?>
          </a>
        </div>
      </section>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
