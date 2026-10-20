<?php
// admin/edit-chicken.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM chickens WHERE id = ?');
$stmt->execute([$id]);
$ch = $stmt->fetch();

if (!$ch) {
    flash('error', 'That chicken could not be found.');
    redirect('chickens.php');
}

$errors = [];
$old = [
    'name' => (string)$ch['name'],
    'breed' => (string)$ch['breed'],
    'age_months' => (string)$ch['age_months'],
    'price' => (string)$ch['price'],
    'quantity' => (string)$ch['quantity'],
    'description' => (string)$ch['description'],
    'gender' => (string)$ch['gender'],
    'color' => (string)$ch['color'],
    'weight' => (string)$ch['weight'],
    'health_notes' => (string)$ch['health_notes'],
    'feeding_information' => (string)$ch['feeding_information'],
    'special_characteristics' => (string)$ch['special_characteristics'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } else {
        foreach (array_keys($old) as $field) {
            $old[$field] = trim((string)($_POST[$field] ?? ''));
        }

        $age   = (int)$old['age_months'];
        $qty   = (int)$old['quantity'];
        $price = (float)str_replace(',', '', $old['price']);

        if ($old['name'] === '')         $errors[] = 'Chicken name is required.';
        if ($old['breed'] === '')       $errors[] = 'Breed is required.';
        if ($age < MIN_AGE_MONTHS)      $errors[] = 'Age must be at least ' . MIN_AGE_MONTHS . ' months.';
        if ($price <= 0)                $errors[] = 'Price must be greater than zero.';
        if ($qty < 0)                   $errors[] = 'Quantity cannot be negative.';
        if ($old['description'] === '') $errors[] = 'Description is required.';

        $imagePath = (string)$ch['image_path'];
        $newUpload = null;

        if (!empty($_FILES['image']['name'])) {
            $uploadError = null;
            $newUpload = upload_image($_FILES['image'], $uploadError);
            if ($newUpload === null) {
                $errors[] = $uploadError ?: 'The image could not be uploaded.';
            } else {
                $imagePath = $newUpload;
            }
        }

        if (isset($_POST['remove_image']) && $newUpload === null) {
            $imagePath = '';
        }

        if (empty($errors)) {
            try {
                $stmt = db()->prepare(
                    'UPDATE chickens SET
                        name = ?, breed = ?, age_months = ?, category_id = ?, price = ?, description = ?,
                        quantity = ?, image_path = ?, gender = ?, color = ?, weight = ?, health_notes = ?,
                        feeding_information = ?, special_characteristics = ?, is_featured = ?
                      WHERE id = ?'
                );
                $stmt->execute([
                    $old['name'],
                    $old['breed'],
                    $age,
                    category_for_age($age),
                    $price,
                    $old['description'],
                    $qty,
                    $imagePath !== '' ? $imagePath : null,
                    $old['gender'] !== '' ? $old['gender'] : null,
                    $old['color'] !== '' ? $old['color'] : null,
                    $old['weight'] !== '' ? $old['weight'] : null,
                    $old['health_notes'] !== '' ? $old['health_notes'] : null,
                    $old['feeding_information'] !== '' ? $old['feeding_information'] : null,
                    $old['special_characteristics'] !== '' ? $old['special_characteristics'] : null,
                    isset($_POST['is_featured']) ? 1 : 0,
                    $id,
                ]);

                if ($newUpload !== null && (string)$ch['image_path'] !== $imagePath) {
                    delete_uploaded_image($ch['image_path']);
                }
                if (isset($_POST['remove_image'])) {
                    delete_uploaded_image($ch['image_path']);
                }

                flash('success', '“' . $old['name'] . '” was updated. The changes are live on the public website.');
                redirect('edit-chicken.php?id=' . $id);
            } catch (Throwable $e) {
                if ($newUpload !== null) {
                    delete_uploaded_image($newUpload);
                }
                $errors[] = 'The chicken could not be updated: ' . $e->getMessage();
            }
        }
    }
}

$stmt = db()->prepare('SELECT * FROM chickens WHERE id = ?');
$stmt->execute([$id]);
$ch = $stmt->fetch();

