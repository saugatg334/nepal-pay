<?php
// simple landing / login placeholder
require_once __DIR__ . '/../app/helpers/session_helper.php';
if (!empty($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>NepalPay — Login</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/custom.css" />
</head>
<body class="bg-gradient-to-br from-indigo-50 to-white min-h-screen flex items-center justify-center">
  <div class="max-w-md w-full">
    <div class="bg-white p-8 rounded-2xl shadow-lg">
      <h2 class="text-2xl font-semibold mb-2">Welcome to NepalPay</h2>
      <p class="text-sm text-gray-500 mb-6">Demo login (development only)</p>

      <form method="post" action="auth.php">
        <label class="text-xs text-gray-500">Phone</label>
        <input name="phone" class="w-full mt-2 px-3 py-2 border rounded" required />

        <label class="text-xs text-gray-500 mt-4">Password / PIN</label>
        <input name="password" type="password" class="w-full mt-2 px-3 py-2 border rounded" required />

        <div class="mt-6 flex justify-between items-center">
          <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Login</button>
          <a href="#" class="text-sm text-indigo-600">Register</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>