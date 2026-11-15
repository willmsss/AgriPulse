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

// Handle Delete Farm Request
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM farm WHERE farm_id = ?");
        $stmt->execute([$deleteId]);
        header("Location: farms.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        header("Location: farms.php?status=delete_error");
        exit();
    }
}

// Handle Add Farm Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_farm'])) {
    $farmerId = trim($_POST['farmer_id']);
    $farmName = trim($_POST['farm_name']);
    $location = trim($_POST['location']);
    $sizeHectares = trim($_POST['size_hectares']);

    if (!empty($farmerId) && !empty($farmName)) {
        try {
            // Flexible insert handling both size_hectares and size column variations
            try {
                $stmt = $pdo->prepare("INSERT INTO farm (farmer_id, farm_name, location, size_hectares) VALUES (?, ?, ?, ?)");
                $stmt->execute([$farmerId, $farmName, $location, $sizeHectares]);
            } catch (PDOException $ex) {
                $stmt = $pdo->prepare("INSERT INTO farm (farmer_id, farm_name, location, size) VALUES (?, ?, ?, ?)");
                $stmt->execute([$farmerId, $farmName, $location, $sizeHectares]);
            }
            header("Location: farms.php?status=added");
            exit();
        } catch (PDOException $e) {
            header("Location: farms.php?status=add_error");
            exit();
        }
    } else {
        header("Location: farms.php?status=empty_error");
        exit();
    }
}

// Handle Update Farm Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_farm'])) {
    $farmId = trim($_POST['farm_id']);
    $farmerId = trim($_POST['farmer_id']);
    $farmName = trim($_POST['farm_name']);
    $location = trim($_POST['location']);
    $sizeHectares = trim($_POST['size_hectares']);

    if (!empty($farmId) && !empty($farmerId) && !empty($farmName)) {
        try {
            try {
                $stmt = $pdo->prepare("UPDATE farm SET farmer_id = ?, farm_name = ?, location = ?, size_hectares = ? WHERE farm_id = ?");
                $stmt->execute([$farmerId, $farmName, $location, $sizeHectares, $farmId]);
            } catch (PDOException $ex) {
                $stmt = $pdo->prepare("UPDATE farm SET farmer_id = ?, farm_name = ?, location = ?, size = ? WHERE farm_id = ?");
                $stmt->execute([$farmerId, $farmName, $location, $sizeHectares, $farmId]);
            }
            header("Location: farms.php?status=updated");
            exit();
        } catch (PDOException $e) {
            header("Location: farms.php?status=update_error");
            exit();
        }
    } else {
        header("Location: farms.php?status=empty_error");
        exit();
    }
}

// Status triggers
$status = $_GET['status'] ?? '';
if ($status === 'added') { $popupMessage = "New farm added successfully!"; $popupType = "success"; }
elseif ($status === 'updated') { $popupMessage = "Farm record updated successfully!"; $popupType = "success"; }
elseif ($status === 'deleted') { $popupMessage = "Farm record deleted successfully!"; $popupType = "success"; }
elseif ($status === 'delete_error') { $popupMessage = "Cannot delete farm: Active planting records are linked to it."; $popupType = "error"; }
elseif ($status === 'add_error' || $status === 'update_error' || $status === 'empty_error') { $popupMessage = "Operation failed. Please check inputs."; $popupType = "error"; }

$search = trim($_GET['search'] ?? '');

try {
    $sql = "SELECT f.*, fr.first_name, fr.last_name FROM farm f JOIN farmer fr ON f.farmer_id = fr.farmer_id WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (f.farm_name ILIKE ? OR f.location ILIKE ? OR fr.first_name ILIKE ? OR fr.last_name ILIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    $sql .= " ORDER BY f.farm_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $farms = $stmt->fetchAll();
    $totalFarms = count($farms);
} catch (PDOException $e) {
    $farms = []; $totalFarms = 0;
}

