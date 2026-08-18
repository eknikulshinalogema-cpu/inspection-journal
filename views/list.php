<?php $pageTitle = 'Журнал осмотра помещений и оборудования'; include __DIR__ . '/_layout_top.php'; ?>
<h1>Журнал осмотра помещений и оборудования</h1>
<div class="toolbar">
  <a class="button" href="?route=create">+ Создать журнал</a>
  <a class="button secondary" href="?route=admin">Настройки (админ)</a>
</div>
<table>
  <thead>
    <tr>
      <th>Дата</th>
      <th>Начальник смены</th>
      <th>Ответственный</th>
      <th>Статус</th>
      <th>Краткая выжимка</th>
      <th>Действия</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="6">Журналов пока нет.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $j): ?>
      <tr>
        <td><?= e($j['date']) ?></td>
        <td><?= userLabel($users, $j['shift_manager_id']) ?></td>
        <td><?= userLabel($users, $j['responsible_id']) ?></td>
        <td class="status-<?= e($j['status']) ?>">
          <?= $j['status'] === 'completed' ? 'Завершено' : 'Черновик' ?>
        </td>
        <td><?= e($j['summary']) ?></td>
        <td>
          <a href="?route=journal&id=<?= (int) $j['id'] ?>">
            <?= $j['status'] === 'completed' ? 'Просмотр' : 'Редактировать' ?>
          </a>
          <br>
          <form method="post" action="?route=delete" style="display:inline;"
                onsubmit="return confirm('Удалить журнал за <?= e($j['date']) ?>? Это необратимо.');">
            <input type="hidden" name="id" value="<?= (int) $j['id'] ?>">
            <button type="submit" class="danger" style="padding:2px 8px; font-size:12px;">Удалить</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<h2>Сводка за период</h2>
<form method="get" class="form-header">
  <input type="hidden" name="route" value="list">
  <div class="field">
    <label>С</label>
    <input type="date" name="from" value="<?= e($reportFrom) ?>">
  </div>
  <div class="field">
    <label>По</label>
    <input type="date" name="to" value="<?= e($reportTo) ?>">
  </div>
  <div class="field" style="flex: 0 0 auto; align-self: flex-end;">
    <button type="submit">Сформировать сводку</button>
  </div>
</form>
<?php if ($reportFrom && $reportTo): ?>
  <?php if (empty($reportRows) && empty($deviationsByDate)): ?>
    <p>За выбранный период отклонений не найдено.</p>
  <?php else: ?>
    <?php if (!empty($reportRows)): ?>
      <table>
        <thead>
          <tr>
            <th>Дата создания</th>
            <th>Начальник смены</th>
            <th>Ответственный</th>
            <th>Краткая выжимка</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reportRows as $row): ?>
            <tr>
              <td><?= e(date('d.m.Y H:i', strtotime($row['created_at']))) ?></td>
              <td><?= $row['shift_manager'] ?></td>
              <td><?= $row['responsible'] ?></td>
              <td><?= e($row['digest']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <?php if (!empty($deviationsByDate)): ?>
      <div style="background:#fff; padding:14px; margin-top:10px; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,.1);">
        <strong>Отклонения по датам</strong>
        <?php foreach ($deviationsByDate as $date => $titles): ?>
          <p><strong><?= e($date) ?>:</strong> <?= e(implode(', ', $titles)) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
<?php endif; ?>
</body>
</html>
