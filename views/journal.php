<?php $pageTitle = 'Журнал от ' . $journal['date']; include __DIR__ . '/_layout_top.php'; ?>

<h1>Журнал осмотра — <?= e($journal['date']) ?>
  <span class="status-<?= e($journal['status']) ?>">
    (<?= $journal['status'] === 'completed' ? 'Завершено' : 'Черновик' ?>)
  </span>
</h1>

<?php if ($saved): ?>
  <div class="success-banner">Черновик сохранён.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="error-banner"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
  <div class="form-header">
    <div class="field">
      <label>Дата</label>
      <input type="date" name="date" value="<?= e($journal['date']) ?>" <?= $readOnly ? 'disabled' : '' ?> required>
    </div>
    <div class="field">
      <label>Начальник смены</label>
      <select name="shift_manager_id" <?= $readOnly ? 'disabled' : '' ?>>
        <option value="">—</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>" <?= $u['id'] == $journal['shift_manager_id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="num-col">№</th>
        <th>Помещение; оборудование</th>
        <th style="width:110px;">Исправность</th>
        <th style="width:90px;">Санитарное состояние</th>
        <th style="width:100px;">Освещение</th>
        <th>Комментарий</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $itemKey => $item): ?>
        <?php $id = (int) $item['id']; ?>
        <tr>
          <td class="num-col"><?= e($numbers[$itemKey] ?? '') ?></td>
          <td>
            <?= e($item['title']) ?>
            <?php if (!empty($item['sub_items'])): ?>
              <div style="color:#6b778c; font-size:12px;"><?= e($item['sub_items']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <select name="items[<?= $id ?>][is_faulty]" <?= $readOnly ? 'disabled' : '' ?>>
              <option value="" <?= empty($item['is_faulty']) ? 'selected' : '' ?>>—</option>
              <?php foreach (['Ок', 'Не ок', 'Ремонт'] as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $item['is_faulty'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <input type="number" min="1" max="5" name="items[<?= $id ?>][sanitary_score]"
                   value="<?= e($item['sanitary_score'] !== null ? (string) $item['sanitary_score'] : '') ?>"
                   <?= $readOnly ? 'disabled' : '' ?>>
          </td>
          <td>
            <select name="items[<?= $id ?>][lighting]" <?= $readOnly ? 'disabled' : '' ?>>
              <option value="" <?= empty($item['lighting']) ? 'selected' : '' ?>>—</option>
              <?php foreach (['Ок', 'Не ок'] as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $item['lighting'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <textarea name="items[<?= $id ?>][comment]" <?= $readOnly ? 'disabled' : '' ?>><?= e($item['comment']) ?></textarea>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="form-footer">
    <div class="field">
      <label>Ответственный</label>
      <select name="responsible_id" <?= $readOnly ? 'disabled' : '' ?>>
        <option value="">—</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>" <?= $u['id'] == $journal['responsible_id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!$readOnly): ?>
      <div class="field" style="flex: 0 0 auto; align-self: flex-end; display:flex; gap:10px;">
        <button type="submit" name="action" value="save_draft" class="secondary">Сохранить черновик</button>
        <button type="submit" name="action" value="complete">Завершить</button>
      </div>
    <?php endif; ?>
  </div>
</form>

<p><a href="?route=list">&larr; Назад к списку</a></p>

</body>
</html>