admin_header('Edit Chicken', 'chickens.php');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Edit Chicken</h1>
    <p class="text-sm text-gray-500 mt-1">Editing “<?php echo esc($ch['name']); ?>” — changes save straight to MySQL.</p>
  </div>
  <div class="flex gap-3 text-sm">
    <a href="../chicken-details.php?id=<?php echo (int)$ch['id']; ?>" target="_blank" rel="noopener" class="text-blue-700 hover:underline">View public page</a>
    <a href="chickens.php" class="text-gray-600 hover:text-primary">&larr; Back to listings</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="mb-5 px-4 py-3 rounded-lg bg-red-100 text-red-800 border border-red-200">
    <p class="font-semibold mb-1">Please fix the following:</p>
    <ul class="list-disc list-inside space-y-1 text-sm">
      <?php foreach ($errors as $error): ?><li><?php echo esc($error); ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="edit-chicken.php?id=<?php echo (int)$ch['id']; ?>"
      class="bg-white rounded-2xl shadow p-6 grid md:grid-cols-2 gap-5">
  <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">

  <div>
    <label for="name" class="block text-sm font-medium mb-1">Chicken Name *</label>
    <input id="name" name="name" value="<?php echo esc($old['name']); ?>" required maxlength="100"
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
  </div>

  <div>
    <label for="breed" class="block text-sm font-medium mb-1">Breed *</label>
    <input id="breed" name="breed" value="<?php echo esc($old['breed']); ?>" required maxlength="100" list="breedOptions"
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <datalist id="breedOptions">
      <?php foreach (db()->query('SELECT DISTINCT breed FROM chickens ORDER BY breed')->fetchAll(PDO::FETCH_COLUMN) as $b): ?>
        <option value="<?php echo esc($b); ?>"></option>
      <?php endforeach; ?>
    </datalist>
  </div>

  <div>
    <label for="age_months" class="block text-sm font-medium mb-1">Age in Months *</label>
    <input id="age_months" name="age_months" type="number" min="<?php echo MIN_AGE_MONTHS; ?>" max="120"
           value="<?php echo esc($old['age_months']); ?>" required
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <p id="agePreview" class="text-xs text-secondary mt-1">Auto-categorised by age.</p>
  </div>

  <div>
    <label for="price" class="block text-sm font-medium mb-1">Price (₱) *</label>
    <input id="price" name="price" type="number" step="0.01" min="0" value="<?php echo esc($old['price']); ?>" required
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
  </div>

  <div>
    <label for="quantity" class="block text-sm font-medium mb-1">Stock Quantity *</label>
    <input id="quantity" name="quantity" type="number" min="0" value="<?php echo esc($old['quantity']); ?>" required
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <p class="text-xs text-gray-500 mt-1">Availability updates automatically from this number.</p>
  </div>

  <div>
    <label for="image" class="block text-sm font-medium mb-1">Replace Image</label>
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview="imagePreview"
           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <?php if (!empty($ch['image_path'])): ?>
      <div class="mt-3 flex items-start gap-3">
        <img src="<?php echo esc(image_url($ch['image_path'])); ?>" alt="Current image" class="w-40 h-32 object-cover rounded-lg border border-cream">
        <label class="flex items-center gap-2 text-sm text-gray-600">
          <input type="checkbox" name="remove_image" value="1" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-secondary">
          Remove current image
        </label>
      </div>
    <?php endif; ?>
    <img id="imagePreview" src="" alt="" class="mt-3 hidden w-40 h-32 object-cover rounded-lg border border-cream">
  </div>

  <div class="md:col-span-2">
    <label for="description" class="block text-sm font-medium mb-1">Description *</label>
    <textarea id="description" name="description" rows="3" required maxlength="2000"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary"><?php echo esc($old['description']); ?></textarea>
  </div>

  <div>
    <label for="gender" class="block text-sm font-medium mb-1">Gender</label>
    <select id="gender" name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      <option value="">Not specified</option>
      <option value="Male"   <?php echo ($old['gender'] === 'Male')   ? 'selected' : ''; ?>>Male</option>
      <option value="Female" <?php echo ($old['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
      <option value="Mixed"  <?php echo ($old['gender'] === 'Mixed')  ? 'selected' : ''; ?>>Mixed</option>
    </select>
  </div>

  <div>
    <label for="color" class="block text-sm font-medium mb-1">Color</label>
    <input id="color" name="color" value="<?php echo esc($old['color']); ?>" maxlength="50"
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
  </div>

  <div>
    <label for="weight" class="block text-sm font-medium mb-1">Approximate Weight</label>
    <input id="weight" name="weight" value="<?php echo esc($old['weight']); ?>" maxlength="50"
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
  </div>

  <div>
    <label for="health_notes" class="block text-sm font-medium mb-1">Health Notes</label>
    <textarea id="health_notes" name="health_notes" rows="2" maxlength="1000"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary"><?php echo esc($old['health_notes']); ?></textarea>
  </div>

  <div>
    <label for="feeding_information" class="block text-sm font-medium mb-1">Feeding Information</label>
    <textarea id="feeding_information" name="feeding_information" rows="2" maxlength="1000"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary"><?php echo esc($old['feeding_information']); ?></textarea>
  </div>

  <div class="md:col-span-2">
    <label for="special_characteristics" class="block text-sm font-medium mb-1">Special Characteristics</label>
    <textarea id="special_characteristics" name="special_characteristics" rows="2" maxlength="1000"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary"><?php echo esc($old['special_characteristics']); ?></textarea>
  </div>

  <div class="md:col-span-2 flex flex-wrap items-center gap-6">
    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" name="is_featured" id="is_featured" value="1" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-secondary"
             <?php echo !empty($ch['is_featured']) ? 'checked' : ''; ?>>
      Feature on the homepage
    </label>
    <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition font-medium">Update Chicken</button>
    <a href="chickens.php" class="text-sm text-gray-600 hover:text-primary">Cancel</a>
  </div>
</form>

<script>
  (function () {
    var age = document.getElementById('age_months');
    var out = document.getElementById('agePreview');
    if (!age || !out) return;
    function update() {
      var months = parseInt(age.value, 10);
      if (!months || isNaN(months)) { out.textContent = 'Auto-categorised by age.'; return; }
      var label = months <= 4 ? '4-Month-Old Chickens' : (months === 5 ? '5-Month-Old Chickens' : '6-Month-Old Chickens and Older');
      out.textContent = 'Will be filed under: ' + label;
    }
    age.addEventListener('input', update);
    update();
  })();
</script>

<?php admin_footer(); ?>
