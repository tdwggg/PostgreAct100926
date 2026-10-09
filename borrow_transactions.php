<?php
require __DIR__.'/config.php';

function runSql($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

$txns = runSql($pdo, '
  SELECT bt.borrow_id, b.borrower_no, b.full_name,
         bt.borrow_date, bt.due_date, bt.status
  FROM borrow_transactions bt
  JOIN borrowers b ON bt.borrower_id = b.borrower_id
  ORDER BY bt.borrow_id
')->fetchAll();

$borrowers = runSql($pdo, 'SELECT * FROM borrowers ORDER BY borrower_id')->fetchAll();

$mode  = $_GET['mode'] ?? 'add';
$id    = $_GET['id'] ?? null;

// Load data for editing
$current = ['borrower_id' => '', 'borrow_date' => '', 'due_date' => '', 'status' => 'pending'];
if ($mode === 'edit' && $id) {
    $current = runSql($pdo, 'SELECT * FROM borrow_transactions WHERE borrow_id = ?', [$id])->fetch() ?: $current;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrower_id = (int)($_POST['borrower_id'] ?? 0);
    $borrow_date = $_POST['borrow_date'] ?? '';
    $due_date    = $_POST['due_date'] ?? '';
    $status      = $_POST['status'] ?? 'pending';

    if ($mode === 'add') {
        $sql = "INSERT INTO borrow_transactions (borrower_id, borrow_date, due_date, status) VALUES (:bid, :bd, :dd, :st)";
        runSql($pdo, $sql, [':bid'=>$borrower_id, ':bd'=>$borrow_date, ':dd'=>$due_date, ':st'=>$status]);
    } else {
        $sql = "UPDATE borrow_transactions SET borrower_id=:bid, borrow_date=:bd, due_date=:dd, status=:st WHERE borrow_id=:id";
        runSql($pdo, $sql, [':bid'=>$borrower_id, ':bd'=>$borrow_date, ':dd'=>$due_date, ':st'=>$status, ':id'=>$id]);
    }
    header('Location: borrow_transactions.php');
    exit;
}

if (isset($_GET['del'])) {
    runSql($pdo, 'DELETE FROM borrow_transactions WHERE borrow_id=:id', [':id'=>$_GET['del']]);
    header('Location: borrow_transactions.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Borrow Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-secondary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">Borrow‑Equipment Manager</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link active" aria-current="page">Borrow Transactions</a></li>
    </ul>
  </div>
</nav>
<div class="container">
  <h2 class="mt-4">Borrow Transactions</h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Borrow ID</th><th>Borrower No</th><th>Full Name</th><th>Borrow Date</th><th>Due Date</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($txns as $t): ?>
      <tr>
        <td><?php echo htmlspecialchars($t['borrow_id']); ?></td>
        <td><?php echo htmlspecialchars($t['borrower_no']); ?></td>
        <td><?php echo htmlspecialchars($t['full_name']); ?></td>
        <td><?php echo htmlspecialchars($t['borrow_date']); ?></td>
        <td><?php echo htmlspecialchars($t['due_date']); ?></td>
        <td><?php echo htmlspecialchars($t['status']); ?></td>
        <td>
          <a class="btn btn-sm btn-warning" href="?mode=edit&id=<?php echo $t['borrow_id']; ?>">Edit</a>
          <form class="d-inline" method="post" action="?mode=del&id=<?php echo $t['borrow_id']; ?>" onsubmit="return confirm('Delete this transaction?');">
            <button class="btn btn-sm btn-danger" type="submit">Del</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 class="mt-4"> <?php echo $mode === 'add' ? 'New Borrow Transaction' : 'Edit Transaction'; ?> </h2>
  <form method="post" class="row g-3">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">
    
    <div class="col-md-6">
      <label class="form-label">Borrower <span class="text-danger">*</span></label>
      <select name="borrower_id" class="form-select" required>
        <option value="">-- select --</option>
        <?php foreach ($borrowers as $b): ?>
        <option value="<?php echo $b['borrower_id']; ?>" <?php echo ($current['borrower_id'] == $b['borrower_id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($b['borrower_no'].' – '.$b['full_name']); ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Borrow Date <span class="text-danger">*</span></label>
      <input type="date" name="borrow_date" class="form-control" value="<?php echo htmlspecialchars($current['borrow_date']); ?>" required>
    </div>

    <div class="col-md-4">
      <label class="form-label">Due Date</label>
      <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($current['due_date']); ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Status <span class="text-danger">*</span></label>
      <select name="status" class="form-select" required>
        <option value="pending" <?php echo ($current['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
        <option value="returned" <?php echo ($current['status'] === 'returned') ? 'selected' : ''; ?>>Returned</option>
        <option value="overdue" <?php echo ($current['status'] === 'overdue') ? 'selected' : ''; ?>>Overdue</option>
      </select>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Save</button>
      <a class="btn btn-secondary" href="borrow_transactions.php">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>