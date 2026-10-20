<?php
// admin/messages.php — contact form messages
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/admin-layout.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? null)) {
        flash('error', 'Invalid security token. Please try again.');
        redirect('messages.php');
    }
    if (isset($_POST['mark_read'])) {
        $stmt = db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
        $stmt->execute([(int)$_POST['mark_read']]);
        flash('success', 'Message marked as read.');
    }
    if (isset($_POST['mark_unread'])) {
        $stmt = db()->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?');
        $stmt->execute([(int)$_POST['mark_unread']]);
        flash('success', 'Message marked as unread.');
    }
    if (isset($_POST['delete_id'])) {
        $stmt = db()->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->execute([(int)$_POST['delete_id']]);
        flash('success', 'Message deleted.');
    }
    redirect('messages.php');
}

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC LIMIT 100')->fetchAll();
$unread   = (int)db()->query('SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0')->fetch()['c'];
$total    = (int)db()->query('SELECT COUNT(*) c FROM contact_messages')->fetch()['c'];

admin_header('Messages', 'messages.php');
?>

<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
  <div>
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-primary">Contact Messages</h1>
    <p class="text-sm text-gray-500 mt-1"><?php echo $total; ?> message<?php echo $total === 1 ? '' : 's'; ?> received &bull; <?php echo $unread; ?> unread.</p>
  </div>
</div>

<?php if (empty($messages)): ?>
  <div class="bg-white rounded-xl shadow text-center py-16">
    <p class="text-4xl mb-3" aria-hidden="true">✉️</p>
    <p class="text-gray-600">No messages yet. Messages sent through the Contact Us page appear here.</p>
  </div>
<?php else: ?>
  <div class="space-y-4">
    <?php foreach ($messages as $msg): ?>
      <article class="bg-white rounded-xl shadow p-5 <?php echo $msg['is_read'] ? '' : 'border-l-4 border-l-gold'; ?>">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
          <div>
            <h2 class="font-heading font-semibold text-charcoal">
              <?php echo esc($msg['subject'] !== null && $msg['subject'] !== '' ? $msg['subject'] : 'General inquiry'); ?>
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">
              From <strong class="text-gray-700"><?php echo esc($msg['name']); ?></strong>
              &lt;<a href="mailto:<?php echo esc($msg['email']); ?>" class="text-secondary hover:underline"><?php echo esc($msg['email']); ?></a>&gt;
              <?php if (!empty($msg['phone'])): ?> &bull; <?php echo esc($msg['phone']); ?><?php endif; ?>
            </p>
          </div>
          <div class="text-right">
            <p class="text-xs text-gray-400"><?php echo date('M j, Y g:i A', strtotime((string)$msg['created_at'])); ?></p>
            <?php if (!$msg['is_read']): ?>
              <span class="inline-block mt-1 px-2 py-0.5 rounded-full bg-gold/25 text-gold text-xs font-bold">NEW</span>
            <?php endif; ?>
          </div>
        </div>

        <p class="text-sm text-gray-700 whitespace-pre-line bg-cream rounded-lg p-4"><?php echo esc($msg['message']); ?></p>

        <div class="flex flex-wrap gap-4 mt-4 text-sm">
          <?php if ($msg['is_read']): ?>
            <form method="post" class="inline">
              <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
              <input type="hidden" name="mark_unread" value="<?php echo (int)$msg['id']; ?>">
              <button class="text-gray-600 hover:text-primary">Mark as unread</button>
            </form>
          <?php else: ?>
            <form method="post" class="inline">
              <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
              <input type="hidden" name="mark_read" value="<?php echo (int)$msg['id']; ?>">
              <button class="text-secondary hover:underline">Mark as read</button>
            </form>
          <?php endif; ?>

          <form method="post" class="inline" data-confirm="Delete this message permanently?">
            <input type="hidden" name="csrf" value="<?php echo esc(csrf_token()); ?>">
            <input type="hidden" name="delete_id" value="<?php echo (int)$msg['id']; ?>">
            <button class="text-red-600 hover:underline">Delete</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php admin_footer(); ?>
