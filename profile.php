<?php
session_start();
require_once 'config.php';
require_once 'util.php';

// Require login
if (empty($_SESSION['user_id'])) {
    header('Location: login2.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

generate_head('My Profile');
generate_header();

echo '<div class="container">';
echo '<div class="card" style="padding:40px; max-width:900px; margin:40px auto; border-radius: 20px;">';
echo '<h2 style="color:var(--deep-pink-dark); margin-top:0; text-align:center;">Your Lash Profile</h2>';
echo '<p class="note" style="text-align:center; margin-bottom:40px;">Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '! Here are your scheduled sessions.</p>';

// Fetch bookings for this user
try {
    $sql = "SELECT b.id, b.booking_date, b.booking_time, b.total_price, b.duration_minutes, b.extras_list, b.notes, s.label AS service_label
            FROM bookings b
            LEFT JOIN services s ON s.service_id = b.service_id
            WHERE b.user_id = :uid
            ORDER BY b.booking_date DESC, b.booking_time DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $userId]);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    $bookings = [];
}

if (empty($bookings)) {
    echo '<div style="text-align:center; padding:40px;">';
    echo '<p class="note">You haven\'t booked a transformation yet.</p>';
    echo '<a href="services.php" class="btn" style="margin-top:20px;">Book Your First Set</a>';
    echo '</div>';
} else {
    echo '<div style="display:grid; gap:20px;">';
    foreach ($bookings as $b) {
        $service = htmlspecialchars($b['service_label'] ?? 'Custom Service');
        $date = date("F j, Y", strtotime($b['booking_date']));
        $time = date("g:i A", strtotime($b['booking_time']));
        $price = number_format($b['total_price'], 2);
        
        echo '<div class="card" style="border: 1px solid var(--pastel-1); box-shadow: 0 4px 15px rgba(0,0,0,0.05);">';
        echo '<div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px;">';
        
        echo '<div>';
        echo '<div class="service-title" style="font-size:20px;">' . $service . '</div>';
        echo '<div class="note" style="color:var(--deep-pink); font-weight:600;">' . $date . ' at ' . $time . '</div>';
        echo '<div class="small-muted">' . $b['duration_minutes'] . ' minutes session</div>';
        echo '</div>';
        
        echo '<div style="text-align:right;">';
        echo '<div class="price" style="font-size:22px; color:var(--deep-pink-dark);">₵' . $price . '</div>';
        echo '<div class="small-muted">Ref: #' . $b['id'] . '</div>';
        echo '</div>';
        
        echo '</div>'; // End Header flex

        // Extras Section
        if (!empty($b['extras_list'])) {
            $extras = json_decode($b['extras_list'], true);
            if (is_array($extras) && count($extras) > 0) {
                echo '<div style="margin-top:15px; border-top: 1px solid #f9f9f9; padding-top:10px;">';
                echo '<span class="small-muted" style="text-transform:uppercase; font-size:11px; letter-spacing:1px;">Added Extras:</span>';
                echo '<div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:5px;">';
                foreach ($extras as $ex) {
                    $ename = htmlspecialchars(is_array($ex) ? ($ex['name'] ?? 'Extra') : $ex);
                    echo '<span style="background:var(--pastel-1); color:var(--deep-pink-dark); padding:4px 12px; border-radius:15px; font-size:12px; font-weight:600;">' . $ename . '</span>';
                }
                echo '</div></div>';
            }
        }

        if ($b['notes']) {
            echo '<div class="note" style="margin-top:15px; font-style:italic;">" ' . htmlspecialchars($b['notes']) . ' "</div>';
        }

        // Action Buttons
        echo '<div style="margin-top:20px; display:flex; gap:10px; justify-content:flex-end; border-top: 1px solid #f9f9f9; padding-top:15px;">';
        
        // Edit Button (Blue Accent)
        echo '<a class="btn btn-secondary" href="edit_booking.php?booking_id=' . $b['id'] . '" style="padding: 8px 20px; font-size:13px; background-color:var(--deep-pink-dark);">Edit Appointment</a>';
        
        // Cancel Form (Red Accent)
        echo '<form method="post" action="api/delete_booking.php" onsubmit="return confirm(\'Are you sure you want to cancel this booking?\');">';
        echo '<input type="hidden" name="booking_id" value="' . $b['id'] . '">';
        echo '<button type="submit" class="btn btn-danger" style="padding: 8px 20px; font-size:13px;">Cancel</button>';
        echo '</form>';
        echo '<a href="logout.php" class="btn btn-danger" style="padding: 8px 20px; font-size:14px; text-decoration:none;">Logout</a>';
        echo '</div>'; // End Actions
        echo '</div>'; // End Booking Card
    }
    echo '</div>';
}

echo '</div></div>';

?>