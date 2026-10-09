<?php
require __DIR__.'/config.php';

function runSql($pdo, $s, $p=[]) {
    $st = $pdo->prepare($s);
    $st->execute($p);
    return $st;
}

if (isset($_GET['del'], $_GET['entity'])) {
    $e = $_GET['entity'];
    $i = $_GET['del'];
    if ($e === 'borrower') runSql($pdo, "DELETE FROM borrowers WHERE borrower_id=?", [$i]);
    if ($e === 'equipment') runSql($pdo, "DELETE FROM equipment WHERE equipment_id=?", [$i]);
    if ($e === 'transaction') runSql($pdo, "DELETE FROM borrow_transactions WHERE borrow_id=?", [$i]);
    if ($e === 'item') runSql($pdo, "DELETE FROM borrow_items WHERE borrow_item_id=?", [$i]);
    header("Location: index.php#$e");
    exit;
}

if (isset($_GET['return_txn'])) {
    runSql($pdo, "UPDATE borrow_transactions SET status='returned' WHERE borrow_id=?", [$_GET['return_txn']]);
    header("Location: index.php#transaction");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    $i = $_POST['id'] ?? '';
    $m = $i ? 'edit' : 'add';

    if ($a === 'borrower') {
        $bn = $_POST['borrower_no']; 
        $fn = $_POST['full_name']; 
        $bt = $_POST['borrower_type'];
        
        if ($m === 'add') {
            runSql($pdo, "INSERT INTO borrowers (borrower_no, full_name, borrower_type) VALUES (?,?,?)", [$bn, $fn, $bt]);
        } else {
            runSql($pdo, "UPDATE borrowers SET borrower_no=?, full_name=?, borrower_type=? WHERE borrower_id=?", [$bn, $fn, $bt, $i]);
        }
    }
    
    if ($a === 'equipment') {
        $pn = $_POST['property_no'];
        $ep = $_POST['equip_preset'];
        $en = ($ep === 'Others') ? trim($_POST['equip_custom']) : $ep;
        $qa = (int)$_POST['quantity_available'];
        
        if ($m === 'add') {
            runSql($pdo, "INSERT INTO equipment (property_no, equipment_name, quantity_available) VALUES (?,?,?)", [$pn, $en, $qa]);
        } else {
            runSql($pdo, "UPDATE equipment SET property_no=?, equipment_name=?, quantity_available=? WHERE equipment_id=?", [$pn, $en, $qa, $i]);
        }
    }
    
    if ($a === 'item') {
        $bi = (int)$_POST['borrower_id'];
        $eq_name = trim($_POST['equipment_name']);
        $q = (int)$_POST['quantity'];
        $bd = $_POST['borrow_date'];
        $dd = $_POST['due_date'];

        $eq = runSql($pdo, "SELECT equipment_id FROM equipment WHERE LOWER(equipment_name) = LOWER(?)", [$eq_name])->fetch();
        
        if (!$eq) {
            echo "<script>alert('We cannot find \"$eq_name\" in your Things. Please add it first!'); window.location.href='index.php#item';</script>";
            exit;
        }
        
        $eid = $eq['equipment_id'];

        $stmt = runSql($pdo, "INSERT INTO borrow_transactions (borrower_id, borrow_date, due_date, status) VALUES (?,?,?,?) RETURNING borrow_id", [$bi, $bd, $dd, 'pending']);
        $new_txn_id = $stmt->fetchColumn();

        runSql($pdo, "INSERT INTO borrow_items (borrow_id, equipment_id, quantity) VALUES (?,?,?)", [$new_txn_id, $eid, $q]);
    }
    
    header("Location: index.php#$a");
    exit;
}

