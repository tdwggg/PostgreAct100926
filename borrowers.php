<?php
require __DIR__.'/config.php';

function runSql($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

$borrowers = runSql($pdo, 'SELECT * FROM borrowers ORDER BY borrower_id')->fetchAll();

$mode  = $_GET['mode'] ?? 'add';
$id    = $_GET['id'] ?? null;

// Load data for editing
$current = ['borrower_no' => '', 'full_name' => '', 'borrower_type' => 'individual'];
if ($mode === 'edit' && $id) {
    $current = runSql($pdo, 'SELECT * FROM borrowers WHERE borrower_id = ?', [$id])->fetch() ?: $current;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrower_no = $_POST['borrower_no'] ?? '';
    $full_name   = $_POST['full_name'] ?? '';
    $borrower_type = $_POST['borrower_type'] ?? '';

    if ($mode === 'add') {
        $sql = "INSERT INTO borrowers (borrower_no, full_name, borrower_type) VALUES (:bn, :fn, :bt)";
        runSql($pdo, $sql, [':bn'=>$borrower_no, ':fn'=>$full_name, ':bt'=>$borrower_type]);
    } else {
        $sql = "UPDATE borrowers SET borrower_no=:bn, full_name=:fn, borrower_type=:bt WHERE borrower_id=:id";
        runSql($pdo, $sql, [':bn'=>$borrower_no, ':fn'=>$full_name, ':bt'=>$borrower_type, ':id'=>$id]);
    }
    header('Location: borrowers.php');
    exit;
}

if (isset($_GET['del'])) {
    runSql($pdo, 'DELETE FROM borrowers WHERE borrower_id=:id', [':id'=>$_GET['del']]);
    header('Location: borrowers.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Borrowers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-secondary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">Borrow‑Equipment Manager</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link active" aria-current="page">Borrowers</a></li>
    </ul>
  </div>
</nav>
<div class="container">
  <h2 class="mt-4">Borrower List</h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>ID</th><th>Borrower No</th><th>Full Name</th><th>Type</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($borrowers as $b): ?>
      <tr>
        <td><?php echo htmlspecialchars($b['borrower_id']); ?></td>
        <td><?php echo htmlspecialchars($b['borrower_no']); ?></td>
        <td><?php echo htmlspecialchars($b['full_name']); ?></td>
        <td><?php echo htmlspecialchars($b['borrower_type']); ?></td>
        <td>
          <a class="btn btn-sm btn-warning" href="?mode=edit&id=<?php echo $b['borrower_id']; ?>">Edit</a>
          <form class="d-inline" method="post" action="?mode=del&id=<?php echo $b['borrower_id']; ?>" onsubmit="return confirm('Delete this borrower?');">
            <button class="btn btn-sm btn-danger" type="submit">Del</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 class="mt-4"> <?php echo $mode === 'add' ? 'Add New Borrower' : 'Edit Borrower'; ?> </h2>
  <form method="post" class="row g-3">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id ?? ''); ?>">
    <div class="col-md-6">
      <label class="form-label">Borrower No <span class="text-danger">*</span></label>
      <input type="text" name="borrower_no" class="form-control" value="<?php echo htmlspecialchars($current['borrower_no']); ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Full Name <span class="text-danger">*</span></label>
      <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($current['full_name']); ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Borrower Type</label>
      <select name="borrower_type" class="form-select">
        <option value="individual" <?php echo ($current['borrower_type'] === 'individual') ? 'selected' : ''; ?>>Individual</option>
        <option value="corporate" <?php echo ($current['borrower_type'] === 'corporate') ? 'selected' : ''; ?>>Corporate</option>
      </select>
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Save</button>
      <a class="btn btn-secondary" href="borrowers.php">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>