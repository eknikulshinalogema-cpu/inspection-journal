<?php

namespace App;

use PDO;

class JournalRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM journals ORDER BY date DESC, id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM journals WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $journal = $stmt->fetch(PDO::FETCH_ASSOC);
        return $journal ?: null;
    }

    public function itemsFor(int $journalId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM journal_items WHERE journal_id = :jid ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':jid' => $journalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Create a new journal, pre-populated with the active settings rows. */
    public function create(string $date, ?int $shiftManagerId, ?int $responsibleId): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO journals (date, shift_manager_id, responsible_id, status)
                 VALUES (:date, :mgr, :resp, :status)'
            );
            $stmt->execute([
                ':date' => $date,
                ':mgr' => $shiftManagerId,
                ':resp' => $responsibleId,
                ':status' => 'draft',
            ]);
            $journalId = (int) $this->pdo->lastInsertId();

            $rows = $this->pdo->query(
                'SELECT sort_order, title, sub_items FROM settings WHERE is_hidden = 0 ORDER BY sort_order ASC'
            )->fetchAll(PDO::FETCH_ASSOC);

            $itemStmt = $this->pdo->prepare(
                'INSERT INTO journal_items (journal_id, sort_order, title, sub_items)
                 VALUES (:jid, :sort, :title, :sub)'
            );
            foreach ($rows as $row) {
                $itemStmt->execute([
                    ':jid' => $journalId,
                    ':sort' => $row['sort_order'],
                    ':title' => $row['title'],
                    ':sub' => $row['sub_items'],
                ]);
            }

            $this->pdo->commit();
            return $journalId;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateHeader(int $journalId, string $date, ?int $shiftManagerId, ?int $responsibleId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE journals SET date = :date, shift_manager_id = :mgr, responsible_id = :resp WHERE id = :id'
        );
        $stmt->execute([
            ':date' => $date,
            ':mgr' => $shiftManagerId,
            ':resp' => $responsibleId,
            ':id' => $journalId,
        ]);
    }

    /**
     * Update only the employee-editable columns of each row.
     * $items: [ item_id => ['is_faulty'=>..,'sanitary_score'=>..,'lighting'=>..,'comment'=>..] ]
     */
    public function updateItems(int $journalId, array $items): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE journal_items
             SET is_faulty = :faulty, sanitary_score = :score, lighting = :lighting, comment = :comment
             WHERE id = :id AND journal_id = :jid'
        );

        foreach ($items as $itemId => $values) {
            $score = $values['sanitary_score'] ?? '';
            $stmt->execute([
                ':faulty' => $values['is_faulty'] ?: null,
                ':score' => $score === '' ? null : (int) $score,
                ':lighting' => $values['lighting'] ?: null,
                ':comment' => $values['comment'] ?? '',
                ':id' => (int) $itemId,
                ':jid' => $journalId,
            ]);
        }
    }

    public function setStatus(int $journalId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE journals SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $journalId]);
    }

    /** All fully-completed rows within [from, to] inclusive, for the reports block. */
    public function completedInRange(string $from, string $to): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM journals WHERE status = 'completed' AND date BETWEEN :from AND :to
             ORDER BY date ASC, id ASC"
        );
        $stmt->execute([':from' => $from, ':to' => $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