$borrowers = runSql($pdo, "SELECT * FROM borrowers ORDER BY borrower_id")->fetchAll();
$equipments = runSql($pdo, "SELECT * FROM equipment ORDER BY equipment_id")->fetchAll();
$txns = runSql($pdo, "SELECT bt.borrow_id, b.borrower_no, b.full_name, bt.borrow_date, bt.due_date, bt.status FROM borrow_transactions bt JOIN borrowers b ON bt.borrower_id = b.borrower_id ORDER BY bt.borrow_id DESC")->fetchAll();
$items = runSql($pdo, "SELECT bi.borrow_item_id, bt.borrow_id, b.borrower_no, e.equipment_id, e.equipment_name, bi.quantity FROM borrow_items bi JOIN borrow_transactions bt ON bi.borrow_id = bt.borrow_id JOIN equipment e ON bi.equipment_id = e.equipment_id JOIN borrowers b ON bt.borrower_id = b.borrower_id ORDER BY bi.borrow_item_id DESC")->fetchAll();

$ee = $_GET['edit'] ?? '';
$ei = $_GET['id'] ?? '';

$cb = ['borrower_no' => '', 'full_name' => '', 'borrower_type' => 'individual'];
$ce = ['property_no' => '', 'equipment_name' => '', 'quantity_available' => ''];

