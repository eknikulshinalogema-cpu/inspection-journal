<?php

namespace App;

use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use Bitrix24\SDK\Services\ServiceBuilder;
use Symfony\Component\HttpFoundation\Request;

class Auth
{
    private static ?ServiceBuilder $service = null;
    private static ?array $currentUser = null;

    public static function service(): ServiceBuilder
    {
        if (self::$service !== null) {
            return self::$service;
        }

        $appProfile = ApplicationProfile::initFromArray([
            'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => getenv('B24_CLIENT_ID') ?: '',
            'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => getenv('B24_CLIENT_SECRET') ?: '',
            'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => getenv('B24_APP_SCOPE') ?: 'user,department',
        ]);

        self::$service = ServiceBuilderFactory::createServiceBuilderFromPlacementRequest(
            Request::createFromGlobals(),
            $appProfile
        );

        return self::$service;
    }

    public static function currentUser(): array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $response = self::service()->core->call('user.current', []);
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

        return ($user['ADMIN'] ?? 'N') === 'Y' || ($user['ADMIN'] ?? null) === true;
    }

    public static function listUsers(): array
    {
        $response = self::service()->core->call('user.get', ['ACTIVE' => true]);
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

    public static function listGroups(): array
    {
        $response = self::service()->core->call('department.get', []);
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
