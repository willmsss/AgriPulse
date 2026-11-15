<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/Database.php';
requireLogin();

$pdo = Database::connect();
$popupMessage = '';
$popupType = '';

if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM planting WHERE planting_id = ?");
        $stmt->execute([$deleteId]);
        header("Location: plantings.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        header("Location: plantings.php?status=delete_error");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_planting'])) {
    $farmId = trim($_POST['farm_id']);
    $cropId = trim($_POST['crop_id']);
    $plantingDate = trim($_POST['planting_date']);
    $statusVal = trim($_POST['status']);

    if (!empty($farmId) && !empty($cropId) && !empty($plantingDate)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO planting (farm_id, crop_id, planting_date, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$farmId, $cropId, $plantingDate, $statusVal]);
            header("Location: plantings.php?status=added");
            exit();
        } catch (PDOException $e) {
            header("Location: plantings.php?status=add_error");
            exit();
        }
    } else {
        header("Location: plantings.php?status=empty_error");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_planting'])) {
    $plantingId = trim($_POST['planting_id']);
    $farmId = trim($_POST['farm_id']);
    $cropId = trim($_POST['crop_id']);
    $plantingDate = trim($_POST['planting_date']);
    $statusVal = trim($_POST['status']);

    if (!empty($plantingId) && !empty($farmId) && !empty($cropId)) {
        try {
            $stmt = $pdo->prepare("UPDATE planting SET farm_id = ?, crop_id = ?, planting_date = ?, status = ? WHERE planting_id = ?");
            $stmt->execute([$farmId, $cropId, $plantingDate, $statusVal, $plantingId]);
            header("Location: plantings.php?status=updated");
            exit();
        } catch (PDOException $e) {
            header("Location: plantings.php?status=update_error");
            exit();
        }
    } else {
        header("Location: plantings.php?status=empty_error");
        exit();
    }
}

$statusUrl = $_GET['status'] ?? '';
if ($statusUrl === 'added') { $popupMessage = "Planting record added successfully!"; $popupType = "success"; }
elseif ($statusUrl === 'updated') { $popupMessage = "Planting record updated successfully!"; $popupType = "success"; }
elseif ($statusUrl === 'deleted') { $popupMessage = "Planting record deleted successfully!"; $popupType = "success"; }
elseif ($statusUrl === 'delete_error') { $popupMessage = "Cannot delete: Harvest records linked to this planting."; $popupType = "error"; }
elseif ($statusUrl === 'add_error' || $statusUrl === 'update_error' || $statusUrl === 'empty_error') { $popupMessage = "Operation failed."; $popupType = "error"; }

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status_filter'] ?? '');

try {
    $sql = "SELECT p.*, f.farm_name, c.crop_name, c.variety FROM planting p JOIN farm f ON p.farm_id = f.farm_id JOIN crop c ON p.crop_id = c.crop_id WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (f.farm_name ILIKE ? OR c.crop_name ILIKE ? OR c.variety ILIKE ? OR p.status ILIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    if (!empty($statusFilter)) {
        $sql .= " AND p.status = ?";
        array_push($params, $statusFilter);
    }
    $sql .= " ORDER BY p.planting_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $plantings = $stmt->fetchAll();
    $totalPlantings = count($plantings);
} catch (PDOException $e) {
    $plantings = []; $totalPlantings = 0;
}

