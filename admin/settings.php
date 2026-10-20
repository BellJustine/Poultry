<?php
// admin/settings.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

$errors = [];
$action = (string)($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('settings.php');
    }

    /* ---------------- Business settings ---------------- */
    if ($action === 'settings') {
        $name        = trim((string)($_POST['business_name'] ?? ''));
        $description = trim((string)($_POST['business_description'] ?? ''));
        $phone       = trim((string)($_POST['contact_number'] ?? ''));
        $email       = trim((string)($_POST['email'] ?? ''));
        $address     = trim((string)($_POST['address'] ?? ''));
        $hours       = trim((string)($_POST['business_hours'] ?? ''));

        if ($name === '')    $errors[] = 'Business name is required.';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($phone === '')   $errors[] = 'Contact number is required.';
        if ($address === '') $errors[] = 'Address is required.';

        if (empty($errors)) {
            try {
                set_setting('business_name', $name);
                set_setting('business_description', $description);
                set_setting('contact_number', $phone);
                set_setting('email', $email);
                set_setting('address', $address);
                set_setting('business_hours', $hours);
                flash('success', 'Business settings saved. The public website has been updated.');
            } catch (Throwable $e) {
                $errors[] = 'The settings could not be saved: ' . $e->getMessage();
            }
        }
    }

    /* ---------------- Password change ---------------- */
    if ($action === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if ($current === '')  $errors[] = 'Please enter your current password.';
        if (mb_strlen($new) < 8) $errors[] = 'The new password must be at least 8 characters long.';
        if ($new !== $confirm)  $errors[] = 'The new password and confirmation do not match.';

        if (empty($errors)) {
            $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
            $stmt->execute([$_SESSION['admin_id']]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($current, (string)$hash)) {
                $errors[] = 'Your current password is not correct.';
            } else {
                try {
                    $up = db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
                    $up->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
                    flash('success', 'Your password has been changed successfully.');
                } catch (Throwable $e) {
                    $errors[] = 'The password could not be changed: ' . $e->getMessage();
                }
            }
        }
    }

    if (empty($errors)) {
        redirect('settings.php');
    }
}

admin_header('Settings', 'settings.php');
?>

<div class="mb-6">
  <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Business Settings</h1>
  <p class="text-sm text-gray-500 mt-1">These values appear in the header, footer and contact pages of the public website.</p>
</div>

<?php if (!empty($errors)): ?>
  <div class="mb-5 px-4 py-3 rounded-lg bg-red-100 text-red-800 border border-red-200">
    <p class="font-semibold mb-1">Please fix the following:</p>
    <ul class="list-disc list-inside space-y-1 text-sm">
      <?php foreach ($errors as $error): ?><li><?php echo esc($error); ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
  <!-- Business details -->
  <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
    <h2 class="font-heading font-semibold text-primary mb-5">Business Information</h2>

    <form method="post" action="settings.php" class="grid sm:grid-cols-2 gap-4">
      <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
      <input type="hidden" name="action" value="settings">

      <div>
        <label for="business_name" class="block text-sm font-medium mb-1">Business Name *</label>
        <input id="business_name" name="business_name" value="<?php echo esc(setting('business_name')); ?>" required maxlength="100"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      </div>

      <div>
        <label for="contact_number" class="block text-sm font-medium mb-1">Contact Number *</label>
        <input id="contact_number" name="contact_number" value="<?php echo esc(setting('contact_number')); ?>" required maxlength="50"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      </div>

      <div>
        <label for="email" class="block text-sm font-medium mb-1">Email Address</label>
        <input id="email" name="email" type="email" value="<?php echo esc(setting('email')); ?>" maxlength="150"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      </div>

      <div>
        <label for="address" class="block text-sm font-medium mb-1">Address *</label>
        <input id="address" name="address" value="<?php echo esc(setting('address')); ?>" required maxlength="255"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      </div>

      <div class="sm:col-span-2">
        <label for="business_hours" class="block text-sm font-medium mb-1">Business Hours</label>
        <input id="business_hours" name="business_hours" value="<?php echo esc(setting('business_hours')); ?>" maxlength="120" placeholder="Mon-Sat 8:00 AM - 5:00 PM"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      </div>

      <div class="sm:col-span-2">
        <label for="business_description" class="block text-sm font-medium mb-1">Business Description / Tagline</label>
        <textarea id="business_description" name="business_description" rows="3" maxlength="500"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary"><?php echo esc(setting('business_description')); ?></textarea>
      </div>

      <div class="sm:col-span-2">
        <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition font-medium">Save Settings</button>
      </div>
    </form>
  </div>

  <!-- Password -->
  <div class="bg-white rounded-xl shadow p-6">
    <h2 class="font-heading font-semibold text-primary mb-1">Change Password</h2>
    <p class="text-xs text-gray-500 mb-5">Signed in as <strong><?php echo esc($_SESSION['admin_username'] ?? 'admin'); ?></strong></p>

    <form method="post" action="settings.php" class="space-y-4">
      <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
      <input type="hidden" name="action" value="password">

      <div>
        <label for="current_password" class="block text-sm font-medium mb-1">Current Password *</label>
        <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      </div>

      <div>
        <label for="new_password" class="block text-sm font-medium mb-1">New Password *</label>
        <input id="new_password" name="new_password" type="password" required minlength="8" autocomplete="new-password"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters.</p>
      </div>

      <div>
        <label for="confirm_password" class="block text-sm font-medium mb-1">Confirm New Password *</label>
        <input id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
      </div>

      <button type="submit" class="w-full bg-secondary text-white px-6 py-2.5 rounded-lg hover:bg-primary transition font-medium">Update Password</button>
    </form>

    <div class="mt-6 pt-5 border-t border-cream">
      <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Add another administrator</p>
      <a href="create-admin.php" class="text-sm text-secondary hover:underline">Create an additional admin account &rarr;</a>
    </div>
  </div>
</div>

<?php admin_footer(); ?>