if ($ee === 'borrower' && $ei) {
    $cb = runSql($pdo, "SELECT * FROM borrowers WHERE borrower_id=?", [$ei])->fetch() ?: $cb;
}
if ($ee === 'equipment' && $ei) {
    $ce = runSql($pdo, "SELECT * FROM equipment WHERE equipment_id=?", [$ei])->fetch() ?: $ce;
    $is_preset = in_array($ce['equipment_name'], ['Laptop', 'Projector', 'Tablet', 'Camera']);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rejalde DIT 3-1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @keyframes luxuryGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body {
            background: linear-gradient(45deg, #eae9e5, #ffffff, #dcdbd5, #f4f4f0);
            background-size: 400% 400%;
            animation: luxuryGradient 15s ease infinite;
            font-family: "Times New Roman", Times, serif;
            color: #111;
        }

        h1, h2, h3, h4, .navbar-brand {
            font-family: "Times New Roman", Times, serif;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .navbar {
            background-color: #050505;
            padding: 1.2rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: #f8f8f8 !important;
            font-size: 1.4rem;
            margin: 0 auto;
        }

        .card-luxury {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.04);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .table {
            --bs-table-bg: transparent;
            margin-top: 1rem;
        }

        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            border-bottom: 2px solid #111;
            padding-bottom: 0.8rem;
            white-space: nowrap;
        }

        .table tbody td {
            vertical-align: middle;
            border-bottom: 1px solid #eaeaea;
            padding: 1rem 0.5rem;
            font-size: 0.9rem;
        }

        .btn-luxury {
            background-color: #050505;
            color: #fff;
            border-radius: 0;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 0.8rem;
            padding: 0.7rem 1rem;
            transition: all 0.3s ease;
            border: 1px solid #050505;
            width: 100%;
            font-family: "Times New Roman", Times, serif;
        }

        .btn-luxury:hover {
            background-color: #fff;
            color: #050505;
        }

        .btn-outline-luxury {
            background-color: transparent;
            color: #111;
            border: 1px solid #111;
            border-radius: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.7rem;
            padding: 0.35rem 0.8rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 0.2rem;
            font-family: "Times New Roman", Times, serif;
        }

        .btn-outline-luxury:hover {
            background-color: #111;
            color: #fff;
        }

        .form-control, .form-select {
            border-radius: 0;
            border: none;
            border-bottom: 1px solid #ccc;
            background: transparent;
            padding: 0.5rem 0;
            font-size: 0.95rem;
            box-shadow: none !important;
            font-family: "Times New Roman", Times, serif;
        }

        .form-control:focus, .form-select:focus {
            border-bottom: 1px solid #111;
            background: transparent;
        }

        .form-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 0;
            margin-top: 1rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
        }

        .form-container {
            background-color: #fdfcfb;
            padding: 2rem;
            border: 1px solid #eee;
            margin-top: auto;
        }

        .table-wrapper {
            flex-grow: 1;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark fixed-top">
    <div class="container-fluid justify-content-center">
        <a class="navbar-brand" href="#">Rejalde, Ivan M. DIT 3-1</a>
    </div>
</nav>

<div class="container-fluid px-4" style="margin-top: 6rem; margin-bottom: 4rem;">
    <div class="row g-4">
        
        <div class="col-xl-6" id="borrower">
            <div class="card-luxury">
                <h2 class="section-title">People</h2>
                <div class="table-wrapper table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Full Name</th>
                                <th>Type</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowers as $b): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['borrower_no']); ?></td>
                                <td><?php echo htmlspecialchars($b['full_name']); ?></td>
                                <td><span style="text-transform: capitalize;"><?php echo htmlspecialchars($b['borrower_type']); ?></span></td>
                                <td class="text-end">
                                    <a class="btn btn-outline-luxury" href="?edit=borrower&id=<?php echo $b['borrower_id']; ?>#borrower">Edit</a>
                                    <a class="btn btn-outline-luxury" href="?entity=borrower&del=<?php echo $b['borrower_id']; ?>" style="color: #900; border-color: #900;">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-container">
                    <h4 class="mb-3 text-center"><?php echo ($ee === 'borrower') ? 'Edit Person' : 'Add Person'; ?></h4>
                    <form method="post" action="index.php">
                        <input type="hidden" name="action" value="borrower">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($ee === 'borrower' ? $ei : ''); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ID Number</label>
                                <input type="text" name="borrower_no" class="form-control" value="<?php echo htmlspecialchars($cb['borrower_no']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($cb['full_name']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Type</label>
                                <select name="borrower_type" class="form-select">
                                    <option value="individual" <?php echo ($cb['borrower_type'] === 'individual') ? 'selected' : ''; ?>>Person</option>
                                    <option value="corporate" <?php echo ($cb['borrower_type'] === 'corporate') ? 'selected' : ''; ?>>Business</option>
                                </select>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-luxury">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6" id="equipment">
            <div class="card-luxury">
                <h2 class="section-title">Things in Inventory</h2>
                <div class="table-wrapper table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item Number</th>
                                <th>Name</th>
                                <th>In Stock</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipments as $e): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($e['property_no']); ?></td>
                                <td><?php echo htmlspecialchars($e['equipment_name']); ?></td>
                                <td><?php echo htmlspecialchars($e['quantity_available']); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-outline-luxury" href="?edit=equipment&id=<?php echo $e['equipment_id']; ?>#equipment">Edit</a>
                                    <a class="btn btn-outline-luxury" href="?entity=equipment&del=<?php echo $e['equipment_id']; ?>" style="color: #900; border-color: #900;">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-container">
                    <h4 class="mb-3 text-center"><?php echo ($ee === 'equipment') ? 'Edit Thing' : 'Add Thing'; ?></h4>
                    <form method="post" action="index.php">
                        <input type="hidden" name="action" value="equipment">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($ee === 'equipment' ? $ei : ''); ?>">
                        
                        <?php 
                        $preset_val = (isset($is_preset) && !$is_preset && $ee === 'equipment') ? 'Others' : $ce['equipment_name']; 
                        $display_custom = ($preset_val === 'Others') ? 'block' : 'none';
                        ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Item Number</label>
                                <input type="text" name="property_no" class="form-control" value="<?php echo htmlspecialchars($ce['property_no']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Name of Thing</label>
                                <select name="equip_preset" class="form-select" onchange="document.getElementById('custom_thing').style.display = (this.value === 'Others') ? 'block' : 'none';">
                                    <option value="Laptop" <?php echo ($preset_val === 'Laptop') ? 'selected' : ''; ?>>Laptop</option>
                                    <option value="Projector" <?php echo ($preset_val === 'Projector') ? 'selected' : ''; ?>>Projector</option>
                                    <option value="Tablet" <?php echo ($preset_val === 'Tablet') ? 'selected' : ''; ?>>Tablet</option>
                                    <option value="Camera" <?php echo ($preset_val === 'Camera') ? 'selected' : ''; ?>>Camera</option>
                                    <option value="Others" <?php echo ($preset_val === 'Others') ? 'selected' : ''; ?>>Others (I will type it)</option>
                                </select>
                                <input type="text" name="equip_custom" id="custom_thing" class="form-control mt-2" style="display: <?php echo $display_custom; ?>;" placeholder="Type the name here" value="<?php echo htmlspecialchars($ce['equipment_name']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">How Many?</label>
                                <input type="number" name="quantity_available" class="form-control" min="0" value="<?php echo htmlspecialchars($ce['quantity_available']); ?>" required>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-luxury">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6" id="transaction">
            <div class="card-luxury">
                <h2 class="section-title">Borrow Records Summary</h2>
                <div class="table-wrapper table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ref ID</th>
                                <th>Name</th>
                                <th>Borrowed</th>
                                <th>Return By</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($txns as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['borrow_id']); ?></td>
                                <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($t['borrow_date']); ?></td>
                                <td><?php echo htmlspecialchars($t['due_date']); ?></td>
                                <td>
                                    <?php if ($t['status'] === 'returned'): ?>
                                        <span style="color: green; text-transform: capitalize;">Returned</span>
                                    <?php else: ?>
                                        <span style="color: orange; text-transform: capitalize;">Not Returned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($t['status'] !== 'returned'): ?>
                                        <a class="btn btn-outline-luxury" href="?return_txn=<?php echo $t['borrow_id']; ?>" style="color: green; border-color: green;">Mark Returned</a>
                                    <?php endif; ?>
                                    <a class="btn btn-outline-luxury" href="?entity=transaction&del=<?php echo $t['borrow_id']; ?>" style="color: #900; border-color: #900;">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6" id="item">
            <div class="card-luxury">
                <h2 class="section-title">Borrowed Things</h2>
                <div class="table-wrapper table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ref ID</th>
                                <th>Name of Thing</th>
                                <th>How Many</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                            <tr>
                                <td>Ref <?php echo htmlspecialchars($it['borrow_id']); ?></td>
                                <td><?php echo htmlspecialchars($it['equipment_name']); ?></td>
                                <td><?php echo htmlspecialchars($it['quantity']); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-outline-luxury" href="?entity=item&del=<?php echo $it['borrow_item_id']; ?>" style="color: #900; border-color: #900;">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-container">
                    <h4 class="mb-3 text-center">Give a Thing to Someone</h4>
                    <form method="post" action="index.php">
                        <input type="hidden" name="action" value="item">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Who is borrowing?</label>
                                <select name="borrower_id" class="form-select" required>
                                    <option value=""></option>
                                    <?php foreach ($borrowers as $b): ?>
                                    <option value="<?php echo $b['borrower_id']; ?>">
                                        <?php echo htmlspecialchars($b['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">What Thing? (Type it)</label>
                                <input list="thing-list" name="equipment_name" class="form-control" placeholder="Type the name..." required>
                                <datalist id="thing-list">
                                    <?php foreach($equipments as $e): ?>
                                        <option value="<?php echo htmlspecialchars($e['equipment_name']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">How Many?</label>
                                <input type="number" name="quantity" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date Borrowed</label>
                                <input type="date" name="borrow_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Return Date</label>
                                <input type="date" name="due_date" class="form-control" required>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-luxury">Confirm Borrowing</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="text-center pb-4" style="font-size: 0.8rem; letter-spacing: 2px; text-transform: uppercase; color: #666; font-family: 'Times New Roman', Times, serif;">
    Maison Rejalde &copy; 2026
</div>

</body>
</html>