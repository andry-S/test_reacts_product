<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('DATA_FILE', 'produk.json');

$method = $_SERVER['REQUEST_METHOD'];


$pathInfo = '';
if (isset($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/');
} else {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptName = $_SERVER['SCRIPT_NAME'];
    if (strpos($requestUri, $scriptName) === 0) {
        $pathInfo = trim(substr($requestUri, strlen($scriptName)), '/');
    } else {
        $pathInfo = trim($requestUri, '/');
    }
}

$path = $pathInfo === '' ? [] : explode('/', $pathInfo);

function loadData() {
    if (!file_exists(DATA_FILE)) {
        file_put_contents(DATA_FILE, json_encode([]));
    }
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?? [];
}

function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

function getInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid JSON input']);
        exit;
    }
    return $data;
}

function findProductById($products, $id) {
    foreach ($products as $index => $p) {
        if ($p['id'] == $id) return [$index, $p];
    }
    return [null, null];
}

$products = loadData();

// Routing
if ($method === 'GET' && count($path) === 1 && $path[0] === 'products') {
    echo json_encode($products);
    exit;
}

if ($method === 'GET' && count($path) === 2 && $path[0] === 'products') {
    $id = intval($path[1]);
    list($idx, $product) = findProductById($products, $id);
    if ($product === null) {
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
        exit;
    }
    echo json_encode($product);
    exit;
}

if ($method === 'POST' && count($path) === 1 && $path[0] === 'products') {
    $input = getInput();
    if (!isset($input['name'], $input['price'], $input['stock']) ||
        !is_string($input['name']) ||
        !is_numeric($input['price']) ||
        !is_numeric($input['stock'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid input']);
        exit;
    }

    $maxId = 0;
    foreach ($products as $p) {
        if ($p['id'] > $maxId) $maxId = $p['id'];
    }

    $newProduct = [
        'id' => $maxId + 1,
        'name' => $input['name'],
        'price' => floatval($input['price']),
        'stock' => intval($input['stock'])
    ];

    $products[] = $newProduct;
    saveData($products);
    http_response_code(201);
    echo json_encode($newProduct);
    exit;
}

if ($method === 'PUT' && count($path) === 2 && $path[0] === 'products') {
    $id = intval($path[1]);
    list($idx, $product) = findProductById($products, $id);
    if ($product === null) {
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
        exit;
    }

    $input = getInput();
    if (!isset($input['name'], $input['price'], $input['stock']) ||
        !is_string($input['name']) ||
        !is_numeric($input['price']) ||
        !is_numeric($input['stock'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid input']);
        exit;
    }

    $products[$idx]['name'] = $input['name'];
    $products[$idx]['price'] = floatval($input['price']);
    $products[$idx]['stock'] = intval($input['stock']);

    saveData($products);
    echo json_encode($products[$idx]);
    exit;
}

if ($method === 'DELETE' && count($path) === 2 && $path[0] === 'products') {
    $id = intval($path[1]);
    list($idx, $product) = findProductById($products, $id);
    if ($product === null) {
        http_response_code(404);
        echo json_encode(['message' => 'Product not found']);
        exit;
    }
    array_splice($products, $idx, 1);
    saveData($products);
    http_response_code(204);
    exit;
}

http_response_code(404);
echo json_encode(['message' => 'Route not found']);
exit;
