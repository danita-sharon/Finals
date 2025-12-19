<?php
/**
 * Session API for managing booking session data
 * Stores selected services, extras, and booking details in a temporary sessions table
 */

define('IS_API_CALL', true);
require_once '../config.php';

header('Content-Type: application/json');

// Only allow POST and GET
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Check if $pdo connection was successful
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database initialization failed.']);
    exit;
}

// Create sessions table if it doesn't exist
try {
    // Check if table exists first
    $result = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'booking_sessions'");
    
    if ($result->rowCount() == 0) {
        // Table doesn't exist, create it
        $pdo->exec("
            CREATE TABLE booking_sessions (
                session_id VARCHAR(100) PRIMARY KEY,
                selected_service LONGTEXT NULL,
                selected_extras LONGTEXT NULL,
                additional_persons INT DEFAULT 1,
                home_service TINYINT(1) DEFAULT 0,
                booking_date DATE NULL,
                booking_time TIME NULL,
                booking_notes LONGTEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
} catch (PDOException $e) {
    error_log('Session table error: ' . $e->getMessage());
    // Don't exit - table might exist, continue with operation
}

// Generate a unique session ID (use IP address + timestamp for uniqueness)
function getSessionId() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // Use a combination of IP and user agent for better uniqueness
    $sessionId = md5($ip . $_SERVER['HTTP_USER_AGENT'] ?? '');
    return substr($sessionId, 0, 100);
}

$sessionId = getSessionId();
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST actions
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        switch ($action) {
            case 'set_service':
                // Set the selected service
                if (empty($data['service']) || empty($data['service']['name'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Missing service data.']);
                    exit;
                }

                try {
                    $service = json_encode($data['service']);
                    
                    $sql = "INSERT INTO booking_sessions (session_id, selected_service, updated_at)
                            VALUES (:sid, :svc, NOW())
                            ON DUPLICATE KEY UPDATE selected_service = VALUES(selected_service), updated_at = NOW()";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':sid' => $sessionId,
                        ':svc' => $service
                    ]);

                    echo json_encode(['success' => true, 'message' => 'Service saved.', 'sessionId' => $sessionId]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    error_log('Set service error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            case 'toggle_extra':
                // Add or remove an extra
                if (empty($data['extra']) || empty($data['extra']['name'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Missing extra data.']);
                    exit;
                }

                try {
                    // Get current extras
                    $stmt = $pdo->prepare("SELECT selected_extras FROM booking_sessions WHERE session_id = :sessionId");
                    $stmt->execute(['sessionId' => $sessionId]);
                    $row = $stmt->fetch();

                    $extras = !empty($row['selected_extras']) ? json_decode($row['selected_extras'], true) : [];
                    if (!is_array($extras)) $extras = [];

                    $extraName = $data['extra']['name'];
                    $existingIndex = array_search($extraName, array_column($extras, 'name'));

                    if ($existingIndex !== false) {
                        // Remove extra
                        array_splice($extras, $existingIndex, 1);
                        $action_result = 'removed';
                    } else {
                        // Add extra
                        $extras[] = $data['extra'];
                        $action_result = 'added';
                    }

                    $extrasJson = json_encode($extras);
                    
                    // Use simple INSERT...ON DUPLICATE without reusing parameters
                    $sql = "INSERT INTO booking_sessions (session_id, selected_extras, updated_at)
                            VALUES (:sid, :exts, NOW())
                            ON DUPLICATE KEY UPDATE selected_extras = VALUES(selected_extras), updated_at = NOW()";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':sid' => $sessionId,
                        ':exts' => $extrasJson
                    ]);

                    echo json_encode([
                        'success' => true,
                        'message' => "Extra {$action_result}.",
                        'sessionId' => $sessionId,
                        'action' => $action_result
                    ]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    error_log('Toggle extra error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            case 'set_booking_details':
                // Set date, time, notes, persons, home service
                try {
                    // First ensure the session record exists
                    $stmt = $pdo->prepare("INSERT IGNORE INTO booking_sessions (session_id, created_at, updated_at) VALUES (:sid, NOW(), NOW())");
                    $stmt->execute([':sid' => $sessionId]);
                    
                    // Build dynamic UPDATE statement
                    $updateParts = [];
                    $params = [':sid' => $sessionId];
                    $paramCounter = 0;

                    if (isset($data['persons'])) {
                        $paramCounter++;
                        $key = ':p' . $paramCounter;
                        $updateParts[] = "additional_persons = $key";
                        $params[$key] = (int)$data['persons'];
                    }
                    if (isset($data['home_service'])) {
                        $paramCounter++;
                        $key = ':p' . $paramCounter;
                        $updateParts[] = "home_service = $key";
                        $params[$key] = (bool)$data['home_service'];
                    }
                    if (isset($data['date'])) {
                        $paramCounter++;
                        $key = ':p' . $paramCounter;
                        $updateParts[] = "booking_date = $key";
                        $params[$key] = $data['date'];
                    }
                    if (isset($data['time'])) {
                        $paramCounter++;
                        $key = ':p' . $paramCounter;
                        $updateParts[] = "booking_time = $key";
                        $params[$key] = $data['time'];
                    }
                    if (isset($data['notes'])) {
                        $paramCounter++;
                        $key = ':p' . $paramCounter;
                        $updateParts[] = "booking_notes = $key";
                        $params[$key] = $data['notes'];
                    }

                    if (!empty($updateParts)) {
                        $updateParts[] = "updated_at = NOW()";
                        $sql = "UPDATE booking_sessions SET " . implode(', ', $updateParts) . " WHERE session_id = :sid";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                    }

                    echo json_encode(['success' => true, 'message' => 'Booking details saved.', 'sessionId' => $sessionId]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    error_log('Set booking details error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            case 'clear_session':
                // Clear the entire session
                try {
                    $stmt = $pdo->prepare("DELETE FROM booking_sessions WHERE session_id = :sessionId");
                    $stmt->execute(['sessionId' => $sessionId]);
                    echo json_encode(['success' => true, 'message' => 'Session cleared.']);
                } catch (PDOException $e) {
                    http_response_code(500);
                    error_log('Clear session error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }

    } else {
        // Handle GET actions - retrieve session data
        $action = $_GET['action'] ?? null;

        switch ($action) {
            case 'get_all':
                try {
                    $stmt = $pdo->prepare("
                        SELECT selected_service, selected_extras, additional_persons, home_service, 
                               booking_date, booking_time, booking_notes
                        FROM booking_sessions 
                        WHERE session_id = :sessionId
                    ");
                    $stmt->execute(['sessionId' => $sessionId]);
                    $row = $stmt->fetch();

                    if (!$row) {
                        echo json_encode([
                            'success' => true,
                            'sessionId' => $sessionId,
                            'service' => null,
                            'extras' => [],
                            'persons' => 1,
                            'home_service' => false,
                            'date' => null,
                            'time' => null,
                            'notes' => ''
                        ]);
                    } else {
                        echo json_encode([
                            'success' => true,
                            'sessionId' => $sessionId,
                            'service' => json_decode($row['selected_service'], true),
                            'extras' => json_decode($row['selected_extras'], true) ?? [],
                            'persons' => (int)$row['additional_persons'],
                            'home_service' => (bool)$row['home_service'],
                            'date' => $row['booking_date'],
                            'time' => $row['booking_time'],
                            'notes' => $row['booking_notes'] ?? ''
                        ]);
                    }
                } catch (PDOException $e) {
                    http_response_code(500);
                    error_log('Get all error: ' . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Session API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
