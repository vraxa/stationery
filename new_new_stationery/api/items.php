<?php
require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');

$method    = $_SERVER['REQUEST_METHOD'];
$dataFile  = dirname(__DIR__) . '/data/items.json';

function readItems(string $file): array {
    $json = file_get_contents($file);
    return json_decode($json, true) ?? [];
}

function writeItems(string $file, array $items): void {
    $fp = fopen($file, 'c+');
    if (!$fp) throw new RuntimeException('Cannot open data file.');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
}

function nextId(array $items): string {
    $max = 0;
    foreach ($items as $item) {
        $id = (int) $item['id'];
        if ($id > $max) $max = $id;
    }
    return (string) ($max + 1);
}

function sanitiseItem(array $input): array {
    return [
        'sku'         => trim(substr($input['sku']         ?? '', 0, 50)),
        'name'        => trim(substr($input['name']        ?? '', 0, 200)),
        'description' => trim(substr($input['description'] ?? '', 0, 500)),
        'price'       => round(max(0, (float) ($input['price'] ?? 0)), 2),
        'unit'        => trim(substr($input['unit']        ?? '', 0, 50)),
        'category'    => trim(substr($input['category']    ?? '', 0, 100)),
    ];
}

switch ($method) {
    case 'GET':
        requireLoginApi();
        echo json_encode(readItems($dataFile));
        break;

    case 'POST':
        requireAdminApi();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $item  = sanitiseItem($input);
        if ($item['name'] === '' || $item['price'] < 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Name and a valid price are required.']);
            exit;
        }
        $items      = readItems($dataFile);
        $item['id'] = nextId($items);
        $items[]    = $item;
        writeItems($dataFile, $items);
        http_response_code(201);
        echo json_encode($item);
        break;

    case 'PUT':
        requireAdminApi();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = trim($input['id'] ?? '');
        if ($id === '') {
            http_response_code(422); echo json_encode(['error' => 'ID required.']); exit;
        }
        $items = readItems($dataFile);
        $found = false;
        foreach ($items as &$item) {
            if ($item['id'] === $id) {
                $updated = sanitiseItem($input);
                if ($updated['name'] === '') {
                    http_response_code(422); echo json_encode(['error' => 'Name required.']); exit;
                }
                $item  = array_merge($item, $updated);
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) { http_response_code(404); echo json_encode(['error' => 'Item not found.']); exit; }
        writeItems($dataFile, $items);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        requireAdminApi();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = trim($input['id'] ?? '');
        if ($id === '') {
            http_response_code(422); echo json_encode(['error' => 'ID required.']); exit;
        }
        $items    = readItems($dataFile);
        $filtered = array_filter($items, fn($i) => $i['id'] !== $id);
        if (count($filtered) === count($items)) {
            http_response_code(404); echo json_encode(['error' => 'Item not found.']); exit;
        }
        writeItems($dataFile, array_values($filtered));
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed.']);
}
