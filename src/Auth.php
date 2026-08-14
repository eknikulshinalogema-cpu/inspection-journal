<?php

namespace App;

use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\AuthToken;
use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\CoreBuilder;

/**
 * Thin wrapper around bitrix24/bitrix24-php-sdk for the calls this app needs:
 * current user info, admin check, and user/group lists for the dropdowns.
 *
 * Vibecode injects the portal auth context (access token, refresh token,
 * client endpoint, member id) into the session/query string on each request
 * for embedded apps. We read whichever the runtime provides and hand it to
 * the SDK.
 */
class Auth
{
    private static ?Core $core = null;
    private static ?array $currentUser = null;

    public static function core(): Core
    {
        if (self::$core !== null) {
            return self::$core;
        }

        session_start();

        $accessToken = $_REQUEST['AUTH_ID'] ?? $_SESSION['B24_ACCESS_TOKEN'] ?? getenv('B24_ACCESS_TOKEN') ?: null;
        $refreshToken = $_REQUEST['REFRESH_ID'] ?? $_SESSION['B24_REFRESH_TOKEN'] ?? getenv('B24_REFRESH_TOKEN') ?: null;
        $domain = $_REQUEST['DOMAIN'] ?? $_SESSION['B24_DOMAIN'] ?? getenv('B24_DOMAIN') ?: null;
        $clientId = getenv('B24_CLIENT_ID') ?: '';
        $clientSecret = getenv('B24_CLIENT_SECRET') ?: '';

        if ($accessToken) {
            $_SESSION['B24_ACCESS_TOKEN'] = $accessToken;
        }
        if ($refreshToken) {
            $_SESSION['B24_REFRESH_TOKEN'] = $refreshToken;
        }
        if ($domain) {
            $_SESSION['B24_DOMAIN'] = $domain;
        }

        if (!$accessToken || !$domain) {
            throw new \RuntimeException(
                'Не удалось получить контекст авторизации Битрикс24. Приложение должно открываться внутри портала.'
            );
        }

        $authToken = new AuthToken($accessToken, $refreshToken ?? '', 3600);
        $credentials = new Credentials(
            'https://' . $domain . '/rest/',
            $authToken,
            new ApplicationProfile($clientId, $clientSecret),
            null
        );

        self::$core = (new CoreBuilder())->withCredentials($credentials)->build();

        return self::$core;
    }

    /** Current user profile via user.current, cached per request. */
    public static function currentUser(): array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $response = self::core()->call('user.current', []);
        self::$currentUser = $response->getResponseData()->getResult();

        return self::$currentUser;
    }

    public static function isAdmin(): bool
    {
        try {
            $user = self::currentUser();
        } catch (\Throwable $e) {
            return false;
        }

        // user.current returns ADMIN => 'Y'/'N' for portal administrators.
        return isset($user['ADMIN']) && $user['ADMIN'] === true || ($user['ADMIN'] ?? 'N') === 'Y';
    }

    /** List of active portal users for the manager/responsible dropdowns. */
    public static function listUsers(): array
    {
        $response = self::core()->call('user.get', ['ACTIVE' => true]);
        $users = $response->getResponseData()->getResult();

        $result = [];
        foreach ($users as $u) {
            $result[] = [
                'id' => (int) $u['ID'],
                'name' => trim(($u['NAME'] ?? '') . ' ' . ($u['LAST_NAME'] ?? '')) ?: ('User #' . $u['ID']),
            ];
        }

        return $result;
    }

    /** List of portal user groups (departments/workgroups) for access settings. */
    public static function listGroups(): array
    {
        $response = self::core()->call('department.get', []);
        $groups = $response->getResponseData()->getResult();

        $result = [];
        foreach ($groups as $g) {
            $result[] = [
                'id' => (int) $g['ID'],
                'name' => $g['NAME'] ?? ('Group #' . $g['ID']),
            ];
        }

        return $result;
    }
}
