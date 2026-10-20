<?php
// admin/add-chicken.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

$errors = [];
$old = [
    'name' => '', 'breed' => '', 'age_months' => '', 'price' => '', 'quantity' => '',
    'description' => '', 'gender' => '', 'color' => '', 'weight' => '',
    'health_notes' => '', 'feeding_information' => '', 'special_characteristics' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please submit the form again.';
    } else {
        foreach (array_keys($old) as $field) {
            $old[$field] = trim((string)($_POST[$field] ?? ''));
        }

        $age  = (int)$old['age_months'];
        $qty  = (int)$old['quantity'];
        $price = (float)str_replace(',', '', $old['price']);

        if ($old['name'] === '')                       $errors[] = 'Chicken name is required.';
        if ($old['breed'] === '')                     $errors[] = 'Breed is required.';
        if ($age < MIN_AGE_MONTHS)                    $errors[] = 'Age must be at least ' . MIN_AGE_MONTHS . ' months.';
        if ($price <= 0)                              $errors[] = 'Price must be greater than zero.';
        if ($qty < 0)                                 $errors[] = 'Quantity cannot be negative.';
        if ($old['description'] === '')               $errors[] = 'Description is required.';

        $imagePath = null;
        if (empty($_FILES['image']['name'])) {
            $errors[] = 'Please choose a product image.';
        } else {
            $uploadError = null;
            $imagePath = upload_image($_FILES['image'], $uploadError);
            if ($imagePath === null) {
                $errors[] = $uploadError ?: 'The image could not be uploaded.';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO chickens
                        (name, breed, age_months, category_id, price, description, quantity, image_path,
                         gender, color, weight, health_notes, feeding_information, special_characteristics, is_featured)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $old['name'],
                    $old['breed'],
                    $age,
                    category_for_age($age),
                    $price,
                    $old['description'],
                    $qty,
                    $imagePath,
                    $old['gender'] !== '' ? $old['gender'] : null,
                    $old['color'] !== '' ? $old['color'] : null,
                    $old['weight'] !== '' ? $old['weight'] : null,
                    $old['health_notes'] !== '' ? $old['health_notes'] : null,
                    $old['feeding_information'] !== '' ? $old['feeding_information'] : null,
                    $old['special_characteristics'] !== '' ? $old['special_characteristics'] : null,
                    isset($_POST['is_featured']) ? 1 : 0,
                ]);
                $newId = (int)db()->lastInsertId();
                flash('success', '“' . $old['name'] . '” was added and is now live in the public catalog.');
                redirect('edit-chicken.php?id=' . $newId);
            } catch (Throwable $e) {
                if ($imagePath !== null) {
                    delete_uploaded_image($imagePath);
                }
                $errors[] = 'The chicken could not be saved: ' . $e->getMessage();
            }
        }
    }
}

admin_header('Add Chicken', 'add-chicken.php');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Add New Chicken</h1>
    <p class="text-sm text-gray-500 mt-1">The age category is assigned automatically from the age you enter.</p>
  </div>
  <a href="chickens.php" class="text-sm text-gray-600 hover:text-primary">&larr; Back to listings</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="mb-5 px-4 py-3 rounded-lg bg-red-100 text-red-800 border border-red-200">
    <p class="font-semibold mb-1">Please fix the following:</p>
    <ul class="list-disc list-inside space-y-1 text-sm">
      <?php foreach ($errors as $error): ?><li><?php echo esc($error); ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="add-chicken.php" class="bg-white rounded-2xl shadow p-6 grid md:grid-cols-2 gap-5">
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
    <input id="quantity" name="quantity" type="number" min="0" value="<?php echo esc($old['quantity'] !== '' ? $old['quantity'] : '0'); ?>" required
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <p class="text-xs text-gray-500 mt-1">Availability is derived from this number.</p>
  </div>

  <div>
    <label for="image" class="block text-sm font-medium mb-1">Product Image *</label>
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required
           data-preview="imagePreview"
           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
    <p class="text-xs text-gray-500 mt-1">JPG, PNG, WEBP or GIF, up to 5 MB.</p>
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
    <input id="color" name="color" value="<?php echo esc($old['color']); ?>" maxlength="50" placeholder="e.g. Reddish-brown"
           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
  </div>

  <div>
    <label for="weight" class="block text-sm font-medium mb-1">Approximate Weight</label>
    <input id="weight" name="weight" value="<?php echo esc($old['weight']); ?>" maxlength="50" placeholder="e.g. 1.60 kg"
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
      <input type="checkbox" name="is_featured" id="is_featured" value="1" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-secondary">
      Feature on the homepage
    </label>
    <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition font-medium">Save Chicken</button>
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
