<?php
// admin/chickens.php — manage all chicken listings
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

/* ---------------- Actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('chickens.php');
    }

    if (isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        $stmt = db()->prepare('SELECT name, image_path FROM chickens WHERE id = ?');
        $stmt->execute([$deleteId]);
        $row = $stmt->fetch();

        if ($row) {
            $del = db()->prepare('DELETE FROM chickens WHERE id = ?');
            $del->execute([$deleteId]);
            delete_uploaded_image($row['image_path']);
            flash('success', '“' . $row['name'] . '” was deleted from the catalog.');
        } else {
            flash('error', 'That chicken no longer exists.');
        }
        redirect('chickens.php');
    }

    if (isset($_POST['toggle_featured'])) {
        $chickenId = (int)$_POST['toggle_featured'];
        $stmt = db()->prepare('UPDATE chickens SET is_featured = 1 - is_featured WHERE id = ?');
        $stmt->execute([$chickenId]);
        flash('success', 'Featured flag updated.');
        redirect('chickens.php');
    }
}

/* ---------------- Filters ---------------- */
$q     = trim((string)($_GET['q'] ?? ''));
$cat   = (int)($_GET['category'] ?? 0);
$avail = (string)($_GET['avail'] ?? '');

$sql = 'SELECT c.*, cat.name AS cat_name
          FROM chickens c
          LEFT JOIN categories cat ON c.category_id = cat.id
         WHERE 1 = 1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (c.name LIKE ? OR c.breed LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($cat > 0) {
    [$minAge, $maxAge] = category_age_range($cat);
    $sql .= ' AND c.age_months BETWEEN ? AND ?';
    $params[] = $minAge;
    $params[] = $maxAge;
}
if ($avail === 'available') {
    $sql .= ' AND c.quantity > 0';
} elseif ($avail === 'out') {
    $sql .= ' AND c.quantity = 0';
}
$sql .= ' ORDER BY c.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$chickens = $stmt->fetchAll();

$cats = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();

admin_header('Manage Chickens', 'chickens.php');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Manage Chickens</h1>
    <p class="text-sm text-gray-500 mt-1"><?php echo count($chickens); ?> listing<?php echo count($chickens) === 1 ? '' : 's'; ?> found.</p>
  </div>
  <a href="add-chicken.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition text-sm font-medium">+ Add Chicken</a>
</div>

<!-- Filter bar -->
<form method="get" action="chickens.php" class="bg-white rounded-xl shadow p-4 mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
  <input type="search" name="q" value="<?php echo esc($q); ?>" placeholder="Search name or breed…"
         class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">

  <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <option value="0">All age categories</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?php echo (int)$c['id']; ?>" <?php echo ($cat === (int)$c['id']) ? 'selected' : ''; ?>><?php echo esc($c['name']); ?></option>
    <?php endforeach; ?>
  </select>

  <select name="avail" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <option value="">All availability</option>
    <option value="available" <?php echo ($avail === 'available') ? 'selected' : ''; ?>>Available</option>
    <option value="out" <?php echo ($avail === 'out') ? 'selected' : ''; ?>>Out of stock</option>
  </select>

  <button class="bg-primary text-white rounded-lg px-4 py-2 text-sm hover:bg-secondary transition">Apply</button>
</form>

<div class="bg-white rounded-xl shadow overflow-x-auto">
  <table class="w-full text-sm min-w-[900px]">
    <thead class="bg-cream text-left text-xs uppercase tracking-wide text-gray-500">
      <tr>
        <th class="p-3 font-semibold">Image</th>
        <th class="p-3 font-semibold">Name</th>
        <th class="p-3 font-semibold">Breed</th>
        <th class="p-3 font-semibold">Age</th>
        <th class="p-3 font-semibold">Category</th>
        <th class="p-3 font-semibold">Price</th>
        <th class="p-3 font-semibold">Stock</th>
        <th class="p-3 font-semibold">Status</th>
        <th class="p-3 font-semibold">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($chickens)): ?>
      <tr><td colspan="9" class="p-10 text-center text-gray-500">No chickens match your filters.</td></tr>
    <?php endif; ?>
    <?php foreach ($chickens as $ch): $qty = (int)$ch['quantity']; ?>
      <tr class="border-t border-cream hover:bg-cream/40">
        <td class="p-3">
          <img src="<?php echo esc(image_url($ch['image_path'])); ?>" alt="<?php echo esc($ch['name']); ?>" class="w-12 h-12 object-cover rounded-lg">
        </td>
        <td class="p-3">
          <p class="font-medium text-charcoal"><?php echo esc($ch['name']); ?></p>
          <?php if (!empty($ch['is_featured'])): ?>
            <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-gold/25 text-gold">★ FEATURED</span>
          <?php endif; ?>
        </td>
        <td class="p-3 text-gray-600"><?php echo esc($ch['breed']); ?></td>
        <td class="p-3 text-gray-600"><?php echo (int)$ch['age_months']; ?> mo</td>
        <td class="p-3 text-gray-600"><?php echo esc($ch['cat_name'] ?: '—'); ?></td>
        <td class="p-3 font-semibold text-primary"><?php echo esc(format_price($ch['price'])); ?></td>
        <td class="p-3 text-gray-600"><?php echo $qty; ?></td>
        <td class="p-3">
          <span class="px-2 py-1 rounded text-xs font-medium <?php echo esc(availability_class($qty)); ?>"><?php echo esc(availability_label($qty)); ?></span>
        </td>
        <td class="p-3">
          <div class="flex flex-wrap items-center gap-3">
            <a href="../chicken-details.php?id=<?php echo (int)$ch['id']; ?>" target="_blank" rel="noopener" class="text-blue-700 hover:underline">View</a>
            <a href="edit-chicken.php?id=<?php echo (int)$ch['id']; ?>" class="text-primary hover:underline">Edit</a>

            <form method="post" class="inline" data-confirm="Remove &quot;<?php echo esc($ch['name']); ?>&quot; from the catalog? This cannot be undone.">
              <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
              <input type="hidden" name="toggle_featured" value="<?php echo (int)$ch['id']; ?>">
              <button class="text-gold hover:underline" title="Toggle featured">★</button>
            </form>

            <form method="post" class="inline" data-confirm="Delete &quot;<?php echo esc($ch['name']); ?>&quot;? This permanently removes the listing.">
              <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
              <input type="hidden" name="delete_id" value="<?php echo (int)$ch['id']; ?>">
              <button class="text-red-600 hover:underline">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php admin_footer(); ?>
