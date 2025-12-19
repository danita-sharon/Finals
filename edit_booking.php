<?php
session_start();
require_once 'config.php';
require_once 'util.php';

// Require login
if (empty($_SESSION['user_id'])) {
    header('Location: login2.php');
    exit;
}

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : (isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0);
if ($bookingId <= 0) {
    header('Location: profile.php?error=invalid_id');
    exit;
}

// Fetch booking
try {
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch();
} catch (Exception $e) {
    $booking = false;
}

if (!$booking) {
    header('Location: profile.php?error=not_found');
    exit;
}

// Authorization: owner or admin
$isOwner = ((int)$_SESSION['user_id'] === (int)$booking['user_id']);
$isAdmin = (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
if (!$isOwner && !$isAdmin) {
    header('Location: profile.php?error=forbidden');
    exit;
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enable error reporting temporarily to surface issues while saving
    @ini_set('display_errors', '1');
    error_reporting(E_ALL);
    $date = $_POST['booking_date'] ?? '';
    $time = $_POST['booking_time'] ?? '';
    $persons = isset($_POST['persons']) ? (int)$_POST['persons'] : (int)$booking['persons'];
    $home = isset($_POST['home_service']) ? 1 : 0;
    $notes = $_POST['notes'] ?? '';
    $extras_raw = $_POST['extras'] ?? ''; // expecting comma-separated list
    $extras_arr = array_values(array_filter(array_map('trim', explode(',', $extras_raw))));
    $extras_json = !empty($extras_arr) ? json_encode($extras_arr) : null;

    // Validate date/time
    $start_ts = strtotime("{$date} {$time}");
    if ($start_ts === false) {
        $err = 'Invalid date or time.';
    } else {
        $start_time = date('H:i:s', $start_ts);
        $duration = (int)$booking['duration_minutes'];
        $end_ts = $start_ts + ($duration * 60);
        $end_time = date('H:i:s', $end_ts);

        // Check for overlapping bookings on same date excluding current booking
        $sql_check = "SELECT id FROM bookings WHERE booking_date = :date AND id != :id AND NOT (ADDTIME(booking_time, SEC_TO_TIME(duration_minutes * 60)) <= :start_time OR booking_time >= :end_time) LIMIT 1";
        $chk = $pdo->prepare($sql_check);
        try {
            $chk->execute(['date' => $date, 'start_time' => $start_time, 'end_time' => $end_time, 'id' => $bookingId]);
        } catch (Exception $e) {
            error_log('Edit booking check error: ' . $e->getMessage());
            $err = 'An internal error occurred while validating the booking time.';
        }

        if (empty($err) && $chk->fetch()) {
            $err = 'Selected time conflicts with another booking. Please choose a different time.';
        } else {
            // Update booking (use correct column name `is_home_service`)
            $updateSql = 'UPDATE bookings SET booking_date = :date, booking_time = :time, persons = :persons, is_home_service = :is_home, notes = :notes, extras_list = :extras WHERE id = :id';
            $u = $pdo->prepare($updateSql);
            try {
                $u->execute([
                    'date' => $date,
                    'time' => $start_time,
                    'persons' => $persons,
                    'is_home' => $home,
                    'notes' => $notes,
                    'extras' => $extras_json,
                    'id' => $bookingId
                ]);
                header('Location: profile.php?updated=1');
                exit;
            } catch (Exception $e) {
                error_log('Edit booking update error: ' . $e->getMessage());
                $err = 'Failed to save booking changes. Please try again.';
            }
        }
    }
}

generate_head('Edit Booking');
generate_header();

echo '<div class="container">';
echo '<div class="card" style="max-width:640px;margin:40px auto;padding:20px;">';
echo '<h2 style="color:#b3005a;margin-top:0;">Edit Booking</h2>';
if (!empty($err)) {
    echo '<div class="note" style="color:#a10044;margin-bottom:12px;">' . htmlspecialchars($err) . '</div>';
}

$selectedNames = [];
$current_extras = '';
if (!empty($booking['extras_list'])) {
    $decoded = json_decode($booking['extras_list'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $it) {
            if (is_array($it) && isset($it['name'])) {
                $selectedNames[] = $it['name'];
            } elseif (is_string($it) || is_numeric($it)) {
                $selectedNames[] = (string)$it;
            }
        }
    } elseif (is_string($decoded)) {
        $selectedNames[] = $decoded;
    }
    $current_extras = implode(', ', $selectedNames);
}

echo '<form method="post">';
echo '<input type="hidden" name="booking_id" value="' . $bookingId . '">';
echo '<label class="group-title">Date</label>';
echo '<input type="date" name="booking_date" required value="' . htmlspecialchars($booking['booking_date']) . '" style="width:100%;padding:10px;margin-bottom:12px;border-radius:8px;border:1px solid #eee;">';
echo '<label class="group-title">Time</label>';
echo '<input type="time" name="booking_time" required value="' . htmlspecialchars(substr($booking['booking_time'],0,5)) . '" style="width:100%;padding:10px;margin-bottom:12px;border-radius:8px;border:1px solid #eee;">';
// Fetch available extras from services table to display like services.php
try {
    $extrasStmt = $pdo->prepare("SELECT s.label, s.price, s.duration_minutes FROM services s JOIN service_types st ON s.type_id = st.type_id WHERE st.name = 'extra' AND s.is_active = 1 ORDER BY s.label");
    $extrasStmt->execute();
    $availableExtras = $extrasStmt->fetchAll();
} catch (Exception $e) {
    $availableExtras = [];
}

echo '<label class="group-title">Extras (click to toggle)</label>';
// Hidden input to hold comma-separated extras for POST processing
echo '<input type="hidden" name="extras" id="extras_input" value="' . htmlspecialchars($current_extras) . '">';
echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:12px;">';
// helper to make slug-safe IDs
function slugify(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9\-\_]+/u', '-', $s);
    $s = trim($s, '-');
    return $s;
}

