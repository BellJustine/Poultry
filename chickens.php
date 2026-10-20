<?php
// chickens.php — Public catalog with search, filtering and sorting
$pageTitle = 'Chicken Catalog';
require_once 'includes/header.php';
require_once 'includes/functions.php';

/* ---------------- Filters ---------------- */
$q     = trim((string)($_GET['q'] ?? ''));
$cat   = (int)($_GET['category'] ?? 0);
$breed = trim((string)($_GET['breed'] ?? ''));
$avail = (string)($_GET['avail'] ?? '');
$sort  = (string)($_GET['sort'] ?? 'name_asc');

$allowedSorts = ['name_asc', 'name_desc', 'price_asc', 'price_desc', 'age_asc', 'age_desc', 'newest'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'name_asc';
}

/* ---------------- Query ---------------- */
$sql = 'SELECT c.*, cat.name AS cat_name
          FROM chickens c
          LEFT JOIN categories cat ON c.category_id = cat.id
         WHERE 1 = 1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (c.name LIKE ? OR c.breed LIKE ? OR c.description LIKE ? OR c.gender LIKE ? OR c.color LIKE ?)';
    $like = "%$q%";
    array_push($params, $like, $like, $like, $like, $like);
}

/* Age filtering works on the real age_months column of the database. */
if ($cat > 0) {
    [$minAge, $maxAge] = category_age_range($cat);
    $sql .= ' AND c.age_months BETWEEN ? AND ?';
    $params[] = $minAge;
    $params[] = $maxAge;
}

if ($breed !== '') {
    $sql .= ' AND c.breed = ?';
    $params[] = $breed;
}

if ($avail === 'available') {
    $sql .= ' AND c.quantity > 0';
} elseif ($avail === 'out') {
    $sql .= ' AND c.quantity = 0';
}

switch ($sort) {
    case 'price_asc':  $sql .= ' ORDER BY c.price ASC, c.name ASC'; break;
    case 'price_desc': $sql .= ' ORDER BY c.price DESC, c.name ASC'; break;
    case 'age_asc':    $sql .= ' ORDER BY c.age_months ASC, c.price ASC'; break;
    case 'age_desc':   $sql .= ' ORDER BY c.age_months DESC, c.price ASC'; break;
    case 'newest':     $sql .= ' ORDER BY c.created_at DESC, c.id DESC'; break;
    case 'name_desc':  $sql .= ' ORDER BY c.name DESC'; break;
    default:           $sql .= ' ORDER BY c.name ASC';
}

$stmt = db()->prepare($sql);
$stmt->execute($params);
$chickens = $stmt->fetchAll();

/* ---------------- Filter option lists ---------------- */
$breeds = db()->query('SELECT DISTINCT breed FROM chickens WHERE breed <> "" ORDER BY breed')->fetchAll(PDO::FETCH_COLUMN);
$cats   = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();

