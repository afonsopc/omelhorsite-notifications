<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require 'auth.php';
require 'database.php';
require 'environment.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = getAuthToken();

    if ($token === null) {
        http_response_code(401);
        exit();
    }

    $user_id = getUserId($token);

    if ($user_id === null) {
        http_response_code(404);
        exit();
    }

    $page = $_GET['page'] ?? null;

    if ($page === null) {
        $count = getUsersUnreadNotificationsCount($user_id);

        if ($count === null) {
            http_response_code(500);
            exit();
        }

        echo json_encode(['count' => $count]);
        exit();
    }

    $page = intval($page) ?? null;

    if ($page === null || $page < 0) {
        http_response_code(400);
        exit();
    }

    $notifications = getUsersNotifications($user_id, $page);

    echo json_encode($notifications);
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = getAuthToken();

    if ($token === null) {
        http_response_code(401);
        exit();
    }

    $requestBody = json_decode(file_get_contents('php://input'), true);

    if (
        !isset($requestBody) ||
        !isset($requestBody['user_id']) ||
        !isset($requestBody['type']) ||
        !isset($requestBody['context']) ||
        strtolower(gettype($requestBody['user_id'])) !== 'string' ||
        strtolower(gettype($requestBody['type'])) !== 'string' ||
        strtolower(gettype($requestBody['context'])) !== 'array' ||
        strlen($requestBody['user_id']) < 1 ||
        strlen($requestBody['type']) < 1 ||
        strlen(json_encode($requestBody['context'])) < 1 ||
        strlen($requestBody['user_id']) > 255 ||
        strlen($requestBody['type']) > 255 ||
        strlen(json_encode($requestBody['context'])) > 2500
    ) {
        http_response_code(400);
        exit();
    }

    $user_id = $requestBody['user_id'];
    $type = $requestBody['type'];
    $context = $requestBody['context'];

    $isAdmin = isAdmin($token);

    if ($isAdmin === false) {
        http_response_code(401);
        exit();
    }

    $create_notification = createNotification($user_id, $type, $context);

    if ($create_notification === null) {
        http_response_code(500);
        exit();
    }

    http_response_code(201);
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $token = getAuthToken();

    if ($token === null) {
        http_response_code(401);
        exit();
    }

    $user_id = getUserId($token);

    if ($user_id === null) {
        http_response_code(404);
        exit();
    }

    $readAllNotifications = readAllNotifications($user_id);

    if ($readAllNotifications === null) {
        http_response_code(500);
        exit();
    }

    http_response_code(200);
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
} else {
    http_response_code(404);
    exit();
}