try {
    $farmersList = $pdo->query("SELECT farmer_id, first_name, last_name FROM farmer ORDER BY last_name ASC")->fetchAll();
} catch (PDOException $e) {
    $farmersList = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farms Directory - AgriPulse</title>
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
                <li class="nav-item"><a class="nav-link active px-3 py-2" href="farms.php"><i class="bi bi-geo-alt me-1"></i> Farms</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="crops.php"><i class="bi bi-flower1 me-1"></i> Crops</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="plantings.php"><i class="bi bi-calendar-check me-1"></i> Planting Records</a></li>
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
                    <h1 class="h3 fw-bold text-dark mb-0">Farms Directory</h1>
                    <span class="badge bg-success text-white px-3 py-1.5 rounded-pill small fw-semibold"><?= $totalFarms ?> Registered</span>
                </div>
                <p class="text-muted small mb-0">Manage registered farm locations, owners, and land areas.</p>
            </div>
            <div>
                <button class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addFarmModal">
                    <i class="bi bi-plus-circle-fill fs-5"></i><span>Add New Farm</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
        <form method="GET" action="farms.php" class="row g-3 align-items-center">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by farm name, location, or owner name...">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="farms.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                            <th class="py-3">Farm Name</th>
                            <th class="py-3">Owner / Farmer</th>
                            <th class="py-3">Location</th>
                            <th class="py-3">Size (Hectares)</th>
                            <th class="py-3 pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($farms)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($farms as $f): ?>
                                <?php 
                                    // Dynamically find the size value from whatever column name exists in DB
                                    $sizeVal = 0;
                                    foreach ($f as $key => $val) {
                                        $lk = strtolower($key);
                                        if ($lk === 'size' || $lk === 'size_hectares' || $lk === 'hectares' || $lk === 'area' || $lk === 'farm_size') {
                                            $sizeVal = $val;
                                            break;
                                        }
                                    }
                                    if (empty($sizeVal)) {
                                        foreach ($f as $key => $val) {
                                            $lk = strtolower($key);
                                            if ((strpos($lk, 'size') !== false || strpos($lk, 'hectare') !== false) && $lk !== 'farm_id' && $lk !== 'farmer_id') {
                                                $sizeVal = $val;
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-light text-secondary border px-2 py-1 fw-bold">#<?= $counter++ ?></span></td>
                                    <td class="fw-bold text-dark"><i class="bi bi-geo-alt text-success me-1"></i><?= htmlspecialchars($f['farm_name']) ?></td>
                                    <td><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($f['location'] ?? 'N/A') ?></td>
                                    <td class="fw-semibold text-primary"><?= number_format((float)$sizeVal, 2) ?> ha</td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group" role="group">
                                            <!-- View Button -->
                                            <button type="button" class="btn btn-sm btn-outline-info px-2 py-1" title="View Details"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal"
                                                    data-name="<?= htmlspecialchars($f['farm_name'], ENT_QUOTES) ?>"
                                                    data-owner="<?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name'], ENT_QUOTES) ?>"
                                                    data-location="<?= htmlspecialchars($f['location'] ?? 'N/A', ENT_QUOTES) ?>"
                                                    data-size="<?= number_format((float)$sizeVal, 2) ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1" title="Edit Record"
                                                    data-bs-toggle="modal" data-bs-target="#editModal"
                                                    data-id="<?= $f['farm_id'] ?>" 
                                                    data-farmer-id="<?= $f['farmer_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($f['farm_name'], ENT_QUOTES) ?>"
                                                    data-location="<?= htmlspecialchars($f['location'] ?? '', ENT_QUOTES) ?>" 
                                                    data-size="<?= $sizeVal ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Record"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-id="<?= $f['farm_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($f['farm_name'], ENT_QUOTES) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-5"><p class="mb-0 fw-semibold">No farms found.</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Farm Modal -->
<div class="modal fade" id="addFarmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-geo-alt-fill me-2"></i> Register New Farm</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Owner Farmer</label>
                        <select class="form-select form-select-lg fs-6" name="farmer_id" required>
                            <option value="" selected disabled>Select farmer...</option>
                            <?php foreach ($farmersList as $fr): ?>
                                <option value="<?= $fr['farmer_id'] ?>"><?= htmlspecialchars($fr['first_name'] . ' ' . $fr['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Farm Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="farm_name" required placeholder="e.g. Green Valley Farm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Location / Address</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="location" placeholder="e.g. Toledo City">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Size (Hectares)</label>
                        <input type="number" step="0.01" class="form-control form-control-lg fs-6" name="size_hectares" placeholder="e.g. 5.50" required>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_farm" class="btn btn-primary px-4">Save Farm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Farm Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Farm Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Farm Name</span>
                        <span id="viewFarmName" class="fw-bold text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Owner / Farmer</span>
                        <span id="viewOwner" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Location</span>
                        <span id="viewLocation" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Land Size</span>
                        <span id="viewSize" class="text-primary fw-bold"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light p-3 justify-content-center">
                <button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Farm Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <input type="hidden" name="farm_id" id="editFarmId">
                <div class="modal-header bg-warning text-dark p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Farm Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Owner Farmer</label>
                        <select class="form-select form-select-lg fs-6" name="farmer_id" id="editFarmerId" required>
                            <?php foreach ($farmersList as $fr): ?>
                                <option value="<?= $fr['farmer_id'] ?>"><?= htmlspecialchars($fr['first_name'] . ' ' . $fr['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Farm Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="farm_name" id="editFarmName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Location</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="location" id="editLocation">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Size (Hectares)</label>
                        <input type="number" step="0.01" class="form-control form-control-lg fs-6" name="size_hectares" id="editSizeHectares" required>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_farm" class="btn btn-warning px-4 fw-semibold">Save Changes</button>
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
                <p class="text-muted mb-1">Are you sure you want to delete farm:</p>
                <h4 id="deleteFarmName" class="fw-bold text-dark mb-2"></h4>
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
            document.getElementById('viewFarmName').textContent = button.getAttribute('data-name');
            document.getElementById('viewOwner').textContent = button.getAttribute('data-owner');
            document.getElementById('viewLocation').textContent = button.getAttribute('data-location');
            document.getElementById('viewSize').textContent = button.getAttribute('data-size') + ' hectares';
        });
    }

    // Handle Edit Modal Data Binding
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editFarmId').value = button.getAttribute('data-id');
            document.getElementById('editFarmerId').value = button.getAttribute('data-farmer-id');
            document.getElementById('editFarmName').value = button.getAttribute('data-name');
            document.getElementById('editLocation').value = button.getAttribute('data-location');
            document.getElementById('editSizeHectares').value = button.getAttribute('data-size');
        });
    }

    <?php if (!empty($popupMessage)): ?>
        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
    <?php endif; ?>
</script>
</body>
</html>
