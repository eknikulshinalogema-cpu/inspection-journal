<?php $pageTitle = 'Настройки'; include __DIR__ . '/_layout_top.php'; ?>

<h1>Настройки полей журнала</h1>
<p><a href="?route=list">&larr; Назад к списку</a></p>

<?php if ($saved): ?>
  <div class="success-banner">Сохранено.</div>
<?php endif; ?>

<h2>Строки журнала</h2>
<table>
  <thead>
    <tr>
      <th class="num-col">#</th>
      <th>Название (title)</th>
      <th>Оборудование (sub_items)</th>
      <th style="width:90px;">Скрыта</th>
      <th style="width:160px;">Действия</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $row): ?>
      <?php $rid = (int) $row['id']; $updateForm = 'row-update-' . $rid; $toggleForm = 'row-toggle-' . $rid; ?>
      <tr>
        <td><?= (int) $row['sort_order'] ?></td>
        <td><input type="text" name="title" form="<?= $updateForm ?>" value="<?= e($row['title']) ?>"></td>
        <td><input type="text" name="sub_items" form="<?= $updateForm ?>" value="<?= e($row['sub_items']) ?>"></td>
        <td><?= $row['is_hidden'] ? 'Да' : 'Нет' ?></td>
        <td>
          <button type="submit" form="<?= $updateForm ?>">Сохранить</button>
          <button type="submit" form="<?= $toggleForm ?>" class="<?= $row['is_hidden'] ? 'secondary' : 'danger' ?>">
            <?= $row['is_hidden'] ? 'Показать' : 'Скрыть' ?>
          </button>
        </td>
      </tr>
      <tr style="display:none;">
        <td colspan="5">
          <form id="<?= $updateForm ?>" method="post">
            <input type="hidden" name="action" value="update_row">
            <input type="hidden" name="id" value="<?= $rid ?>">
          </form>
          <form id="<?= $toggleForm ?>" method="post">
            <input type="hidden" name="action" value="<?= $row['is_hidden'] ? 'show_row' : 'hide_row' ?>">
            <input type="hidden" name="id" value="<?= $rid ?>">
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h2>Добавить новую строку</h2>
<form method="post" class="form-header">
  <input type="hidden" name="action" value="add_row">
  <div class="field">
    <label>Название</label>
    <input type="text" name="title" required>
  </div>
  <div class="field">
