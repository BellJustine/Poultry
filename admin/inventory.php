<?php
// admin/inventory.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('inventory.php');
    }
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        $updated = 0;
        $stmt = db()->prepare('UPDATE chickens SET quantity = ? WHERE id = ?');
        foreach ($_POST['qty'] as $id => $q) {
            $chickenId = (int)$id;
            $quantity  = max(0, (int)$q);
            $stmt->execute([$quantity, $chickenId]);
            $updated++;
        }
        flash('success', 'Inventory updated for ' . $updated . ' listing' . ($updated === 1 ? '' : 's') . '. Availability now reflects the new quantities.');
    }
    redirect('inventory.php');
}

$view   = (string)($_GET['view'] ?? 'all');
$search = trim((string)($_GET['q'] ?? ''));

$sql = 'SELECT c.id, c.name, c.breed, c.age_months, c.quantity, c.image_path, cat.name AS cat_name
          FROM chickens c
          LEFT JOIN categories cat ON c.category_id = cat.id
         WHERE 1 = 1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (c.name LIKE ? OR c.breed LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($view === 'available') {
    $sql .= ' AND c.quantity > 0';
} elseif ($view === 'out') {
    $sql .= ' AND c.quantity = 0';
} elseif ($view === 'low') {
    $sql .= ' AND c.quantity > 0 AND c.quantity <= 2';
}
$sql .= ' ORDER BY c.quantity ASC, c.name ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$chickens = $stmt->fetchAll();

$totals = db()->query(
    'SELECT COUNT(*) AS listings,
            COALESCE(SUM(quantity), 0) AS units,
            COALESCE(SUM(quantity = 0), 0) AS out_stock
       FROM chickens'
)->fetch();

admin_header('Inventory', 'inventory.php');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Inventory Management</h1>
    <p class="text-sm text-gray-500 mt-1">
      <?php echo (int)$totals['listings']; ?> listings &bull; <?php echo (int)$totals['units']; ?> heads in stock &bull;
      <?php echo (int)$totals['out_stock']; ?> out of stock
    </p>
  </div>
</div>

<!-- View filter -->
<form method="get" action="inventory.php" class="bg-white rounded-xl shadow p-4 mb-6 flex flex-wrap gap-3 items-center">
  <input type="search" name="q" value="<?php echo esc($search); ?>" placeholder="Search name or breed…"
         class="flex-1 min-w-[200px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">

  <div class="flex flex-wrap gap-2">
    <?php
    $views = [
        'all'       => 'All',
        'available' => 'Available',
        'low'       => 'Low stock (1–2)',
        'out'       => 'Out of stock',
    ];
    foreach ($views as $key => $label): ?>
      <a href="inventory.php?view=<?php echo esc($key); ?>&amp;q=<?php echo esc(urlencode($search)); ?>"
         class="px-3 py-1.5 rounded-lg text-sm border <?php echo ($view === $key) ? 'bg-primary text-white border-primary' : 'border-gray-300 text-gray-600 hover:border-primary hover:text-primary'; ?>">
        <?php echo esc($label); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <button class="bg-primary text-white rounded-lg px-4 py-2 text-sm hover:bg-secondary transition">Search</button>
</form>

<form method="post" action="inventory.php" class="bg-white rounded-xl shadow overflow-hidden">
  <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">

  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[760px]">
      <thead class="bg-cream text-left text-xs uppercase tracking-wide text-gray-500">
        <tr>
          <th class="p-3 font-semibold">Chicken</th>
          <th class="p-3 font-semibold">Breed</th>
          <th class="p-3 font-semibold">Age</th>
          <th class="p-3 font-semibold">Category</th>
          <th class="p-3 font-semibold">Current Stock</th>
          <th class="p-3 font-semibold">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($chickens)): ?>
        <tr><td colspan="6" class="p-10 text-center text-gray-500">No listings match this view.</td></tr>
      <?php endif; ?>
      <?php foreach ($chickens as $ch): $qty = (int)$ch['quantity']; ?>
        <tr class="border-t border-cream <?php echo $qty === 0 ? 'bg-red-50/60' : ''; ?> hover:bg-cream/40">
          <td class="p-3">
            <div class="flex items-center gap-3">
              <img src="<?php echo esc(image_url($ch['image_path'])); ?>" alt="" class="w-9 h-9 rounded-lg object-cover shrink-0">
              <a href="edit-chicken.php?id=<?php echo (int)$ch['id']; ?>" class="font-medium text-charcoal hover:text-primary"><?php echo esc($ch['name']); ?></a>
            </div>
          </td>
          <td class="p-3 text-gray-600"><?php echo esc($ch['breed']); ?></td>
          <td class="p-3 text-gray-600"><?php echo (int)$ch['age_months']; ?> mo</td>
          <td class="p-3 text-gray-600"><?php echo esc($ch['cat_name'] ?: '—'); ?></td>
          <td class="p-3">
            <input type="number" name="qty[<?php echo (int)$ch['id']; ?>]" value="<?php echo $qty; ?>" min="0"
                   class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </td>
          <td class="p-3">
            <span class="px-2 py-1 rounded text-xs font-medium <?php echo esc(availability_class($qty)); ?>"><?php echo esc(availability_label($qty)); ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="p-4 border-t border-cream flex flex-wrap items-center gap-4">
    <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition font-medium">Save Inventory</button>
    <p class="text-xs text-gray-500">Availability on the public website updates automatically from these quantities.</p>
  </div>
</form>

<?php admin_footer(); ?>