$totalListings = (int)db()->query('SELECT COUNT(*) c FROM chickens')->fetch()['c'];
$hasFilters    = ($q !== '' || $cat > 0 || $breed !== '' || $avail !== '');
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

  <!-- Page heading -->
  <div class="mb-8">
    <h1 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-2">Chicken Catalog</h1>
    <p class="text-gray-600">
      Browse our live farm inventory. Every chicken below is pulled straight from the database, and
      availability updates automatically with our stock records.
    </p>
  </div>

  <!-- Filter panel -->
  <form method="get" action="<?php echo esc(app_base()); ?>chickens.php"
        class="bg-white rounded-2xl shadow p-5 mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
    <div class="lg:col-span-2">
      <label for="q" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Search</label>
      <input id="q" type="search" name="q" value="<?php echo esc($q); ?>" placeholder="Name, breed, colour…"
             class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    </div>

    <div>
      <label for="category" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Age Category</label>
      <select id="category" name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
        <option value="0">All Ages</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ($cat === (int)$c['id']) ? 'selected' : ''; ?>>
            <?php echo esc($c['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="breed" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Breed</label>
      <select id="breed" name="breed" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
        <option value="">All Breeds</option>
        <?php foreach ($breeds as $b): ?>
          <option value="<?php echo esc($b); ?>" <?php echo ($breed === $b) ? 'selected' : ''; ?>><?php echo esc($b); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="avail" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Availability</label>
      <select id="avail" name="avail" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
        <option value="">All</option>
        <option value="available" <?php echo ($avail === 'available') ? 'selected' : ''; ?>>Available</option>
        <option value="out" <?php echo ($avail === 'out') ? 'selected' : ''; ?>>Out of Stock</option>
      </select>
    </div>

    <div>
      <label for="sort" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Sort By</label>
      <select id="sort" name="sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
        <option value="name_asc"  <?php echo ($sort === 'name_asc')  ? 'selected' : ''; ?>>Name (A–Z)</option>
        <option value="name_desc" <?php echo ($sort === 'name_desc') ? 'selected' : ''; ?>>Name (Z–A)</option>
        <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Price (Low → High)</option>
        <option value="price_desc"<?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Price (High → Low)</option>
        <option value="age_asc"   <?php echo ($sort === 'age_asc')   ? 'selected' : ''; ?>>Age (Youngest)</option>
        <option value="age_desc"  <?php echo ($sort === 'age_desc')  ? 'selected' : ''; ?>>Age (Oldest)</option>
        <option value="newest"    <?php echo ($sort === 'newest')    ? 'selected' : ''; ?>>Newest First</option>
      </select>
    </div>

    <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap items-center gap-3 pt-1">
      <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition font-medium">Apply Filters</button>
      <?php if ($hasFilters): ?>
        <a href="<?php echo esc(app_base()); ?>chickens.php" class="text-sm text-gray-600 hover:text-primary hover:underline">Clear all filters</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Result summary -->
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <p class="text-sm text-gray-600">
      Showing <strong class="text-primary"><?php echo count($chickens); ?></strong>
      of <?php echo $totalListings; ?> listing<?php echo $totalListings === 1 ? '' : 's'; ?>
      <?php if ($hasFilters): ?><span class="text-gray-500">(filtered)</span><?php endif; ?>
    </p>
    <?php if ($cat > 0): ?>
      <span class="inline-flex items-center gap-2 bg-primary/10 text-primary text-sm font-medium px-3 py-1 rounded-full">
        <?php echo esc(category_name($cat)); ?>
        <a href="<?php echo esc(app_base()); ?>chickens.php" class="hover:text-secondary" aria-label="Remove category filter">&times;</a>
      </span>
    <?php endif; ?>
  </div>

  <!-- Product grid -->
  <?php if (empty($chickens)): ?>
    <div class="bg-white rounded-2xl shadow text-center py-16 px-6">
      <p class="text-5xl mb-4" aria-hidden="true">🐔</p>
      <h2 class="font-heading text-xl font-semibold text-primary mb-2">No chickens match your filters</h2>
      <p class="text-gray-500 mb-6">Try a different search term, breed or age category.</p>
      <a href="<?php echo esc(app_base()); ?>chickens.php" class="inline-block bg-primary text-white px-5 py-2 rounded-lg hover:bg-secondary transition">Reset Filters</a>
    </div>
  <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php foreach ($chickens as $ch):
          $qty = (int)$ch['quantity']; ?>
        <article class="card-hover bg-white rounded-2xl overflow-hidden shadow flex flex-col">
          <a href="<?php echo esc(app_base()); ?>chicken-details.php?id=<?php echo (int)$ch['id']; ?>" class="img-zoom block">
            <img src="<?php echo esc(image_url($ch['image_path'])); ?>" alt="<?php echo esc($ch['name']); ?>"
                 class="w-full h-48 object-cover object-center" loading="lazy" width="600" height="400">
          </a>
          <div class="p-4 flex flex-col flex-1">
            <h2 class="font-heading font-semibold text-charcoal"><?php echo esc($ch['name']); ?></h2>
            <p class="text-sm text-gray-600"><?php echo esc($ch['breed']); ?> &bull; <?php echo (int)$ch['age_months']; ?> months</p>

            <p class="text-sm text-gray-500 mt-2 flex-1"><?php echo esc(excerpt($ch['description'])); ?></p>

            <div class="mt-3 flex items-center justify-between">
              <p class="text-primary font-bold text-lg"><?php echo esc(format_price($ch['price'])); ?></p>
              <span class="px-2 py-1 rounded text-xs font-medium <?php echo esc(availability_class($qty)); ?>">
                <?php echo esc(availability_label($qty)); ?>
              </span>
            </div>

            <p class="text-xs text-gray-500 mt-1">
              Stock: <?php echo $qty; ?> head<?php echo $qty === 1 ? '' : 's'; ?>
              <?php if (!empty($ch['gender'])): ?> &bull; <?php echo esc($ch['gender']); ?><?php endif; ?>
            </p>

            <a href="<?php echo esc(app_base()); ?>chicken-details.php?id=<?php echo (int)$ch['id']; ?>"
               class="block mt-3 text-center bg-primary text-white py-2 rounded-lg hover:bg-secondary transition">
              View Details
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
