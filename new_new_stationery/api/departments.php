<?php
require_once dirname(__DIR__) . '/includes/auth.php';
header('Content-Type: application/json');

$method   = $_SERVER['REQUEST_METHOD'];
$dataFile = dirname(__DIR__) . '/data/departments.json';

function readDepts(string $file): array {
    $json = file_get_contents($file);
    return json_decode($json, true) ?? [];
}

function writeDepts(string $file, array $depts): void {
    $fp = fopen($file, 'c+');
    if (!$fp) throw new RuntimeException('Cannot open data file.');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(array_values($depts), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
}

function nextDeptId(array $depts): string {
    $max = 0;
    foreach ($depts as $d) {
        $id = (int) $d['id'];
        if ($id > $max) $max = $id;
    }
    return (string) ($max + 1);
}

function sanitiseDept(array $input): array {
    return [
        'name'      => trim(substr($input['name']      ?? '', 0, 200)),
        'head_name' => trim(substr($input['head_name'] ?? '', 0, 200)),
        'email'     => filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL),
    ];
}

switch ($method) {
    case 'GET':
        requireLoginApi();
        echo json_encode(readDepts($dataFile));
        break;

    case 'POST':
        requireAdminApi();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $dept  = sanitiseDept($input);
        if ($dept['name'] === '' || !filter_var($dept['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['error' => 'Name and a valid email are required.']);
            exit;
        }
        $depts      = readDepts($dataFile);
        $dept['id'] = nextDeptId($depts);
        $depts[]    = $dept;
        writeDepts($dataFile, $depts);
        http_response_code(201);
        echo json_encode($dept);
        break;

    case 'PUT':
        requireAdminApi();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = trim($input['id'] ?? '');
        if ($id === '') {
            http_response_code(422); echo json_encode(['error' => 'ID required.']); exit;
        }
        $depts = readDepts($dataFile);
        $found = false;
        foreach ($depts as &$dept) {
            if ($dept['id'] === $id) {
                $updated = sanitiseDept($input);
                if ($updated['name'] === '' || !filter_var($updated['email'], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(422); echo json_encode(['error' => 'Name and valid email required.']); exit;
                }
                $dept  = array_merge($dept, $updated);
                $found = true;
                break;
            }
        }
        unset($dept);
        if (!$found) { http_response_code(404); echo json_encode(['error' => 'Department not found.']); exit; }
        writeDepts($dataFile, $depts);
        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        requireAdminApi();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = trim($input['id'] ?? '');
        if ($id === '') {
            http_response_code(422); echo json_encode(['error' => 'ID required.']); exit;
        }
        $depts    = readDepts($dataFile);
        $filtered = array_filter($depts, fn($d) => $d['id'] !== $id);
        if (count($filtered) === count($depts)) {
            http_response_code(404); echo json_encode(['error' => 'Department not found.']); exit;
        }
        writeDepts($dataFile, array_values($filtered));
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed.']);
}