try {
    $farmsList = $pdo->query("SELECT farm_id, farm_name FROM farm ORDER BY farm_name ASC")->fetchAll();
    $cropsList = $pdo->query("SELECT crop_id, crop_name, variety FROM crop ORDER BY crop_name ASC")->fetchAll();
} catch (PDOException $e) {
    $farmsList = []; $cropsList = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planting Records - AgriPulse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 py-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="../dashboard.php">
            <i class="bi bi-flower1 fs-4"></i><span>AgriPulse</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="topNavbarNav">
            <ul class="navbar-nav align-items-center gap-1">
                <li class="nav-item"><a class="nav-link px-3 py-2" href="../dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="farmers.php"><i class="bi bi-people me-1"></i> Farmers</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="farms.php"><i class="bi bi-geo-alt me-1"></i> Farms</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="crops.php"><i class="bi bi-flower1 me-1"></i> Crops</a></li>
                <li class="nav-item"><a class="nav-link active px-3 py-2" href="plantings.php"><i class="bi bi-calendar-check me-1"></i> Planting Records</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="harvests.php"><i class="bi bi-basket me-1"></i> Harvest Records</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="../reports.php"><i class="bi bi-file-earmark-bar-graph me-1"></i> Reports</a></li>
                <li class="nav-item ms-lg-3"><a href="../logout.php" class="btn btn-outline-light btn-sm px-3 py-2 w-100"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="card card-header-fancy border-0 p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h3 fw-bold text-dark mb-0">Planting Records Directory</h1>
                    <span class="badge bg-info text-dark px-3 py-1.5 rounded-pill small fw-semibold"><?= $totalPlantings ?> Total Records</span>
                </div>
                <p class="text-muted small mb-0">Monitor active crop plantings, dates, and growth status.</p>
            </div>
            <div>
                <button class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addPlantingModal">
                    <i class="bi bi-plus-circle-fill fs-5"></i><span>Add Planting Record</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
        <form method="GET" action="plantings.php" class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by farm, crop, or status...">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="status_filter" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Planted" <?= $statusFilter === 'Planted' ? 'selected' : '' ?>>Planted</option>
                    <option value="Growing" <?= $statusFilter === 'Growing' ? 'selected' : '' ?>>Growing</option>
                    <option value="Harvested" <?= $statusFilter === 'Harvested' ? 'selected' : '' ?>>Harvested</option>
                    <option value="Failed" <?= $statusFilter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="plantings.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card table-card border-0 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="py-3 ps-4">No.</th>
                            <th class="py-3">Farm</th>
                            <th class="py-3">Crop & Variety</th>
                            <th class="py-3">Planting Date</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($plantings)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($plantings as $p): ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-light text-secondary border px-2 py-1 fw-bold">#<?= $counter++ ?></span></td>
                                    <td class="fw-bold text-dark"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($p['farm_name']) ?></td>
                                    <td>
                                        <span class="text-dark fw-semibold"><?= htmlspecialchars($p['crop_name']) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($p['variety'] ?? '') ?></small>
                                    </td>
                                    <td class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($p['planting_date']) ?></td>
                                    <td>
                                        <?php 
                                            $badgeBg = 'secondary';
                                            if ($p['status'] === 'Harvested') $badgeBg = 'success';
                                            elseif ($p['status'] === 'Growing') $badgeBg = 'info';
                                            elseif ($p['status'] === 'Planted') $badgeBg = 'primary';
                                            elseif ($p['status'] === 'Failed') $badgeBg = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $badgeBg ?> px-2 py-1"><?= htmlspecialchars($p['status']) ?></span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group" role="group">
                                            <!-- View Button -->
                                            <button type="button" class="btn btn-sm btn-outline-info px-2 py-1" title="View Details"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal"
                                                    data-farm="<?= htmlspecialchars($p['farm_name'], ENT_QUOTES) ?>"
                                                    data-crop="<?= htmlspecialchars($p['crop_name'] . (!empty($p['variety']) ? ' (' . $p['variety'] . ')' : ''), ENT_QUOTES) ?>"
                                                    data-date="<?= htmlspecialchars($p['planting_date'], ENT_QUOTES) ?>"
                                                    data-status="<?= htmlspecialchars($p['status'], ENT_QUOTES) ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1" title="Edit Record" data-bs-toggle="modal" data-bs-target="#editModal"
                                                    data-id="<?= $p['planting_id'] ?>" data-farm-id="<?= $p['farm_id'] ?>" data-crop-id="<?= $p['crop_id'] ?>"
                                                    data-date="<?= htmlspecialchars($p['planting_date'], ENT_QUOTES) ?>" data-status="<?= htmlspecialchars($p['status'], ENT_QUOTES) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Record" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-id="<?= $p['planting_id'] ?>" data-name="<?= htmlspecialchars($p['farm_name'] . ' - ' . $p['crop_name'], ENT_QUOTES) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-5"><p class="mb-0 fw-semibold">No planting records found.</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Planting Modal -->
<div class="modal fade" id="addPlantingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2"></i> Register New Planting</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Farm</label>
                        <select class="form-select form-select-lg fs-6" name="farm_id" required>
                            <option value="" selected disabled>Select farm...</option>
                            <?php foreach ($farmsList as $f): ?>
                                <option value="<?= $f['farm_id'] ?>"><?= htmlspecialchars($f['farm_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Crop</label>
                        <select class="form-select form-select-lg fs-6" name="crop_id" required>
                            <option value="" selected disabled>Select crop...</option>
                            <?php foreach ($cropsList as $c): ?>
                                <option value="<?= $c['crop_id'] ?>"><?= htmlspecialchars($c['crop_name'] . ' (' . ($c['variety'] ?? 'N/A') . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Planting Date</label>
                        <input type="date" class="form-control form-control-lg fs-6" name="planting_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Status</label>
                        <select class="form-select form-select-lg fs-6" name="status">
                            <option value="Planted">Planted</option>
                            <option value="Growing" selected>Growing</option>
                            <option value="Harvested">Harvested</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_planting" class="btn btn-primary px-4">Save Planting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Planting Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Planting Record Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Farm</span>
                        <span id="viewFarm" class="fw-bold text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Crop & Variety</span>
                        <span id="viewCrop" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Planting Date</span>
                        <span id="viewDate" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Status</span>
                        <span id="viewStatus" class="badge"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light p-3 justify-content-center">
                <button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Planting Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <input type="hidden" name="planting_id" id="editPlantingId">
                <div class="modal-header bg-warning text-dark p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Planting Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Farm</label>
                        <select class="form-select form-select-lg fs-6" name="farm_id" id="editFarmId" required>
                            <?php foreach ($farmsList as $f): ?>
                                <option value="<?= $f['farm_id'] ?>"><?= htmlspecialchars($f['farm_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Crop</label>
                        <select class="form-select form-select-lg fs-6" name="crop_id" id="editCropId" required>
                            <?php foreach ($cropsList as $c): ?>
                                <option value="<?= $c['crop_id'] ?>"><?= htmlspecialchars($c['crop_name'] . ' (' . ($c['variety'] ?? 'N/A') . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Planting Date</label>
                        <input type="date" class="form-control form-control-lg fs-6" name="planting_date" id="editPlantingDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Status</label>
                        <select class="form-select form-select-lg fs-6" name="status" id="editStatus">
                            <option value="Planted">Planted</option>
                            <option value="Growing">Growing</option>
                            <option value="Harvested">Harvested</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_planting" class="btn btn-warning px-4 fw-semibold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger fs-1"><i class="bi bi-trash3"></i></div>
                <p class="text-muted mb-1">Are you sure you want to delete planting record:</p>
                <h4 id="deletePlantingName" class="fw-bold text-dark mb-2"></h4>
                <p class="text-danger small mb-0 fw-semibold">This action is permanent.</p>
            </div>
            <div class="modal-footer bg-light p-3 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <a id="confirmDeleteBtn" href="#" class="btn btn-danger px-4">Yes, Delete</a>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header <?= $popupType === 'success' ? 'bg-success' : 'bg-danger' ?> text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi <?= $popupType === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> Notice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center"><p class="fs-5 text-dark mb-0"><?= htmlspecialchars($popupMessage) ?></p></div>
            <div class="modal-footer bg-light p-3 justify-content-center"><button type="button" class="btn <?= $popupType === 'success' ? 'btn-success' : 'btn-danger' ?> px-5 fw-semibold" data-bs-dismiss="modal">OK</button></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
<script>
    // Handle View Modal Data Binding
    const viewModal = document.getElementById('viewModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('viewFarm').textContent = button.getAttribute('data-farm');
            document.getElementById('viewCrop').textContent = button.getAttribute('data-crop');
            document.getElementById('viewDate').textContent = button.getAttribute('data-date');
            
            const status = button.getAttribute('data-status');
            const statusBadge = document.getElementById('viewStatus');
            statusBadge.textContent = status;
            statusBadge.className = 'badge px-2 py-1';
            if (status === 'Harvested') statusBadge.classList.add('bg-success');
            else if (status === 'Growing') statusBadge.classList.add('bg-info');
            else if (status === 'Planted') statusBadge.classList.add('bg-primary');
            else if (status === 'Failed') statusBadge.classList.add('bg-danger');
            else statusBadge.classList.add('bg-secondary');
        });
    }

    // Handle Edit Modal Data Binding
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editPlantingId').value = button.getAttribute('data-id');
            document.getElementById('editFarmId').value = button.getAttribute('data-farm-id');
            document.getElementById('editCropId').value = button.getAttribute('data-crop-id');
            document.getElementById('editPlantingDate').value = button.getAttribute('data-date');
            document.getElementById('editStatus').value = button.getAttribute('data-status');
        });
    }

    <?php if (!empty($popupMessage)): ?>
        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
    <?php endif; ?>
</script>
</body>
</html>
