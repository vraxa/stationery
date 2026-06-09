<?php
// Microsoft Graph API helpers (uses cURL)

function graphRequest(string $method, string $endpoint, string $token, ?array $body = null): array {
    $url  = 'https://graph.microsoft.com/v1.0' . $endpoint;
    $curl = curl_init($url);

    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ];

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    }

    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $response = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

    return [
        'status' => $httpCode,
        'body'   => ($response !== false && $response !== '') ? (json_decode($response, true) ?? []) : [],
    ];
}

function tokenRequest(string $tenantId, array $params): array {
    $url  = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($curl);

    return ($response !== false && $response !== '') ? (json_decode($response, true) ?? []) : [];
}

function graphGetUser(string $token): array {
    $result = graphRequest('GET', '/me?$select=displayName,mail,userPrincipalName,jobTitle,department', $token);
    if ($result['status'] !== 200) {
        return [];
    }
    $data  = $result['body'];
    $email = $data['mail'] ?? $data['userPrincipalName'] ?? '';
    return [
        'name'       => $data['displayName'] ?? '',
        'email'      => $email,
        'job_title'  => $data['jobTitle']    ?? '',
        'department' => $data['department']  ?? '',
    ];
}

function graphSendMail(string $token, string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $payload = [
        'message' => [
            'subject' => $subject,
            'body'    => [
                'contentType' => 'HTML',
                'content'     => $htmlBody,
            ],
            'toRecipients' => [
                [
                    'emailAddress' => [
                        'address' => $toEmail,
                        'name'    => $toName,
                    ],
                ],
            ],
        ],
        'saveToSentItems' => true,
    ];
    $result = graphRequest('POST', '/me/sendMail', $token, $payload);
    return $result['status'] === 202;
}