foreach ($availableExtras as $ex) {
    $ename = $ex['label'];
    $enameHtml = htmlspecialchars($ename);
    $eprice = (float)$ex['price'];
    $emin = (int)$ex['duration_minutes'];
    $slug = slugify($ename);
    $btnId = 'extra-' . $slug;
    // Determine initial selected state
    $isSelected = in_array($ename, $selectedNames, true) || in_array($ename, array_map('trim', explode(',', $current_extras)), true);

    echo '<div class="card">';
    echo '<div class="service-title">' . $enameHtml . ' — +₵' . rtrim(rtrim(number_format($eprice,2,'.',''), '0'), '.') . '</div>';
    echo '<div class="duration">Adds: ' . ($emin > 0 ? $emin . ' mins' : '—') . '</div>';
    echo '<div style="margin-top:10px"><button type="button" id="' . $btnId . '" class="btn' . ($isSelected ? ' selected-extra' : '') . '" onclick="toggleExtraEdit(' . json_encode($ename) . ', ' . json_encode($slug) . ')">' . ($isSelected ? 'Selected' : 'Toggle') . '</button></div>';
    echo '</div>';
}
echo '</div>';
echo '<label class="group-title">Persons</label>';
echo '<input type="number" name="persons" min="1" value="' . (int)$booking['persons'] . '" style="width:100%;padding:10px;margin-bottom:12px;border-radius:8px;border:1px solid #eee;">';
echo '<label style="display:block;margin-bottom:8px;"><input type="checkbox" name="home_service"' . ($booking['is_home_service'] ? ' checked' : '') . '> Home service</label>';
echo '<label class="group-title">Notes</label>';
echo '<textarea name="notes" style="width:100%;padding:10px;border-radius:8px;border:1px solid #eee;min-height:100px;">' . htmlspecialchars($booking['notes']) . '</textarea>';
echo '<div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;">';
echo '<a class="btn" href="profile.php" style="background:#ccc;color:#333;">Cancel</a>';
echo '<button class="btn" type="submit">Save changes</button>';
echo '</div>';
echo '</form>';

// JS to handle extras selection in edit form
echo '<script>';
echo 'let selectedExtras = [];' . "\n";
echo 'function updateExtrasInput(){ document.getElementById("extras_input").value = selectedExtras.join(", "); }' . "\n";
echo 'function toggleExtraEdit(name, slug){' . "\n";
echo '  const idx = selectedExtras.indexOf(name);' . "\n";
echo '  const btn = document.getElementById("extra-" + slug);' . "\n";
echo '  if(idx === -1){ selectedExtras.push(name); if(btn){ btn.classList.add("selected-extra"); btn.textContent = "Selected"; } }' . "\n";
echo '  else { selectedExtras.splice(idx,1); if(btn){ btn.classList.remove("selected-extra"); btn.textContent = "Toggle"; } }' . "\n";
echo '  updateExtrasInput();' . "\n";
echo '}' . "\n";
// initialize from server value
$init = $current_extras;
echo 'window.addEventListener("DOMContentLoaded", function(){' . "\n";
echo '  const initVal = ' . json_encode($init) . ';' . "\n";
echo '  if(initVal){ selectedExtras = initVal.split(",").map(s=>s.trim()).filter(Boolean); }' . "\n";
echo '  selectedExtras.forEach(function(name){ const slug = name.toLowerCase().replace(/[^a-z0-9\-\_]+/g,"-").replace(/(^-|-$)/g,""); const btn = document.getElementById("extra-" + slug); if(btn){ btn.classList.add("selected-extra"); btn.textContent = "Selected"; } });' . "\n";
echo '  updateExtrasInput();' . "\n";
echo '});' . "\n";
echo '</script>';

echo '</div></div>';

generate_footer();

?>
