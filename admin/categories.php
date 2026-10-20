<?php
// admin/categories.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recategorize'])) {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('categories.php');
    }
    try {
        $stmt = db()->prepare(
            'UPDATE chickens
                SET category_id = CASE
                    WHEN age_months <= 4 THEN 1
                    WHEN age_months = 5  THEN 2
                    ELSE 3
                END'
        );
        $stmt->execute();
        flash('success', 'All ' . $stmt->rowCount() . ' listing(s) were re-categorised from their age.');
    } catch (Throwable $e) {
        flash('error', 'The chickens could not be re-categorised: ' . $e->getMessage());
    }
    redirect('categories.php');
}

$cats = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();

$counts = db()->query(
    'SELECT COUNT(*) AS total,
            COALESCE(SUM(age_months = 4), 0) AS c4,
            COALESCE(SUM(age_months = 5), 0) AS c5,
            COALESCE(SUM(age_months >= 6), 0) AS c6
       FROM chickens'
)->fetch();

$mismatched = (int)db()->query(
    'SELECT COUNT(*) c FROM chickens
      WHERE category_id <> CASE WHEN age_months <= 4 THEN 1 WHEN age_months = 5 THEN 2 ELSE 3 END'
)->fetch()['c'];

$byCategory = [];
foreach ($cats as $c) {
    $cid = (int)$c['id'];
    [$minAge, $maxAge] = category_age_range($cid);
    $st = db()->prepare('SELECT id, name, breed, age_months, quantity FROM chickens WHERE age_months BETWEEN ? AND ? ORDER BY name');
    $st->execute([$minAge, $maxAge]);
    $byCategory[$cid] = $st->fetchAll();
}

admin_header('Age Categories', 'categories.php');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Age Categories</h1>
    <p class="text-sm text-gray-500 mt-1">
      The three required categories. Every chicken is filed automatically according to its real age.
    </p>
  </div>
  <form method="post" data-confirm="Re-categorise every chicken based on its age?">
    <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
    <input type="hidden" name="recategorize" value="1">
    <button class="bg-secondary text-white px-4 py-2 rounded-lg hover:bg-primary transition text-sm font-medium">
      Re-categorise all by age
    </button>
  </form>
</div>

<?php if ($mismatched > 0): ?>
  <div class="mb-5 px-4 py-3 rounded-lg bg-amber-100 text-amber-900 border border-amber-200 text-sm">
    <strong><?php echo $mismatched; ?></strong> listing(s) have a category that does not match their age.
    Use “Re-categorise all by age” to repair them.
  </div>
<?php else: ?>
  <div class="mb-5 px-4 py-3 rounded-lg bg-green-100 text-green-800 border border-green-200 text-sm">
    All listings are correctly categorised according to their age.
  </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
  <?php foreach ($cats as $c):
      $cid = (int)$c['id'];
      [$minAge, $maxAge] = category_age_range($cid);
      $total = match ($cid) {
          1       => (int)$counts['c4'],
          2       => (int)$counts['c5'],
          3       => (int)$counts['c6'],
          default => 0,
      };
  ?>
    <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col">
      <img src="<?php echo esc(image_url(category_image($cid))); ?>" alt="<?php echo esc($c['name']); ?>"
           class="w-full h-36 object-cover object-center" loading="lazy" width="600" height="400">
      <div class="p-5 flex flex-col flex-1">
        <h2 class="font-heading font-semibold text-primary"><?php echo esc($c['name']); ?></h2>
        <p class="text-sm text-gray-600 mt-1 flex-1"><?php echo esc($c['description']); ?></p>

        <dl class="grid grid-cols-2 gap-3 my-4 text-sm">
          <div class="bg-cream rounded-lg p-3">
            <dt class="text-xs uppercase tracking-wide text-gray-500">Listings</dt>
            <dd class="font-heading text-xl font-bold text-primary"><?php echo $total; ?></dd>
          </div>
          <div class="bg-cream rounded-lg p-3">
            <dt class="text-xs uppercase tracking-wide text-gray-500">Age range</dt>
            <dd class="font-semibold text-charcoal"><?php echo $minAge === $maxAge ? $minAge . ' months' : $minAge . '+ months'; ?></dd>
          </div>
        </dl>

        <a href="../chickens.php?category=<?php echo $cid; ?>" target="_blank" rel="noopener"
           class="text-sm text-secondary hover:underline mb-3">View on the public site &rarr;</a>

        <details class="border-t border-cream pt-3">
          <summary class="cursor-pointer text-sm font-medium text-charcoal">Chickens in this category</summary>
          <ul class="mt-3 space-y-2">
            <?php if (empty($byCategory[$cid])): ?>
              <li class="text-sm text-gray-500">No chickens in this category yet.</li>
            <?php else: ?>
              <?php foreach ($byCategory[$cid] as $item): ?>
                <li class="flex items-center justify-between gap-2 text-sm">
                  <a href="edit-chicken.php?id=<?php echo (int)$item['id']; ?>" class="text-charcoal hover:text-primary truncate"><?php echo esc($item['name']); ?></a>
                  <span class="text-xs px-2 py-0.5 rounded <?php echo esc(availability_class((int)$item['quantity'])); ?>"><?php echo (int)$item['quantity']; ?></span>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </details>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php admin_footer(); ?>
