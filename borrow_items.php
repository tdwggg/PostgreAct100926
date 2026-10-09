<?php
require __DIR__.'/config.php';

function runSql($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Read
$items = runSql($pdo, '
  SELECT bi.borrow_item_id, bt.borrow_id, b.borrower_no,
         e.equipment_id, e.equipment_name, bi.quantity
  FROM borrow_items bi
  JOIN borrow_transactions bt ON bi.borrow_id = bt.borrow_id
  JOIN equipment e ON bi.equipment_id = e.equipment_id
  JOIN borrowers b ON bt.borrower_id = b.borrower_id
  ORDER BY bi.borrow_item_id')
->fetchAll();

// Add / Edit
$mode  = $_GET['mode'] ?? 'add';
$id    = $_GET['id'] ?? null;

// Pre‑load options
$borrowers = runSql($pdo, 'SELECT * FROM borrowers ORDER BY borrower_id')->fetchAll();
$equipments = runSql($pdo, 'SELECT * FROM equipment ORDER BY equipment_id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrow_id   = (int)($_POST['borrow_id'] ?? 0);
    $equipment_id = (int)($_POST['equipment_id'] ?? 0);
    $quantity    = (int)($_POST['quantity'] ?? 0);

    if ($mode === 'add') {
        $sql = "INSERT INTO borrow_items (borrow_id, equipment_id, quantity) VALUES (:bid, :eid, :qty)";
        runSql($pdo, $sql, [':bid'=>$borrow_id, ':eid'=>$equipment_id, ':qty'=>$quantity]);
    } else {
        $sql = "UPDATE borrow_items SET borrow_id=:bid, equipment_id=:eid, qty=:qty WHERE borrow_item_id=:iid";
        runSql($pdo, $sql, [':bid'=>$borrow_id, ':eid'=>$equipment_id, ':qty'=>$quantity, ':iid'=>$id]);
    }
    header('Location: borrow_items.php');
    exit;
}

// Delete
if (isset($_GET['del'])) {
    runSql($pdo, 'DELETE FROM borrow_items WHERE borrow_item_id=:id', [':id'=>$_GET['del']]);
    header('Location: borrow_items.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Borrow Items</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-secondary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">Borrow‑Equipment Manager</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav5">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav5">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" aria-current="page">Borrow Items</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">

  <h2 class="mt-4">Borrow Items</h2>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Item ID</th>
        <th>Borrow ID</th>
        <th>Borrower No</th>
        <th>Equipment ID</th>
        <th>Equipment Name</th>
        <th>Quantity</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?php echo htmlspecialchars($it['borrow_item_id']); ?></td>
        <td><?php echo htmlspecialchars($it['borrow_id']); ?></td>
        <td><?php echo htmlspecialchars($it['borrower_no']); ?></td>
        <td><?php echo htmlspecialchars($it['equipment_id']); ?></td>
        <td><?php echo htmlspecialchars($it['equipment_name']); ?></td>
        <td><?php echo htmlspecialchars($it['quantity']); ?></td>
        <td>
          <a class="btn btn-sm btn-warning" href="?mode=edit&id=<?php echo $it['borrow_item_id']; ?>">Edit</a>
          <form class="d-inline" method="post" action="?mode=del&id=<?php echo $it['borrow_item_id']; ?>" onsubmit="return confirm('Delete this borrow item?');">
            <button class="btn btn-sm btn-danger" type="submit">Del</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 class="mt-4"> <?php echo $mode === 'add' ? 'Add New Borrow Item' : 'Edit Borrow Item'; ?> </h2>
  <form method="post" class="row g-3">
    <input type="hidden" name="id" value="<?php echo $id ?? ''; ?>">

    <div class="col-md-4">
      <label class="form-label">Borrow ID <span class="text-danger">*</span></label>
      <select name="borrow_id" class="form-select" required>
        <option value="">-- select --</option>
        <?php foreach ($borrowers as $b): ?>
        <option value="<?php echo $b['borrower_id']; ?>" <?php echo ($borrow_id == $b['borrower_id'])?'selected':''; ?>>
          <?php echo htmlspecialchars($b['borrower_no'].' ('.$b['full_name'].')'); ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Equipment ID <span class="text-danger">*</span></label>
      <select name="equipment_id" class="form-select" required>
        <option value="">-- select --</option>
        <?php foreach ($equipments as $e): ?>
        <option value="<?php echo $e['equipment_id']; ?>" <?php echo ($equipment_id == $e['equipment_id'])?'selected':''; ?>>
          <?php echo htmlspecialchars($e['equipment_name'].' ('.$e['property_no'].')'); ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Quantity <span class="text-danger">*</span></label>
      <input type="number" name="quantity" class="form-control" min="0" required>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Save</button>
      <a class="btn btn-secondary" href="borrow_items.php">Cancel</a>
    </div>
  </form>

</div>

<footer class="mt-5 text-center text-muted small">Generated by PHP‑Bootstrap CRUD UI</footer>

</body>
</html>