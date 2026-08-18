<?php

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Auth;
use App\JournalRepository;
use App\SettingsRepository;
use App\SummaryService;
use App\Numbering;

$route = $_GET['route'] ?? 'list';

try {
    switch ($route) {
        case 'create':
            handleCreate();
            break;
        case 'journal':
            handleJournal();
            break;
        case 'delete':
            handleDelete();
            break;
        case 'admin':
            handleAdmin();
            break;
        case 'list':
        default:
            handleList();
            break;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo '<h1>Ошибка</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function userLabel(array $usersById, ?int $id): string
{
    if (!$id) {
        return '—';
    }
    return e($usersById[$id]['name'] ?? ('#' . $id));
}

function usersById(array $users): array
{
    $map = [];
    foreach ($users as $u) {
        $map[$u['id']] = $u;
    }
    return $map;
}

function handleList(): void
{
    $repo = new JournalRepository();
    $journals = $repo->listAll();
    $users = usersById(Auth::listUsers());

    $rows = [];
    foreach ($journals as $j) {
        $items = $repo->itemsFor((int) $j['id']);
        $j['summary'] = $j['status'] === 'draft' ? '— (черновик)' : SummaryService::summarizeItems($items);
        $rows[] = $j;
    }

    $reportFrom = $_GET['from'] ?? '';
    $reportTo = $_GET['to'] ?? '';
    $reportRows = [];
    $topWordsLabel = '';

    if ($reportFrom && $reportTo) {
        $completed = $repo->completedInRange($reportFrom, $reportTo);
        $allComments = [];

        foreach ($completed as $j) {
            $items = $repo->itemsFor((int) $j['id']);
            $reportRows[] = [
                'created_at' => $j['created_at'],
                'shift_manager' => userLabel($users, $j['shift_manager_id']),
                'responsible' => userLabel($users, $j['responsible_id']),
                'digest' => SummaryService::summarizeItems($items),
            ];
            foreach ($items as $it) {
                $c = trim((string) ($it['comment'] ?? ''));
                if ($c !== '') {
                    $allComments[] = $c;
                }
            }
        }

        $topWordsLabel = SummaryService::topWordsLabel($allComments);
    }

    include __DIR__ . '/../views/list.php';
}

function handleCreate(): void
{
    $isAdmin = true; // any authenticated portal user may create a journal
    $users = Auth::listUsers();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $date = $_POST['date'] ?? date('Y-m-d');
        $mgr = $_POST['shift_manager_id'] !== '' ? (int) $_POST['shift_manager_id'] : null;
        $resp = $_POST['responsible_id'] !== '' ? (int) $_POST['responsible_id'] : null;

        $repo = new JournalRepository();
        $id = $repo->create($date, $mgr, $resp);

        header('Location: ?route=journal&id=' . $id);
        exit;
    }

    include __DIR__ . '/../views/create.php';
}

function handleDelete(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            (new JournalRepository())->delete($id);
        }
    }
    header('Location: ?route=list');
    exit;
}

function handleJournal(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $repo = new JournalRepository();
    $journal = $repo->find($id);

    if
