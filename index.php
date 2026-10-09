<?php require __DIR__.'/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Borrow‑Equipment Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
  <div class="container">
    <a class="navbar-brand" href="#">Borrow‑Equipment Manager</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarnav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarnav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="borrowers.php">Borrowers</a></li>
        <li class="nav-item"><a class="nav-link" href="equipment.php">Equipment</a></li>
        <li class="nav-item"><a class="nav-link" href="borrow_transactions.php">Borrow Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="borrow_items.php">Borrow Items</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
  <h2>Select a table to manage</h2>
  <p class="lead">Use the navigation links above or the buttons below.</p>
</div>

</body>
</html>