<?php
// admin/dashboard.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

$stats = db()->query(
    'SELECT COUNT(*) AS listings,
            COALESCE(SUM(quantity), 0) AS units,
            COALESCE(SUM(quantity > 0), 0) AS in_stock,
            COALESCE(SUM(quantity = 0), 0) AS out_stock,
            COUNT(DISTINCT breed) AS breeds
       FROM chickens'
)->fetch();

$totalCategories = (int)db()->query('SELECT COUNT(*) c FROM categories')->fetch()['c'];

$recent = db()->query(
    'SELECT c.*, cat.name AS cat_name
       FROM chickens c
       LEFT JOIN categories cat ON c.category_id = cat.id
      ORDER BY c.updated_at DESC, c.id DESC
      LIMIT 6'
)->fetchAll();

$lowStock = db()->query(
    'SELECT id, name, breed, quantity FROM chickens WHERE quantity <= 2 ORDER BY quantity ASC, name ASC LIMIT 5'
)->fetchAll();

$byCategory = db()->query(
    'SELECT cat.id, cat.name, COUNT(ch.id) AS total, COALESCE(SUM(ch.quantity), 0) AS units
       FROM categories cat
       LEFT JOIN chickens ch ON ch.age_months BETWEEN
            CASE cat.id WHEN 1 THEN 4 WHEN 2 THEN 5 ELSE 6 END
            AND
            CASE cat.id WHEN 1 THEN 4 WHEN 2 THEN 5 ELSE 999 END
      GROUP BY cat.id, cat.name
      ORDER BY cat.id'
)->fetchAll();

admin_header('Dashboard', 'dashboard.php');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Dashboard Overview</h1>
    <p class="text-sm text-gray-500 mt-1">Live statistics taken directly from the MySQL database.</p>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="add-chicken.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition text-sm font-medium">+ Add Chicken</a>
    <a href="inventory.php" class="bg-white border border-primary text-primary px-4 py-2 rounded-lg hover:bg-primary hover:text-white transition text-sm font-medium">Update Stock</a>
  </div>
</div>

<!-- Statistics -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
  <?php
  $cards = [
      ['Total Listings',        (int)$stats['listings'],   'text-primary', 'All chicken records'],
      ['Available Chickens',    (int)$stats['units'],      'text-secondary', 'Total heads in stock'],
      ['In-Stock Listings',     (int)$stats['in_stock'],   'text-green-700', 'Records with stock > 0'],
      ['Out-of-Stock Listings', (int)$stats['out_stock'],  'text-red-700', 'Records with stock = 0'],
      ['Total Categories',      $totalCategories,           'text-gold', 'Age groups'],
      ['Total Breeds',          (int)$stats['breeds'],     'text-charcoal', 'Distinct breeds'],
  ];
  foreach ($cards as [$label, $value, $colour, $hint]): ?>
    <div class="bg-white rounded-xl shadow p-4 sm:p-5">
      <p class="text-xs uppercase tracking-wide text-gray-500"><?php echo esc($label); ?></p>
      <p class="font-heading text-3xl font-bold mt-1 <?php echo esc($colour); ?>"><?php echo $value; ?></p>
      <p class="text-xs text-gray-400 mt-1"><?php echo esc($hint); ?></p>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-6">
  <!-- Recent listings -->
  <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-cream">
      <h2 class="font-heading font-semibold text-primary">Recently Updated Chickens</h2>
      <a href="chickens.php" class="text-sm text-secondary hover:underline">View all</a>
    </div>

    <?php if (empty($recent)): ?>
      <p class="text-center text-gray-500 py-10">No chickens yet. <a href="add-chicken.php" class="text-primary underline">Add the first one</a>.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-cream text-left text-xs uppercase tracking-wide text-gray-500">
            <tr>
              <th class="p-3 font-semibold">Chicken</th>
              <th class="p-3 font-semibold">Breed</th>
              <th class="p-3 font-semibold">Age</th>
              <th class="p-3 font-semibold">Price</th>
              <th class="p-3 font-semibold">Stock</th>
              <th class="p-3 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($recent as $ch): $qty = (int)$ch['quantity']; ?>
            <tr class="border-t border-cream hover:bg-cream/50">
              <td class="p-3">
                <div class="flex items-center gap-3">
                  <img src="<?php echo esc(image_url($ch['image_path'])); ?>" alt="" class="w-10 h-10 rounded-lg object-cover shrink-0">
                  <a href="edit-chicken.php?id=<?php echo (int)$ch['id']; ?>" class="font-medium text-charcoal hover:text-primary"><?php echo esc($ch['name']); ?></a>
                </div>
              </td>
              <td class="p-3 text-gray-600"><?php echo esc($ch['breed']); ?></td>
              <td class="p-3 text-gray-600"><?php echo (int)$ch['age_months']; ?> mo</td>
              <td class="p-3 font-semibold text-primary"><?php echo esc(format_price($ch['price'])); ?></td>
              <td class="p-3 text-gray-600"><?php echo $qty; ?></td>
              <td class="p-3">
                <span class="px-2 py-1 rounded text-xs font-medium <?php echo esc(availability_class($qty)); ?>"><?php echo esc(availability_label($qty)); ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Side column -->
  <div class="space-y-6">
    <!-- Stock by category -->
    <div class="bg-white rounded-xl shadow p-5">
      <h2 class="font-heading font-semibold text-primary mb-4">Stock by Age Category</h2>
      <ul class="space-y-4">
        <?php foreach ($byCategory as $bc): ?>
          <li>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-charcoal"><?php echo esc($bc['name']); ?></span>
              <span class="text-gray-500"><?php echo (int)$bc['total']; ?> listing<?php echo (int)$bc['total'] === 1 ? '' : 's'; ?> &bull; <?php echo (int)$bc['units']; ?> head<?php echo (int)$bc['units'] === 1 ? '' : 's'; ?></span>
            </div>
            <div class="h-2 bg-cream rounded-full overflow-hidden">
              <?php $maxUnits = max(1, (int)max(array_column($byCategory, 'units'))); ?>
              <div class="h-full bg-secondary rounded-full" style="width: <?php echo round(((int)$bc['units'] / $maxUnits) * 100); ?>%"></div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Low stock -->
    <div class="bg-white rounded-xl shadow p-5">
      <h2 class="font-heading font-semibold text-primary mb-4">Low or Out of Stock</h2>
      <?php if (empty($lowStock)): ?>
        <p class="text-sm text-gray-500">All listings are well stocked.</p>
      <?php else: ?>
        <ul class="space-y-3">
          <?php foreach ($lowStock as $ls): ?>
            <li class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-medium text-charcoal"><?php echo esc($ls['name']); ?></p>
                <p class="text-xs text-gray-500"><?php echo esc($ls['breed']); ?></p>
              </div>
              <span class="px-2 py-1 rounded text-xs font-medium <?php echo esc(availability_class((int)$ls['quantity'])); ?>">
                <?php echo (int)$ls['quantity']; ?> left
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
        <a href="inventory.php" class="inline-block mt-4 text-sm text-secondary hover:underline">Manage inventory &rarr;</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php admin_footer(); ?>
