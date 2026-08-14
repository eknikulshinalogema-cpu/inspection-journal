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

    public function accessGroups(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM access_groups ORDER BY id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveAccessGroup(int $b24GroupId, string $name, bool $canView, bool $canEdit): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM access_groups WHERE b24_group_id = :gid');
        $stmt->execute([':gid' => $b24GroupId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $upd = $this->pdo->prepare(
                'UPDATE access_groups SET group_name = :name, can_view = :view, can_edit = :edit WHERE id = :id'
            );
            $upd->execute([
                ':name' => $name, ':view' => $canView ? 1 : 0, ':edit' => $canEdit ? 1 : 0, ':id' => $existing,
            ]);
        } else {
            $ins = $this->pdo->prepare(
                'INSERT INTO access_groups (b24_group_id, group_name, can_view, can_edit)
                 VALUES (:gid, :name, :view, :edit)'
            );
            $ins->execute([
                ':gid' => $b24GroupId, ':name' => $name, ':view' => $canView ? 1 : 0, ':edit' => $canEdit ? 1 : 0,
            ]);
        }
    }
}
