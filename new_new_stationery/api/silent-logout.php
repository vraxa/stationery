<?php
require_once dirname(__DIR__) . '/includes/auth.php';
sessionStart();
session_destroy();
header('Content-Type: application/json');
echo json_encode(['success' => true]);
