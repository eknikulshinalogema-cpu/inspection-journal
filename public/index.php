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
    $reportGroups = [];
    $topWordsLabel = '';

    if ($reportFrom && $reportTo) {
        $completed = $repo->completedInRange($reportFrom, $reportTo);
        $byDate = [];
        $allComments = [];

        foreach ($completed as $j) {
            $items = $repo->itemsFor((int) $j['id']);
            $date = $j['date'];
            $byDate[$date] ??= ['violation' => false, 'comments' => []];

            if (SummaryService::hasAnyViolation($items)) {
                $byDate[$date]['violation'] = true;
            }
            foreach ($items as $it) {
                $c = trim((string) ($it['comment'] ?? ''));
                if ($c !== '') {
                    $byDate[$date]['comments'][] = $c;
                    $allComments[] = $c;
                }
            }
        }

        ksort($byDate);
        $reportGroups = $byDate;
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

function handleJournal(): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $repo = new JournalRepository();
    $journal = $repo->find($id);

    if (!$journal) {
        http_response_code(404);
        echo '<h1>Журнал не найден</h1>';
        return;
    }

    $users = Auth::listUsers();
    $usersById = usersById($users);
    $error = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'save_draft';

        $date = $_POST['date'] ?? $journal['date'];
        $mgr = $_POST['shift_manager_id'] !== '' ? (int) $_POST['shift_manager_id'] : null;
        $resp = $_POST['responsible_id'] !== '' ? (int) $_POST['responsible_id'] : null;
        $repo->updateHeader($id, $date, $mgr, $resp);

        $itemsInput = $_POST['items'] ?? [];
        $repo->updateItems($id, $itemsInput);

        // Re-fetch to validate against what was just persisted.
        $freshItems = $repo->itemsFor($id);

        if ($action === 'complete') {
            $incomplete = SummaryService::findIncompleteRows($freshItems);
            if (!empty($incomplete)) {
                $error = 'Заполните "Исправность", "Санитарное состояние" и "Освещение" во всех строках перед завершением.';
            } else {
                $repo->setStatus($id, 'completed');
                header('Location: ?route=list');
                exit;
            }
        } else {
            header('Location: ?route=journal&id=' . $id . '&saved=1');
            exit;
        }

        $journal = $repo->find($id);
    }

    $items = $repo->itemsFor($id);
    $numbers = Numbering::assign($items);
    $readOnly = $journal['status'] === 'completed';
    $saved = isset($_GET['saved']);

    include __DIR__ . '/../views/journal.php';
}

function handleAdmin(): void
{
    if (!Auth::isAdmin()) {
        http_response_code(403);
        echo '<h1>Доступ запрещён</h1><p>Раздел доступен только администраторам портала.</p>';
        return;
    }

    $repo = new SettingsRepository();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_row') {
            $repo->update((int) $_POST['id'], trim($_POST['title']), trim($_POST['sub_items']));
        } elseif ($action === 'hide_row') {
            $repo->setHidden((int) $_POST['id'], true);
        } elseif ($action === 'show_row') {
            $repo->setHidden((int) $_POST['id'], false);
        } elseif ($action === 'add_row') {
            $repo->add(trim($_POST['title']), trim($_POST['sub_items']));
        } elseif ($action === 'save_access') {
            foreach ($_POST['groups'] as $groupId => $vals) {
                $repo->saveAccessGroup(
                    (int) $groupId,
                    $vals['name'] ?? '',
                    isset($vals['can_view']),
                    isset($vals['can_edit'])
                );
            }
        }

        header('Location: ?route=admin&saved=1');
        exit;
    }

    $rows = $repo->all();
    $groups = [];
    try {
        $groups = Auth::listGroups();
    } catch (\Throwable $e) {
        // Non-fatal: access-group management is optional if department.get is unavailable.
    }
    $accessByGroup = [];
    foreach ($repo->accessGroups() as $ag) {
        $accessByGroup[$ag['b24_group_id']] = $ag;
    }
    $saved = isset($_GET['saved']);

    include __DIR__ . '/../views/admin.php';
}
