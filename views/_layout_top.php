<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Журнал осмотра') ?></title>
<style>
  body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; padding: 24px; background: #f4f5f7; color: #172b4d; }
  h1 { font-size: 22px; margin-bottom: 16px; }
  h2 { font-size: 17px; margin-top: 32px; }
  a.button, button { display: inline-block; background: #2065d1; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 14px; cursor: pointer; }
  a.button.secondary, button.secondary { background: #6b778c; }
  a.button.danger, button.danger { background: #de350b; }
  table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.1); margin-top: 12px; }
  th, td { border: 1px solid #dfe1e6; padding: 8px 10px; font-size: 13px; text-align: left; vertical-align: top; }
  th { background: #ebecf0; }
  input[type=date], input[type=number], input[type=text], select, textarea { padding: 6px; border: 1px solid #c1c7d0; border-radius: 4px; font-size: 13px; width: 100%; box-sizing: border-box; }
  textarea { min-height: 40px; resize: vertical; }
  .toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; }
  .status-draft { color: #b26b00; font-weight: 600; }
  .status-completed { color: #006644; font-weight: 600; }
  .error-banner { background: #ffebe6; border: 1px solid #de350b; color: #bf2600; padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; }
  .success-banner { background: #e3fcef; border: 1px solid #006644; color: #006644; padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; }
  .num-col { width: 48px; white-space: nowrap; }
  .form-header, .form-footer { background: #fff; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,.1); border-radius: 4px; margin-bottom: 16px; display: flex; gap: 16px; flex-wrap: wrap; }
  .field { flex: 1; min-width: 200px; }
  .field label { display: block; font-size: 12px; color: #6b778c; margin-bottom: 4px; }
</style>
</head>
<body>
