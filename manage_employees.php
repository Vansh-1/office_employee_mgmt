<?php
session_start();
require 'config.php';

// only admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Helper: flash message
function flash($key, $msg = null) {
    if ($msg === null) {
        if (isset($_SESSION['_flash'][$key])) {
            $m = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $m;
        }
        return null;
    } else {
        $_SESSION['_flash'][$key] = $msg;
    }
}

// Inspect employees table columns to adapt insert/update
$empCols = [];
try {
    $colsStmt = $conn->query("SHOW COLUMNS FROM employees");
    $empCols = $colsStmt->fetchAll(PDO::FETCH_COLUMN); // first column = Field
} catch (Throwable $e) {
    // leave $empCols empty if table not standard
}
$hasDeptId   = in_array('department_id', $empCols);
$hasDeptText = in_array('department', $empCols);
$hasEmail    = in_array('email', $empCols);
$hasPhone    = in_array('phone', $empCols);
$hasPosition = in_array('position', $empCols);
$hasDoj      = in_array('doj', $empCols);

// Load departments (if exists)
$depts = [];
try {
    $deptsStmt = $conn->query("SELECT id, name FROM departments ORDER BY name");
    $depts = $deptsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $depts = [];
}

// Handle POST actions: add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $values = [];
        $fields = [];
        $placeholders = [];

        // name (required)
        if ($name === '') {
            flash('error', 'Name is required.');
            header("Location: manage_employees.php");
            exit;
        }
        $fields[] = 'name'; $placeholders[] = '?'; $values[] = $name;

        if ($hasEmail)    { $fields[]='email';     $placeholders[]='?'; $values[] = trim($_POST['email'] ?? '') ?: null; }
        if ($hasPhone)    { $fields[]='phone';     $placeholders[]='?'; $values[] = trim($_POST['phone'] ?? '') ?: null; }
        if ($hasPosition) { $fields[]='position';  $placeholders[]='?'; $values[] = trim($_POST['position'] ?? '') ?: null; }
        if ($hasDoj)      { $fields[]='doj';       $placeholders[]='?'; $values[] = trim($_POST['doj'] ?? '') ?: null; }

        if ($hasDeptId) {
            $deptId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $fields[]='department_id'; $placeholders[]='?'; $values[] = $deptId;
        } elseif ($hasDeptText) {
            $deptText = trim($_POST['department_text'] ?? '') ?: null;
            $fields[]='department'; $placeholders[]='?'; $values[] = $deptText;
        }

        // Build SQL
        $sql = "INSERT INTO employees (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            flash('success','Employee added successfully.');
        } catch (Throwable $e) {
            flash('error','Failed to add employee: ' . $e->getMessage());
        }

        header("Location: manage_employees.php");
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error','Invalid employee id for update.');
            header("Location: manage_employees.php");
            exit;
        }

        $updates = [];
        $values = [];

        if (isset($_POST['name'])) {
            $updates[] = 'name = ?'; $values[] = trim($_POST['name']);
        }
        if ($hasEmail)    { $updates[] = 'email = ?';     $values[] = trim($_POST['email'] ?? '') ?: null; }
        if ($hasPhone)    { $updates[] = 'phone = ?';     $values[] = trim($_POST['phone'] ?? '') ?: null; }
        if ($hasPosition) { $updates[] = 'position = ?';  $values[] = trim($_POST['position'] ?? '') ?: null; }
        if ($hasDoj)      { $updates[] = 'doj = ?';       $values[] = trim($_POST['doj'] ?? '') ?: null; }

        if ($hasDeptId) {
            $updates[] = 'department_id = ?'; $values[] = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        } elseif ($hasDeptText) {
            $updates[] = 'department = ?'; $values[] = trim($_POST['department_text'] ?? '') ?: null;
        }

        if (count($updates) === 0) {
            flash('error','Nothing to update.');
            header("Location: manage_employees.php");
            exit;
        }

        $values[] = $id;
        $sql = "UPDATE employees SET " . implode(', ', $updates) . " WHERE id = ?";
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);
            flash('success','Employee updated successfully.');
        } catch (Throwable $e) {
            flash('error','Failed to update employee: ' . $e->getMessage());
        }

        header("Location: manage_employees.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$id]);
                flash('success','Employee deleted.');
            } catch (Throwable $e) {
                flash('error','Failed to delete: ' . $e->getMessage());
            }
        } else {
            flash('error','Invalid id for delete.');
        }
        header("Location: manage_employees.php");
        exit;
    }
}

