<?php
// Define a constant to indicate this is an API call, preventing the util.php
// from outputting general HTML on DB failure.
define('IS_API_CALL', true);

// Suppress error output to ensure clean JSON response
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Configuration and database connection (requires config.php in the parent directory)
// NOTE: Assuming this file is placed in an 'api' subdirectory.
require_once '../config.php';
require_once '../util.php'; // Required for database connection via config

// Set the content type header to JSON for API response
header('Content-Type: application/json');

// Allow only POST requests (security check)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Check if $pdo connection was successful (from config.php)
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database initialization failed.']);
    exit;
}

// Start session if available so we can associate bookings with logged-in users
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}


// Get the JSON data sent from the JavaScript fetch request
// Read and decode incoming JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
    exit;
}

// --- 1. Basic Data Validation ---
// Required top-level fields
if (empty($data['clientName']) || empty($data['clientPhone']) || empty($data['booking']) || !is_array($data['booking'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required client or booking information.']);
    exit;
}

// Required booking sub-fields
$booking = $data['booking'];
if (empty($booking['service']) || !is_array($booking['service']) || empty($booking['service']['name']) 
    || empty($booking['date']) || empty($booking['time']) || empty($booking['duration_minutes']) 
    || !isset($data['booking']['total_price'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required booking data (service with name, date, time, duration, total_price).']);
    exit;
}

// Extract and sanitize key variables (with sane defaults for optional fields)
$clientName       = filter_var($data['clientName'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$clientPhone      = filter_var($data['clientPhone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$appointment_date = $booking['date'];
$start_time_raw   = $booking['time'];
$duration_mins    = (int)$booking['duration_minutes'];

// If user is logged in, prefer session values for linking
$userId = null;
$clientEmail = null;
if (!empty($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    // session may contain email (set at login)
    if (!empty($_SESSION['user_email'])) {
        $clientEmail = $_SESSION['user_email'];
    }
}
// Allow frontend to pass an explicit clientEmail in payload if session not present
if (empty($clientEmail) && !empty($data['clientEmail'])) {
    $clientEmail = filter_var($data['clientEmail'], FILTER_VALIDATE_EMAIL) ? $data['clientEmail'] : null;
}

// --- 2. Availability Check Logic ---

// Normalize start and end times to H:i:s format
$start_ts = strtotime("$appointment_date $start_time_raw");
if ($start_ts === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date/time format.']);
    exit;
}
$start_time = date('H:i:s', $start_ts);
$end_ts = $start_ts + ($duration_mins * 60);
$end_time = date('H:i:s', $end_ts);

/*
 * SQL query to check for time overlaps:
 * A conflict occurs if any existing booking on the same date overlaps with the new one.
 * The booking duration is calculated on the existing record by ADDTIME(booking_time, SEC_TO_TIME(duration_minutes * 60)).
 */
$sql_check = "
SELECT id FROM bookings
WHERE booking_date = :date
AND NOT (
    ADDTIME(booking_time, SEC_TO_TIME(duration_minutes * 60)) <= :start_time
    OR booking_time >= :end_time
)
LIMIT 1";

$stmt = $pdo->prepare($sql_check);
$stmt->execute([
    'date' => $appointment_date,
    'start_time' => $start_time,
    'end_time' => $end_time
]);

if ($stmt->fetch()) {
    // Conflict detected
    http_response_code(409); // HTTP Conflict status code
    echo json_encode(['success' => false, 'message' => 'The selected time slot is already booked. Please choose another time.']);
    exit;
}


// --- 3. Resolve service_id (using service name as a proxy, as client-side uses name) and Save Booking to Database ---
try {
    // Attempt to find service_id by the name provided in the client-side selection
    $serviceId = null;
    $label = $booking['service']['name'];
    
    // Find service ID based on the service label (name)
    $q = $pdo->prepare('SELECT service_id FROM services WHERE label = :label LIMIT 1');
    $q->execute(['label' => $label]);
    $found = $q->fetch();
    
    if ($found) {
        $serviceId = (int)$found['service_id'];
    } else {
        // Fallback: If service name not found in DB, log error and return fail
        error_log("Booking failed: Service '{$label}' not found in 'services' table.");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Service not recognized. Booking failed.']);
        exit;
    }

    $extras = !empty($booking['extras']) ? $booking['extras'] : [];
    $home = !empty($booking['home']) ? 1 : 0;
    $persons = !empty($booking['persons']) ? (int)$booking['persons'] : 1;
    $notes = isset($booking['notes']) ? $booking['notes'] : '';

    // Insert booking and attach user_id/client_email when available
    $sql_insert = "INSERT INTO bookings (client_name, client_email, user_id, client_phone, service_id, total_price, booking_date, booking_time, duration_minutes, extras_list, is_home_service, persons, notes)
                   VALUES (:clientName, :clientEmail, :userId, :clientPhone, :serviceId, :totalPrice, :date, :time, :duration, :extras, :home, :persons, :notes)";

    $stmt = $pdo->prepare($sql_insert);
    $stmt->execute([
        'clientName'    => $clientName,
        'clientEmail'   => $clientEmail,
        'userId'        => $userId,
        'clientPhone'   => $clientPhone,
        'serviceId'     => $serviceId,
        'totalPrice'    => $data['booking']['total_price'],
        'date'          => $appointment_date,
        'time'          => $start_time,
        'duration'      => $duration_mins,
        'extras'        => json_encode($extras), // Store extras as JSON string
        'home'          => $home,
        'persons'       => $persons,
        'notes'         => $notes
    ]);

    $booking_id = $pdo->lastInsertId();

} catch (\PDOException $e) {
    http_response_code(500);
    error_log("Database Insert Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save booking to database.']);
    exit;
}

// --- 4. Send Email Notification to Lash Tech ---
// Retrieve lash tech email from config
$lash_tech_email = $lash_tech_email ?? 'admin@localhost.com';

$extras_summary = '';
if (!empty($data['booking']['extras'])) {
    $extras_summary = "Extras: " . implode(', ', array_map(function($e) { return $e['name'] . ' (₵' . $e['price'] . ')'; }, $data['booking']['extras'])) . "\n";
}

$is_home_text = $data['booking']['home'] ? 'YES (Address: ' . ($data['booking']['location'] ?? 'Not provided') . ' | Travel fee pending confirmation)' : 'NO (Studio Appointment)';

$email_subject = "NEW Lash Nouveau Appointment Booked (ID: {$booking_id})";
$email_body = "A new appointment has been successfully booked.\n\n"
            . "--- Client Details ---\n"
            . "Name: {$clientName}\n"
            . "Phone: {$clientPhone}\n\n"
            . "--- Appointment Details ---\n"
            . "Service: {$data['booking']['service']['name']} (₵{$data['booking']['service']['price']})\n"
            . $extras_summary
            . "Persons: {$data['booking']['persons']}\n"
            . "Home Service: {$is_home_text}\n"
            . "Date: {$appointment_date}\n"
            . "Time: {$start_time}\n"
            . "Duration: {$duration_mins} minutes\n"
            . "ESTIMATED TOTAL: ₵{$data['booking']['total_price']}\n\n"
            . "Notes:\n{$booking['notes']}\n\n"
            . "Provider: {$booking['provider']}\n\n"
            . "Please contact the client to confirm the booking.";

$headers = "From: bookings@lash-nouveau.com\r\n" .
           "Reply-To: {$clientName} <noreply@lash-nouveau.com>\r\n" .
           "X-Mailer: PHP/" . phpversion();

// Attempt to send the email notification (suppress warnings if mail server not configured)
@mail($lash_tech_email, $email_subject, $email_body, $headers);


// --- 5. Success Response ---
echo json_encode(['success' => true, 'message' => 'Appointment successfully submitted and email notification sent.']);

?>