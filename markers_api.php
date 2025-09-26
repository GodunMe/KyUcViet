<?php
// markers_api.php - API để quản lý markers (CRUD)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        getMarkers();
        break;
    case 'POST':
        addMarker($input);
        break;
    case 'PUT':
        updateMarker($input);
        break;
    case 'DELETE':
        deleteMarker($input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getMarkers() {
    global $conn;
    
    $sql = "SELECT * FROM markers ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    $markers = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $markers[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'description' => $row['description'],
                'lat' => floatval($row['latitude']),
                'lng' => floatval($row['longitude']),
                'address' => $row['address'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    echo json_encode($markers);
}

function addMarker($data) {
    global $conn;
    
    if (!isset($data['name']) || !isset($data['lat']) || !isset($data['lng'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $name = $conn->real_escape_string($data['name']);
    $type = $conn->real_escape_string($data['type'] ?? 'other');
    $description = $conn->real_escape_string($data['description'] ?? '');
    $lat = floatval($data['lat']);
    $lng = floatval($data['lng']);
    $address = $conn->real_escape_string($data['address'] ?? '');
    
    $sql = "INSERT INTO markers (name, type, description, latitude, longitude, address, created_at) 
            VALUES ('$name', '$type', '$description', $lat, $lng, '$address', NOW())";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode([
            'success' => true, 
            'id' => $conn->insert_id,
            'message' => 'Marker added successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

function updateMarker($data) {
    global $conn;
    
    if (!isset($data['id']) || !isset($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $id = intval($data['id']);
    $name = $conn->real_escape_string($data['name']);
    $type = $conn->real_escape_string($data['type'] ?? 'other');
    $description = $conn->real_escape_string($data['description'] ?? '');
    $address = $conn->real_escape_string($data['address'] ?? '');
    
    $sql = "UPDATE markers SET 
            name = '$name', 
            type = '$type', 
            description = '$description', 
            address = '$address',
            updated_at = NOW()
            WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Marker updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

function deleteMarker($data) {
    global $conn;
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing marker ID']);
        return;
    }
    
    $id = intval($data['id']);
    
    $sql = "DELETE FROM markers WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => 'Marker deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

$conn->close();
?>