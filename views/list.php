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
  <?php if (empty($reportGroups)): ?>
    <p>За выбранный период завершённых журналов не найдено.</p>
  <?php else: ?>
    <?php foreach ($reportGroups as $date => $data): ?>
      <div style="background:#fff; padding:14px; margin-top:10px; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,.1);">
        <strong>Дата: <?= e($date) ?></strong><br>
        <?= $data['violation'] ? '⚠ Есть нарушения' : '✅ Ок' ?>
        <?php if (!empty($data['comments'])): ?>
          <ul>
            <?php foreach ($data['comments'] as $c): ?>
              <li><?= e($c) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div style="background:#fff; padding:14px; margin-top:10px; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,.1);">
      <strong>Частотный анализ (Топ-3 замечаний)</strong>
      <p><?= e($topWordsLabel) ?></p>
    </div>
  <?php endif; ?>
<?php endif; ?>

</body>
</html>
