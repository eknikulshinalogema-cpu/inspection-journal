<?php

namespace App;

use PDO;

class SettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM settings ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, string $title, string $subItems): void
    {
        $stmt = $this->pdo->prepare('UPDATE settings SET title = :title, sub_items = :sub WHERE id = :id');
        $stmt->execute([':title' => $title, ':sub' => $subItems, ':id' => $id]);
    }

    public function setHidden(int $id, bool $hidden): void
    {
        $stmt = $this->pdo->prepare('UPDATE settings SET is_hidden = :h WHERE id = :id');
        $stmt->execute([':h' => $hidden ? 1 : 0, ':id' => $id]);
    }

    public function add(string $title, string $subItems): int
    {
        $maxSort = (int) $this->pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM settings')->fetchColumn();
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (sort_order, title, sub_items, is_hidden) VALUES (:sort, :title, :sub, 0)'
        );
        $stmt->execute([':sort' => $maxSort + 10, ':title' => $title, ':sub' => $subItems]);
        return (int) $this->pdo->lastInsertId();
    }

    public function accessUsers(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM access_users ORDER BY id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveAccessUser(int $b24UserId, string $name, bool $canView, bool $canEdit): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM access_users WHERE b24_user_id = :uid');
        $stmt->execute([':uid' => $b24UserId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $upd = $this->pdo->prepare(
                'UPDATE access_users SET user_name = :name, can_view = :view, can_edit = :edit WHERE id = :id'
            );
            $upd->execute([':name' => $name, ':view' => $canView ? 1 : 0, ':edit' => $canEdit ? 1 : 0, ':id' => $existing]);
        } else {
            $ins = $this->pdo->prepare(
                'INSERT INTO access_users (b24_user_id, user_name, can_view, can_edit) VALUES (:uid, :name, :view, :edit)'
            );
            $ins->execute([':uid' => $b24UserId, ':name' => $name, ':view' => $canView ? 1 : 0, ':edit' => $canEdit ? 1 : 0]);
        }
    }
}
