<?php

function getUserId($token)
{
    if (gettype($token) !== 'string') {
        return null;
    }
    if (strlen($token) < 1) {
        return null;
    }

    $accounts_service_url = getEnvironmentVariable('ACCOUNTS_SERVICE_URL');

    if (!isset($accounts_service_url)) {
        return null;
    }

    $url = "$accounts_service_url/account?info_to_get[id]=true";
    $options = array(
        'http' => array(
            'header' => "Authorization: Bearer $token\r\n",
            'method' => 'GET',
        )
    );

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $result = json_decode($result, true);

    if (isset($result['id'])) {
        return $result['id'];
    }

    return null;
}

function isAdmin($token)
{
    if (gettype($token) !== 'string') {
        return false;
    }

    if (strlen($token) < 1) {
        return false;
    }

    $notificationsApiKey = getEnvironmentVariable('NOTIFICATIONS_API_KEY');

    if (!isset($notificationsApiKey)) {
        return null;
    }

    if ($notificationsApiKey === $token) {
        return true;
    }

    $accounts_service_url = getEnvironmentVariable('ACCOUNTS_SERVICE_URL');

    if (!isset($accounts_service_url)) {
        return null;
    }

    $url = "$accounts_service_url/admin";
    $options = array(
        'http' => array(
            'header' => "Authorization: Bearer $token\r\n",
            'method' => 'GET',
        )
    );

    $context = stream_context_create($options);

    $result = @file_get_contents($url, false, $context);

    if ($result !== false) {
        // The request was successful, check the status code
        $status_line = $http_response_header[0];
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $status = $match[1];

        if ($status === '200') {
            return true;
        }
    }

    return false;
}

function getAuthToken()
{
    $headers = getallheaders();

    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        $authHeaderParts = explode(' ', $authHeader);

        if (count($authHeaderParts) === 2 && $authHeaderParts[0] === 'Bearer') {
            return $authHeaderParts[1];
        }
    }

    return null;
}
