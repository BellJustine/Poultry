<?php
// chicken-details.php — Product details
$pageTitle = 'Chicken Details';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT c.*, cat.name AS cat_name
       FROM chickens c
       LEFT JOIN categories cat ON c.category_id = cat.id
      WHERE c.id = ?'
);
$stmt->execute([$id]);
$ch = $chicken = $stmt->fetch();

if (!$ch) {
    ?>
    <div class="max-w-3xl mx-auto px-4 py-16 text-center">
      <p class="text-5xl mb-4" aria-hidden="true">🐔</p>
      <h1 class="font-heading text-2xl font-bold text-primary mb-3">Chicken not found</h1>
      <p class="text-gray-600 mb-6">This listing may have been removed from the catalog.</p>
      <a href="<?php echo esc(app_base()); ?>chickens.php" class="inline-block bg-primary text-white px-5 py-2 rounded-lg hover:bg-secondary transition">Back to Catalog</a>
    </div>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$qty = (int)$ch['quantity'];

$relatedStmt = db()->prepare(
    'SELECT * FROM chickens
      WHERE id <> ?
        AND (age_months BETWEEN ? AND ? OR breed = ?)
      ORDER BY (breed = ?) DESC, name ASC
      LIMIT 4'
);
[$minAge, $maxAge] = category_age_range((int)$ch['category_id']);
$relatedStmt->execute([$id, $minAge, $maxAge, $ch['breed'], $ch['breed']]);
$related = $relatedStmt->fetchAll();

$specs = [
    'Breed'             => $ch['breed'],
    'Age'               => (int)$ch['age_months'] . ' months',
    'Category'          => $ch['cat_name'] ?: category_name((int)$ch['category_id']),
    'Gender'            => $ch['gender'],
    'Color'             => $ch['color'],
    'Approximate Weight'=> $ch['weight'],
    'Stock on Hand'     => $qty . ' head' . ($qty === 1 ? '' : 's'),
    'Listed On'         => date('F j, Y', strtotime((string)$ch['created_at'])),
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
  <!-- Breadcrumb -->
  <nav class="text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
    <a href="<?php echo esc(app_base()); ?>index.php" class="hover:text-primary">Home</a>
    <span class="mx-2">/</span>
    <a href="<?php echo esc(app_base()); ?>chickens.php" class="hover:text-primary">Chicken Catalog</a>
    <span class="mx-2">/</span>
    <span class="text-charcoal font-medium"><?php echo esc($ch['name']); ?></span>
  </nav>

  <div class="grid lg:grid-cols-2 gap-10">
    <!-- Picture -->
    <div>
      <div class="img-zoom rounded-2xl overflow-hidden shadow-xl bg-white">
        <img src="<?php echo esc(image_url($ch['image_path'])); ?>" alt="<?php echo esc($ch['name']); ?>"
             class="w-full h-96 object-cover object-center" width="1000" height="700">
      </div>
      <div class="mt-4 flex items-center gap-3 text-sm">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full font-medium <?php echo esc(availability_class($qty)); ?>">
          <span class="w-2 h-2 rounded-full <?php echo esc(availability_dot($qty)); ?>" aria-hidden="true"></span>
          <?php echo esc(availability_label($qty)); ?>
        </span>
        <?php if (!empty($ch['is_featured'])): ?>
          <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gold/20 text-gold font-medium">★ Featured</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Information -->
    <div>
      <h1 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-2"><?php echo esc($ch['name']); ?></h1>
      <p class="text-gray-600 mb-5">
        <?php echo esc($ch['breed']); ?> &bull; <?php echo (int)$ch['age_months']; ?> months old
      </p>

      <p class="text-3xl font-bold text-primary mb-2"><?php echo esc(format_price($ch['price'])); ?></p>
      <p class="text-sm text-gray-500 mb-6">
        Per head &bull;
        <?php echo $qty > 0 ? 'Ready for pickup or delivery' : 'Currently unavailable — contact the farm for restock dates'; ?>
      </p>

      <!-- Specifications -->
      <h2 class="font-heading text-lg font-semibold text-charcoal mb-3">Product Specifications</h2>
      <dl class="bg-white rounded-2xl shadow divide-y divide-cream mb-6">
        <?php foreach ($specs as $label => $value):
            if ($value === null || $value === '') continue; ?>
          <div class="px-5 py-3 flex flex-wrap justify-between gap-2">
            <dt class="text-sm text-gray-500"><?php echo esc($label); ?></dt>
            <dd class="text-sm font-semibold text-charcoal text-right"><?php echo esc($value); ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>

      <!-- Description -->
      <h2 class="font-heading text-lg font-semibold text-charcoal mb-2">Description</h2>
      <p class="text-gray-700 leading-relaxed mb-6"><?php echo nl2br(esc($ch['description'])); ?></p>

      <!-- Care information -->
      <?php
      $care = array_filter([
          'Health Notes'          => $ch['health_notes'],
          'Feeding Information'   => $ch['feeding_information'],
          'Special Characteristics'=> $ch['special_characteristics'],
      ]);
      if ($care): ?>
        <h2 class="font-heading text-lg font-semibold text-charcoal mb-3">Care &amp; Characteristics</h2>
        <div class="space-y-3 mb-8">
          <?php foreach ($care as $label => $text): ?>
            <div class="bg-cream rounded-xl p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-secondary mb-1"><?php echo esc($label); ?></p>
              <p class="text-sm text-gray-700"><?php echo nl2br(esc($text)); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="flex flex-wrap gap-3">
        <a href="<?php echo esc(app_base()); ?>contact.php" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition">Contact the Farm</a>
        <a href="<?php echo esc(app_base()); ?>chickens.php?category=<?php echo (int)$ch['category_id']; ?>" class="border border-primary text-primary px-6 py-3 rounded-lg hover:bg-primary hover:text-white transition">More <?php echo esc($ch['cat_name'] ?: 'Chickens'); ?></a>
      </div>
    </div>
  </div>

  <!-- Related chickens -->
  <?php if (!empty($related)): ?>
    <section class="mt-16">
      <h2 class="font-heading text-2xl font-bold text-primary mb-6">Related Chickens</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($related as $r): ?>
          <div class="card-hover bg-white rounded-2xl overflow-hidden shadow flex flex-col">
            <a href="<?php echo esc(app_base()); ?>chicken-details.php?id=<?php echo (int)$r['id']; ?>" class="img-zoom block">
              <img src="<?php echo esc(image_url($r['image_path'])); ?>" alt="<?php echo esc($r['name']); ?>"
                   class="w-full h-40 object-cover object-center" loading="lazy" width="600" height="400">
            </a>
            <div class="p-4 flex flex-col flex-1">
              <h3 class="font-heading font-semibold"><?php echo esc($r['name']); ?></h3>
              <p class="text-sm text-gray-600"><?php echo esc($r['breed']); ?> &bull; <?php echo (int)$r['age_months']; ?> months</p>
              <p class="text-primary font-bold mt-2"><?php echo esc(format_price($r['price'])); ?></p>
              <span class="inline-block mt-2 self-start px-2 py-1 rounded text-xs font-medium <?php echo esc(availability_class((int)$r['quantity'])); ?>">
                <?php echo esc(availability_label((int)$r['quantity'])); ?>
              </span>
              <a href="<?php echo esc(app_base()); ?>chicken-details.php?id=<?php echo (int)$r['id']; ?>"
                 class="block mt-3 text-center bg-primary text-white py-2 rounded-lg hover:bg-secondary transition">View Details</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
