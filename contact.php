<?php
// contact.php — Contact information and message form
$pageTitle = 'Contact Us';
require_once 'includes/header.php';
require_once 'includes/functions.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['name']    = trim((string)($_POST['name'] ?? ''));
        $old['email']   = trim((string)($_POST['email'] ?? ''));
        $old['phone']   = trim((string)($_POST['phone'] ?? ''));
        $old['subject'] = trim((string)($_POST['subject'] ?? ''));
        $old['message'] = trim((string)($_POST['message'] ?? ''));

        if ($old['name'] === '') {
            $errors[] = 'Please enter your name.';
        }
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (mb_strlen($old['message']) < 10) {
            $errors[] = 'Please write a message of at least 10 characters.';
        }
        if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) {
            $errors[] = 'Please enter a valid contact number.';
        }

        if (empty($errors)) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $old['name'],
                    $old['email'],
                    $old['phone'] !== '' ? $old['phone'] : null,
                    $old['subject'] !== '' ? $old['subject'] : null,
                    $old['message'],
                ]);
                flash('success', 'Thank you! Your message has been received. We will get back to you soon.');
                redirect(app_base() . 'contact.php');
            } catch (Throwable $e) {
                $errors[] = 'Your message could not be saved right now. Please try again later.';
            }
        }
    }
}
$flashes = take_flashes();
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="text-center max-w-2xl mx-auto mb-12">
    <h1 class="font-heading text-3xl md:text-4xl font-bold text-primary mb-3">Contact Us</h1>
    <p class="text-gray-600">
      Questions about our chickens, availability or orders? Send us a message or reach the farm directly.
    </p>
  </div>

  <div class="grid lg:grid-cols-5 gap-8">
    <!-- Contact details -->
    <div class="lg:col-span-2 space-y-4">
      <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="font-heading text-lg font-semibold text-primary mb-4">Farm Details</h2>
        <ul class="space-y-4 text-sm">
          <li class="flex gap-3">
            <span class="text-xl" aria-hidden="true">📞</span>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Phone</p>
              <a href="tel:<?php echo esc(preg_replace('/\s+/', '', setting('contact_number'))); ?>" class="font-semibold text-charcoal hover:text-primary"><?php echo esc(setting('contact_number')); ?></a>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="text-xl" aria-hidden="true">✉️</span>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Email</p>
              <a href="mailto:<?php echo esc(setting('email')); ?>" class="font-semibold text-charcoal hover:text-primary"><?php echo esc(setting('email')); ?></a>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="text-xl" aria-hidden="true">📍</span>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Address</p>
              <p class="font-semibold text-charcoal"><?php echo esc(setting('address')); ?></p>
            </div>
          </li>
          <li class="flex gap-3">
            <span class="text-xl" aria-hidden="true">🕐</span>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-500">Business Hours</p>
              <p class="font-semibold text-charcoal"><?php echo esc(setting('business_hours')); ?></p>
            </div>
          </li>
        </ul>
      </div>

      <div class="bg-primary text-white rounded-2xl shadow p-6">
        <h2 class="font-heading text-lg font-semibold mb-2">Looking for chickens?</h2>
        <p class="text-sm text-white/85 mb-4">Browse the live catalog to see what is available right now.</p>
        <a href="<?php echo esc(app_base()); ?>chickens.php" class="inline-block bg-gold text-charcoal px-5 py-2 rounded-lg font-semibold hover:bg-white transition">
          View the Catalog
        </a>
      </div>
    </div>

    <!-- Message form -->
    <div class="lg:col-span-3">
      <div class="bg-white rounded-2xl shadow p-6 sm:p-8">
        <h2 class="font-heading text-xl font-semibold text-primary mb-1">Send a Message</h2>
        <p class="text-sm text-gray-500 mb-6">All fields marked with * are required.</p>

        <?php foreach ($flashes as $flash): ?>
          <div class="mb-4 px-4 py-3 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo esc($flash['message']); ?>
          </div>
        <?php endforeach; ?>

        <?php if (!empty($errors)): ?>
          <div class="mb-4 px-4 py-3 rounded-lg bg-red-100 text-red-800">
            <p class="font-semibold mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1 text-sm">
              <?php foreach ($errors as $error): ?>
                <li><?php echo esc($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc(app_base()); ?>contact.php" class="grid sm:grid-cols-2 gap-4">
          <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">

          <div>
            <label for="name" class="block text-sm font-medium mb-1">Your Name *</label>
            <input id="name" name="name" value="<?php echo esc($old['name']); ?>" required maxlength="100"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </div>

          <div>
            <label for="email" class="block text-sm font-medium mb-1">Email Address *</label>
            <input id="email" name="email" type="email" value="<?php echo esc($old['email']); ?>" required maxlength="150"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </div>

          <div>
            <label for="phone" class="block text-sm font-medium mb-1">Contact Number</label>
            <input id="phone" name="phone" value="<?php echo esc($old['phone']); ?>" maxlength="50" placeholder="+63 900 000 0000"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </div>

          <div>
            <label for="subject" class="block text-sm font-medium mb-1">Subject</label>
            <input id="subject" name="subject" value="<?php echo esc($old['subject']); ?>" maxlength="150" placeholder="e.g. Availability inquiry"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
          </div>

          <div class="sm:col-span-2">
            <label for="message" class="block text-sm font-medium mb-1">Message *</label>
            <textarea id="message" name="message" rows="5" required maxlength="2000"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary"><?php echo esc($old['message']); ?></textarea>
          </div>

          <div class="sm:col-span-2">
            <button type="submit" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">Send Message</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
