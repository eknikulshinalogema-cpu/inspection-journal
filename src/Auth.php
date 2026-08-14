<?php

namespace App;

class Auth
{
    private static ?array $currentUser = null;

    private static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public static function bearerToken(): ?string
    {
        $auth = self::header('X-Vibe-Authorization');
        if (!$auth) {
            return null;
        }
        return preg_replace('/^Bearer\s+/i', '', $auth);
    }

    /** Быстрая идентификация без обращения к API — прямо из заголовков Gateway. */
    public static function currentUser(): array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $rawId = self::header('X-Vibe-User-Id');
        $nameEncoded = self::header('X-Vibe-User-Name-Encoded');
        $role = self::header('X-Vibe-User-Role');

        self::$currentUser = [
            'id' => ($rawId !== null && ctype_digit($rawId)) ? (int) $rawId : null,
            'rawId' => $rawId,
            'name' => $nameEncoded ? urldecode($nameEncoded) : null,
            'role' => $role,
        ];

        return self::$currentUser;
    }

    public static function isAdmin(): bool
    {
        return self::currentUser()['role'] === 'ADMIN';
    }

    private static function apiKey(): string
    {
        return getenv('VIBE_APP_KEY') ?: '';
    }

    /** Запрос к прокси-API Вайбкод от имени текущего пользователя. */
    private static function apiCall(string $method, string $path, array $query = []): array
    {
        $url = 'https://vibecode.bitrix24.tech' . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = ['X-Api-Key: ' . self::apiKey()];
        $bearer = self::bearerToken();
        if ($bearer) {
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException('Ошибка обращения к Вайбкод API: ' . curl_error($ch));
        }
        curl_close($ch);

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || ($decoded['success'] ?? false) !== true) {
            $message = $decoded['error']['message'] ?? 'Неизвестная ошибка Вайбкод API';
            throw new \RuntimeException($message);
        }

        return $decoded;
    }

    /** Список пользователей портала для выпадающих списков. */
    public static function listUsers(): array
    {
        $result = self::apiCall('GET', '/v1/users', ['limit' => 200]);
        $users = $result['data'] ?? [];

        $out = [];
        foreach ($users as $u) {
            $id = (int) ($u['id'] ?? 0);
            if (!$id) {
                continue;
            }
            $name = trim(($u['name'] ?? '') . ' ' . ($u['lastName'] ?? '')) ?: ('User #' . $id);
            $out[] = ['id' => $id, 'name' => $name];
        }

        return $out;
    }

    /** Список отделов портала (для раздела прав доступа в /admin). */
    public static function listGroups(): array
    {
        try {
            $result = self::apiCall('GET', '/v1/departments', ['limit' => 200]);
        } catch (\Throwable $e) {
            return [];
        }
        $groups = $result['data'] ?? [];

        $out = [];
        foreach ($groups as $g) {
            $id = (int) ($g['id'] ?? 0);
            if (!$id) {
                continue;
            }
            $out[] = ['id' => $id, 'name' => $g['name'] ?? ('Group #' . $id)];
        }

        return $out;
    }
}