// Fetch employees list with department name if possible
$employees = [];
try {
    if ($hasDeptId) {
        $sql = "SELECT e.id, e.name, e.email, e.phone, e.position, e.doj, d.name AS dept_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                ORDER BY e.id DESC";
    } elseif ($hasDeptText) {
        $sql = "SELECT id, name, email, phone, position, doj, department AS dept_name FROM employees ORDER BY id DESC";
    } else {
        // fallback
        $sql = "SELECT id, name, email, phone, position, doj FROM employees ORDER BY id DESC";
    }
    $employees = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    flash('error','Failed to fetch employees: ' . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Employees - Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f4f6f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial; }
    .container { max-width: 1100px; padding-top: 30px; }
    .card-panel { border-radius: 14px; box-shadow: 0 10px 28px rgba(18, 38, 63, .08); overflow: hidden; }
    .card-header-grad { background: linear-gradient(135deg,#4e73df,#1cc88a); color:#fff; padding: 18px 22px; font-weight:700; display:flex; align-items:center; justify-content:space-between; }
    .btn-primary-gradient { background: linear-gradient(135deg,#4e73df,#1cc88a); border: none; }
    .btn-primary-gradient:focus, .btn-primary-gradient:hover { opacity:.95; }
    .table thead th { background:#0d6efd; color:#fff; vertical-align:middle; text-align:center; }
    .table tbody td { vertical-align: middle; text-align:center; }
    .action-btns .btn { margin-right:6px; }
    .flash { position:relative; margin-bottom:12px; }
    .modal .form-control { border-radius:8px; }
  </style>
</head>
<body>
<div class="container">
  <?php if ($m = flash('success')): ?>
    <div class="alert alert-success flash"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash('error')): ?>
    <div class="alert alert-danger flash"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>

  <div class="card card-panel">
    <div class="card-header-grad">
      <div>
        <i class="bi bi-people-fill me-2"></i> Manage Employees
      </div>
      <div>
        <a href="admin_dashboard.php" class="btn btn-sm btn-light me-2"><i class="bi bi-arrow-left"></i> Back</a>
        <button class="btn btn-sm btn-primary-gradient" id="btnAddEmployee" data-bs-toggle="modal" data-bs-target="#employeeModal">
          <i class="bi bi-person-plus"></i> Add Employee
        </button>
      </div>
    </div>

    <div class="card-body p-3">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th style="width:70px">ID</th>
              <th>Name</th>
              <?php if ($hasDeptId || $hasDeptText): ?><th>Department</th><?php endif; ?>
              <?php if ($hasPhone): ?><th>Phone</th><?php endif; ?>
              <?php if ($hasEmail): ?><th>Email</th><?php endif; ?>
              <?php if ($hasPosition): ?><th>Position</th><?php endif; ?>
              <?php if ($hasDoj): ?><th>DOJ</th><?php endif; ?>
              <th style="width:150px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($employees)): ?>
              <?php foreach ($employees as $emp): ?>
                <tr>
                  <td><?= (int)$emp['id'] ?></td>
                  <td><?= htmlspecialchars($emp['name']) ?></td>
                  <?php if ($hasDeptId || $hasDeptText): ?>
                    <td><?= htmlspecialchars($emp['dept_name'] ?? '') ?></td>
                  <?php endif; ?>
                  <?php if ($hasPhone): ?><td><?= htmlspecialchars($emp['phone'] ?? '') ?></td><?php endif; ?>
                  <?php if ($hasEmail): ?><td><?= htmlspecialchars($emp['email'] ?? '') ?></td><?php endif; ?>
                  <?php if ($hasPosition): ?><td><?= htmlspecialchars($emp['position'] ?? '') ?></td><?php endif; ?>
                  <?php if ($hasDoj): ?><td><?= htmlspecialchars($emp['doj'] ?? '') ?></td><?php endif; ?>
                  <td class="action-btns">
                    <button class="btn btn-sm btn-outline-primary btn-edit"
                            data-id="<?= (int)$emp['id'] ?>"
                            data-name="<?= htmlspecialchars($emp['name'], ENT_QUOTES) ?>"
                            data-dept="<?= htmlspecialchars($emp['dept_name'] ?? '', ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($emp['email'] ?? '', ENT_QUOTES) ?>"
                            data-phone="<?= htmlspecialchars($emp['phone'] ?? '', ENT_QUOTES) ?>"
                            data-position="<?= htmlspecialchars($emp['position'] ?? '', ENT_QUOTES) ?>"
                            data-doj="<?= htmlspecialchars($emp['doj'] ?? '', ENT_QUOTES) ?>"
                            data-bs-toggle="modal" data-bs-target="#employeeModal"
                    >
                      <i class="bi bi-pencil"></i> Edit
                    </button>

                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this employee?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$emp['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center text-muted py-4">No employees found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Employee Modal (Add / Edit) -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" id="empForm">
      <div class="modal-header">
        <h5 class="modal-title" id="empModalTitle">Add Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="empId" value="">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name *</label>
            <input type="text" name="name" id="empName" class="form-control" required>
          </div>

          <?php if ($hasEmail): ?>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" id="empEmail" class="form-control">
            </div>
          <?php endif; ?>

          <?php if ($hasPhone): ?>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" id="empPhone" class="form-control">
            </div>
          <?php endif; ?>

          <?php if ($hasDeptId): ?>
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <select name="department_id" id="empDeptId" class="form-select">
                <option value="">-- Select Department --</option>
                <?php foreach ($depts as $d): ?>
                  <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php elseif ($hasDeptText): ?>
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <input type="text" name="department_text" id="empDeptText" class="form-control" placeholder="e.g., HR, Frontend, Accounts">
            </div>
          <?php endif; ?>

          <?php if ($hasPosition): ?>
            <div class="col-md-6">
              <label class="form-label">Position</label>
              <input type="text" name="position" id="empPosition" class="form-control">
            </div>
          <?php endif; ?>

          <?php if ($hasDoj): ?>
            <div class="col-md-6">
              <label class="form-label">Date of Joining</label>
              <input type="date" name="doj" id="empDoj" class="form-control">
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary-gradient" id="empSaveBtn">Save</button>
      </div>
    </form>
  </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Open Add Employee modal
  document.getElementById('btnAddEmployee').addEventListener('click', function(){
      document.getElementById('empModalTitle').textContent = 'Add Employee';
      document.getElementById('formAction').value = 'add';
      document.getElementById('empId').value = '';
      // clear fields
      ['empName','empEmail','empPhone','empPosition','empDoj','empDeptText'].forEach(function(id){
          var el = document.getElementById(id);
          if (el) el.value = '';
      });
      // reset dept select
      var dsel = document.getElementById('empDeptId');
      if (dsel) dsel.selectedIndex = 0;
  });

  // Fill modal on Edit
  document.querySelectorAll('.btn-edit').forEach(function(btn){
      btn.addEventListener('click', function(){
          var id = this.dataset.id;
          document.getElementById('empModalTitle').textContent = 'Edit Employee';
          document.getElementById('formAction').value = 'update';
          document.getElementById('empId').value = id;
          // set fields if present
          if (document.getElementById('empName')) document.getElementById('empName').value = this.dataset.name || '';
          if (document.getElementById('empEmail')) document.getElementById('empEmail').value = this.dataset.email || '';
          if (document.getElementById('empPhone')) document.getElementById('empPhone').value = this.dataset.phone || '';
          if (document.getElementById('empPosition')) document.getElementById('empPosition').value = this.dataset.position || '';
          if (document.getElementById('empDoj')) document.getElementById('empDoj').value = this.dataset.doj || '';
          // department: try id select by matching text (we store dept name in data-dept)
          var deptText = this.dataset.dept || '';
          var deptSelect = document.getElementById('empDeptId');
          if (deptSelect) {
              // find option with matching text
              var found = false;
              for (var i=0;i<deptSelect.options.length;i++){
                  if (deptSelect.options[i].text === deptText) { deptSelect.selectedIndex = i; found = true; break; }
              }
              if (!found) deptSelect.selectedIndex = 0;
          }
          var deptInput = document.getElementById('empDeptText');
          if (deptInput) deptInput.value = deptText || '';
      });
  });
</script>
</body>
</html>
