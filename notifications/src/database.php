<?php

function getDatabaseConnection()
{
    $db_host = getEnvironmentVariable('DB_HOST');
    $db_port = getEnvironmentVariable('DB_PORT');
    $db_database = getEnvironmentVariable('DB_DATABASE');
    $db_user = getEnvironmentVariable('DB_USER');
    $db_password = getEnvironmentVariable('DB_PASSWORD');

    if (
        !isset($db_host) ||
        !isset($db_port) ||
        !isset($db_database) ||
        !isset($db_user) ||
        !isset($db_password)
    ) {
        return null;
    }

    $connection = new mysqli($db_host, $db_user, $db_password, $db_database, $db_port);

    if ($connection->connect_error) {
        return null;
    }

    return $connection;
}

function getUsersNotifications(string $user_id, int $page = 0)
{
    $PAGE_SIZE = 10;

    $db = getDatabaseConnection();

    if ($db === null) {
        return null;
    }

    $limit = $PAGE_SIZE;
    $offset = $page * $PAGE_SIZE;

    $stmt = $db->prepare('SELECT `id`, `type`, `context`, `created_at`, `read` FROM `notifications` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT ? OFFSET ?');
    $stmt->bind_param('sii', $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    $ids = array_column($notifications, 'id');

    if (!empty($ids)) {
        $id_placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $db->prepare("UPDATE `notifications` SET `read` = TRUE WHERE `id` IN ($id_placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
    }

    $stmt->close();
    $db->close();

    foreach ($notifications as &$notification) {
        $notification['context'] = json_decode($notification['context'], true);
        $notification['read'] = (bool) $notification['read'];
    }

    return $notifications;
}

function getUsersUnreadNotificationsCount(string $user_id)
{
    $db = getDatabaseConnection();

    if ($db === null) {
        return null;
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `read` = FALSE');
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];

    $stmt->close();
    $db->close();

    return $count;
}

function createNotification(string $user_id, string $type, array $context)
{
    $context_json_string = json_encode($context);

    $db = getDatabaseConnection();

    if ($db === null) {
        return null;
    }

    $stmt = $db->prepare('INSERT INTO `notifications` (`user_id`, `type`, `context`) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $user_id, $type, $context_json_string);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        return null;
    }

    $stmt->close();
    $db->close();

    return true;
}

function readAllNotifications(string $user_id)
{
    $db = getDatabaseConnection();

    if ($db === null) {
        return null;
    }

    $stmt = $db->prepare('UPDATE `notifications` SET `read` = TRUE WHERE `user_id` = ? AND `read` = FALSE');
    $stmt->bind_param('s', $user_id);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        return null;
    }

    $stmt->close();
    $db->close();

    return true;
}
