<?php
require_once 'config.php';
require_once 'util.php';

generate_head('Admin: Services');
generate_header();

function fetch_service_types(PDO $pdo): array {
    $stmt = $pdo->query('SELECT type_id, name, description FROM service_types ORDER BY name');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetch_services(PDO $pdo): array {
    $sql = "SELECT s.service_id, s.label, s.description, s.price, s.duration_minutes, s.is_active, st.name AS type_name
            FROM services s
            JOIN service_types st ON s.type_id = st.type_id
            ORDER BY FIELD(st.name,'lashes_full_set','refill','extra','brow'), s.label";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$errors = [];
$notices = [];
$editRow = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $label = trim($_POST['label'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $minutes = $_POST['duration'] ?? '';
    $type_id = (int)($_POST['type_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $service_id = (int)($_POST['service_id'] ?? 0);

    if (in_array($action, ['create','update'], true)) {
        if ($label === '') $errors[] = 'Label is required.';
        if (!is_numeric($price)) $errors[] = 'Price must be numeric.';
        if (!ctype_digit((string)$minutes)) $errors[] = 'Duration must be whole minutes.';
        if ($type_id <= 0) $errors[] = 'Type is required.';
    }

    if (!$errors) {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO services (type_id, label, description, price, duration_minutes, is_active) VALUES (:type_id,:label,:description,:price,:minutes,:active)');
                $stmt->execute([
                    'type_id' => $type_id,
                    'label' => $label,
                    'description' => $desc ?: null,
                    'price' => $price,
                    'minutes' => $minutes,
                    'active' => $is_active,
                ]);
                $notices[] = 'Service created.';
            } elseif ($action === 'update' && $service_id > 0) {
                $stmt = $pdo->prepare('UPDATE services SET type_id=:type_id, label=:label, description=:description, price=:price, duration_minutes=:minutes, is_active=:active WHERE service_id=:id');
                $stmt->execute([
                    'type_id' => $type_id,
                    'label' => $label,
                    'description' => $desc ?: null,
                    'price' => $price,
                    'minutes' => $minutes,
                    'active' => $is_active,
                    'id' => $service_id,
                ]);
                $notices[] = 'Service updated.';
            } elseif ($action === 'delete' && $service_id > 0) {
                $stmt = $pdo->prepare('DELETE FROM services WHERE service_id=:id');
                $stmt->execute(['id' => $service_id]);
                $notices[] = 'Service deleted.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$types = fetch_service_types($pdo);
$services = fetch_services($pdo);

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($services as $row) {
        if ((int)$row['service_id'] === $editId) {
            $editRow = $row;
            break;
        }
    }
}
?>

<div class="container" style="padding-bottom:32px">
  <h2 class="group-title">Manage Services</h2>

  <?php if ($errors): ?>
    <div class="card" style="border-left:4px solid #d9534f; margin-bottom:12px;">
      <ul class="note" style="margin:0; padding-left:18px; color:#b52b27;">
        <?php foreach ($errors as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($notices): ?>
    <div class="card" style="border-left:4px solid #2d9f4a; margin-bottom:12px;">
      <ul class="note" style="margin:0; padding-left:18px; color:#2d7f3b;">
        <?php foreach ($notices as $msg): ?><li><?php echo htmlspecialchars($msg); ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:16px">
    <h3 style="margin-top:0;"><?php echo $editRow ? 'Edit Service' : 'Create Service'; ?></h3>
    <form method="post" style="display:grid; gap:10px;">
      <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>">
      <?php if ($editRow): ?><input type="hidden" name="service_id" value="<?php echo (int)$editRow['service_id']; ?>"><?php endif; ?>
      <label class="small-muted">Label<br>
        <input name="label" value="<?php echo htmlspecialchars($editRow['label'] ?? ''); ?>" required style="width:100%; padding:8px;">
      </label>
      <label class="small-muted">Description (optional)<br>
        <textarea name="description" rows="3" style="width:100%; padding:8px;"><?php echo htmlspecialchars($editRow['description'] ?? ''); ?></textarea>
      </label>
      <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <label class="small-muted" style="flex:1; min-width:140px;">Price (₵)<br>
          <input name="price" type="number" step="0.01" value="<?php echo htmlspecialchars($editRow['price'] ?? ''); ?>" required style="width:100%; padding:8px;">
        </label>
        <label class="small-muted" style="flex:1; min-width:140px;">Duration (mins)<br>
          <input name="duration" type="number" step="1" value="<?php echo htmlspecialchars($editRow['duration_minutes'] ?? ''); ?>" required style="width:100%; padding:8px;">
        </label>
        <label class="small-muted" style="flex:1; min-width:160px;">Type<br>
          <select name="type_id" required style="width:100%; padding:8px;">
            <option value="">Select type</option>
            <?php foreach ($types as $t): ?>
              <option value="<?php echo (int)$t['type_id']; ?>" <?php echo (!empty($editRow) && (int)$editRow['type_name'] === (int)$t['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <label style="display:flex; align-items:center; gap:8px;">
        <input type="checkbox" name="is_active" <?php echo (!empty($editRow) ? ((int)$editRow['is_active'] === 1 ? 'checked' : '') : 'checked'); ?>>
        <span class="small-muted">Active</span>
      </label>
      <div>
        <button class="btn" type="submit" style="min-width:140px;"><?php echo $editRow ? 'Update Service' : 'Create Service'; ?></button>
        <?php if ($editRow): ?>
          <a class="btn" href="admin_services.php" style="background:#fff; color:var(--deep-pink); box-shadow:none; margin-left:8px;">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0;">All Services</h3>
    <div class="note" style="margin-bottom:10px;">Click edit to load into the form. Delete is immediate.</div>
    <div class="grid">
      <?php foreach ($services as $svc): ?>
        <div class="card" style="box-shadow:none; border:1px solid #eee;">
          <div class="service-title" style="margin-bottom:4px;"><?php echo htmlspecialchars($svc['label']); ?> — ₵<?php echo rtrim(rtrim(number_format($svc['price'],2,'.',''), '0'), '.'); ?></div>
          <div class="duration">Duration: <?php echo (int)$svc['duration_minutes']; ?> mins</div>
          <div class="note">Type: <?php echo htmlspecialchars($svc['type_name']); ?> | Status: <?php echo $svc['is_active'] ? 'Active' : 'Inactive'; ?></div>
          <?php if (!empty($svc['description'])): ?><div class="note" style="margin-top:6px;"><?php echo htmlspecialchars($svc['description']); ?></div><?php endif; ?>
          <div style="margin-top:10px; display:flex; gap:8px;">
            <a class="btn" style="background:#fff; color:var(--deep-pink); box-shadow:none;" href="admin_services.php?edit=<?php echo (int)$svc['service_id']; ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Delete this service?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="service_id" value="<?php echo (int)$svc['service_id']; ?>">
              <button class="btn" type="submit" style="background:#fff; color:#b52b27; box-shadow:none;">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php generate_footer(); ?>
