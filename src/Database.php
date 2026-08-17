<?php

namespace App;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dbPath = getenv('DB_PATH') ?: (__DIR__ . '/../data/journal.sqlite');
        $isNew = !file_exists($dbPath);

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');

        self::$pdo = $pdo;

        if ($isNew) {
            self::migrate($pdo);
        } else {
            self::ensureSchema($pdo);
        }

        return $pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS journals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date TEXT NOT NULL,
                shift_manager_id INTEGER,
                responsible_id INTEGER,
                status TEXT NOT NULL DEFAULT 'draft',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS journal_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                journal_id INTEGER NOT NULL,
                sort_order INTEGER NOT NULL,
                title TEXT NOT NULL,
                sub_items TEXT DEFAULT '',
                is_faulty TEXT,
                sanitary_score INTEGER,
                lighting TEXT,
                comment TEXT,
                FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sort_order INTEGER NOT NULL,
                title TEXT NOT NULL,
                sub_items TEXT DEFAULT '',
                is_hidden INTEGER NOT NULL DEFAULT 0
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS access_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                b24_user_id INTEGER NOT NULL UNIQUE,
                user_name TEXT,
                can_view INTEGER NOT NULL DEFAULT 1,
                can_edit INTEGER NOT NULL DEFAULT 0
            )
        ");

        self::seedDefaultRows($pdo);
    }

    private static function ensureSchema(PDO $pdo): void
    {
        // Idempotent safety net in case the app is redeployed against an
        // existing data volume that predates a later table.
        self::migrate($pdo);
    }

    private static function seedDefaultRows(PDO $pdo): void
    {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $defaultRows = require __DIR__ . '/../config/default_rows.php';

        $stmt = $pdo->prepare(
            'INSERT INTO settings (sort_order, title, sub_items, is_hidden) VALUES (:sort, :title, :sub, 0)'
        );

        foreach ($defaultRows as $row) {
            $stmt->execute([
                ':sort' => $row['sort'],
                ':title' => $row['title'],
                ':sub' => $row['sub'],
            ]);
        }
    }
}
