<?php
require_once 'util.php';
require_once 'config.php';
generate_head('Services');
generate_header();

function format_duration_text(int $minutes): string {
    if ($minutes < 60) {
        return $minutes . ' mins';
    }
    $hrs = intdiv($minutes, 60);
    $mins = $minutes % 60;
    if ($mins === 0) {
        return $hrs . ' hr' . ($hrs > 1 ? 's' : '');
    }
    return $hrs . ' hr' . ($hrs > 1 ? 's' : '') . ' ' . $mins . ' mins';
}

$servicesByType = [];
try {
    $sql = "SELECT s.label, s.description, s.price, s.duration_minutes, st.name AS type_name
            FROM services s
            JOIN service_types st ON s.type_id = st.type_id
            WHERE s.is_active = 1
            ORDER BY FIELD(st.name,'lashes_full_set','refill','extra','brow'), s.label";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $servicesByType[$row['type_name']][] = $row;
    }
} catch (PDOException $e) {
    error_log('Services fetch error: ' . $e->getMessage());
}

$headings = [
    'lashes_full_set' => 'Lashes (Full Sets)',
    'refill'          => 'Refills',
    'extra'           => 'Extras (add-ons)',
    'brow'            => 'Brow Services',
];

?>
<style>.selected-extra {
    background: var(--deep-pink-dark) !important;
    color: white !important;
    transform: scale(0.95);
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.2);
}

/* Ensure the main container doesn't get covered by the summary bar */
.container {
    padding-bottom: 120px;
}
</style>
<div class="container">
  <?php foreach (['lashes_full_set','refill','extra','brow','Home Service'] as $type): ?>
    <?php if (!empty($servicesByType[$type])): ?>
      <h2 class="group-title"><?php echo htmlspecialchars($headings[$type] ?? ucfirst($type)); ?></h2>
      <div class="grid">
        <?php foreach ($servicesByType[$type] as $svc):
            $name    = htmlspecialchars($svc['label']);
            $price   = (float)$svc['price'];
            $minutes = (int)$svc['duration_minutes'];
            $note    = $svc['description'] ? htmlspecialchars($svc['description']) : '';
            $isExtra = ($type === 'extra');
            $durationText = $isExtra ? 'Adds: ' . format_duration_text($minutes) : 'Duration: ' . format_duration_text($minutes);
            $priceText = $isExtra ? '+₵' . rtrim(rtrim(number_format($price,2,'.',''), '0'), '.') : '₵' . rtrim(rtrim(number_format($price,2,'.',''), '0'), '.');
            $btnId = $isExtra ? 'extra-' . $name : '';
        ?>
          <div class="card">
            <div class="service-title"><?php echo $name; ?> — <?php echo $priceText; ?></div>
            <div class="duration"><?php echo $durationText; ?></div>
            <?php if ($note !== ''): ?><div class="note"><?php echo $note; ?></div><?php endif; ?>
            <div style="margin-top:12px">
              <?php if ($isExtra): ?>
                <a id="<?php echo $btnId; ?>" class="btn" onclick="toggleExtra('<?php echo $name; ?>',<?php echo $price; ?>,<?php echo $minutes; ?>)">Add Extra</a>
              <?php else: ?>
                <a class="btn" onclick="selectService('<?php echo $name; ?>',<?php echo $price; ?>,<?php echo $minutes; ?>)">Book Now</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>

<div id="summary-bar" class="card" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 600px; display: none; z-index: 1000; border: 2px solid var(--deep-pink); background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 20px;">
        <div id="summary-text" style="color: var(--deep-pink-dark); font-weight: 600;">
            Selected: <span id="current-selection">None</span> | Total: <span id="total-price">₵0</span>
        </div>
        <a href="booking.php" class="btn" style="padding: 8px 20px;">Finalize Booking</a>
    </div>
</div>

<script>
const SESSION_API = 'api/session.php';

// Modified update UI to handle the summary bar and persistent selection
async function updateUI() {
    try {
        const response = await fetch(`${SESSION_API}?action=get_all`);
        const data = await response.json();
        if (!data.success) return;

        const mainSvc = data.service || null;
        const selectedExtras = data.extras || [];
        const summaryBar = document.getElementById('summary-bar');
        
        // Update Extras Buttons
        document.querySelectorAll('[id^="extra-"]').forEach(btn => {
            btn.classList.remove('selected-extra');
            btn.textContent = "Add Extra";
        });
        
        selectedExtras.forEach(ex => {
            const btn = document.getElementById(`extra-${ex.name}`);
            if (btn) {
                btn.classList.add('selected-extra');
                btn.textContent = "Selected";
            }
        });

        // Update Summary Bar Logic
        if (mainSvc) {
            summaryBar.style.display = 'block';
            let totalPrice = parseFloat(mainSvc.price);
            selectedExtras.forEach(ex => totalPrice += parseFloat(ex.price));
            
            document.getElementById('current-selection').textContent = mainSvc.name;
            document.getElementById('total-price').textContent = '₵' + totalPrice.toFixed(2);
        } else {
            summaryBar.style.display = 'none';
        }
    } catch (err) { console.error('UI Sync error:', err); }
}

window.onload = updateUI;

async function selectService(name, price, minutes) {
    try {
        const resp = await fetch(`${SESSION_API}?action=set_service`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ service: { name, price, minutes } })
        });
        const result = await resp.json();
        if (result.success) {
            Swal.fire({
                icon: 'success', title: `${name} Selected`, 
                toast: true, position: 'top-end', showConfirmButton: false, timer: 2000
            });
            updateUI(); // Refresh totals instead of redirecting
        }
    } catch (error) { console.error(error); }
}

async function toggleExtra(name, price, minutes) {
    try {
        const resp = await fetch(`${SESSION_API}?action=toggle_extra`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ extra: { name, price, minutes } })
        });
        const result = await resp.json();
        if (result.success) {
            updateUI(); // Refresh totals
        }
    } catch (error) { console.error(error); }
}
</script>
</div>