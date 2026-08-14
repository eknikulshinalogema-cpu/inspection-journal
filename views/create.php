<?php $pageTitle = 'Создать журнал'; include __DIR__ . '/_layout_top.php'; ?>

<h1>Создать журнал</h1>

<form method="post" class="form-header">
  <div class="field">
    <label>Дата</label>
    <input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" required>
  </div>
  <div class="field">
    <label>Начальник смены</label>
    <select name="shift_manager_id">
      <option value="">—</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label>Ответственный</label>
    <select name="responsible_id">
      <option value="">—</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field" style="flex: 0 0 auto; align-self: flex-end;">
    <button type="submit">Создать и перейти к заполнению</button>
  </div>
</form>

<p><a href="?route=list">&larr; Назад к списку</a></p>

</body>
</html>
