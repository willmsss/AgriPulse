<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/Database.php';
requireLogin();

$pdo = Database::connect();
$popupMessage = '';
$popupType = ''; // 'success' or 'error'

// Handle Delete Harvest Request
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM harvest WHERE harvest_id = ?");
        $stmt->execute([$deleteId]);
        header("Location: harvests.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        header("Location: harvests.php?status=delete_error");
        exit();
    }
}

// Handle Add Harvest Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_harvest'])) {
    $plantingId = trim($_POST['planting_id']);
    $harvestDate = trim($_POST['harvest_date']);
    $quantityKg = trim($_POST['quantity_kg']);
    $sellingPricePerKg = trim($_POST['selling_price_per_kg']);
    $qualityGrade = trim($_POST['quality_grade']);
    $remarks = trim($_POST['remarks']);

    if (!empty($plantingId) && !empty($harvestDate) && !empty($quantityKg) && !empty($sellingPricePerKg)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade, remarks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$plantingId, $harvestDate, $quantityKg, $sellingPricePerKg, $qualityGrade, $remarks]);
            header("Location: harvests.php?status=added");
            exit();
        } catch (PDOException $e) {
            header("Location: harvests.php?status=add_error");
            exit();
        }
    } else {
        header("Location: harvests.php?status=empty_error");
        exit();
    }
}

// Handle Update Harvest Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_harvest'])) {
    $harvestId = trim($_POST['harvest_id']);
    $plantingId = trim($_POST['planting_id']);
    $harvestDate = trim($_POST['harvest_date']);
    $quantityKg = trim($_POST['quantity_kg']);
    $sellingPricePerKg = trim($_POST['selling_price_per_kg']);
    $qualityGrade = trim($_POST['quality_grade']);
    $remarks = trim($_POST['remarks']);

    if (!empty($harvestId) && !empty($plantingId) && !empty($harvestDate) && !empty($quantityKg) && !empty($sellingPricePerKg)) {
        try {
            $stmt = $pdo->prepare("UPDATE harvest SET planting_id = ?, harvest_date = ?, quantity_kg = ?, selling_price_per_kg = ?, quality_grade = ?, remarks = ? WHERE harvest_id = ?");
            $stmt->execute([$plantingId, $harvestDate, $quantityKg, $sellingPricePerKg, $qualityGrade, $remarks, $harvestId]);
            header("Location: harvests.php?status=updated");
            exit();
        } catch (PDOException $e) {
            header("Location: harvests.php?status=update_error");
            exit();
        }
    } else {
        header("Location: harvests.php?status=empty_error");
        exit();
    }
}

// Check URL status for pop-up triggers
$statusUrl = $_GET['status'] ?? '';
if ($statusUrl === 'added') {
    $popupMessage = "New harvest record added successfully!";
    $popupType = "success";
} elseif ($statusUrl === 'updated') {
    $popupMessage = "Harvest record updated successfully!";
    $popupType = "success";
} elseif ($statusUrl === 'deleted') {
    $popupMessage = "Harvest record deleted successfully!";
    $popupType = "success";
} elseif ($statusUrl === 'delete_error') {
    $popupMessage = "Cannot delete harvest record due to a database constraint error.";
    $popupType = "error";
} elseif ($statusUrl === 'add_error' || $statusUrl === 'update_error' || $statusUrl === 'empty_error') {
    $popupMessage = "Operation failed. Please check your inputs and try again.";
    $popupType = "error";
}

// Search and Filter parameters
$search = trim($_GET['search'] ?? '');
$gradeFilter = trim($_GET['grade_filter'] ?? '');

// Build dynamic SQL query for search and filter with JOINs
try {
    $sql = "
        SELECT h.*, p.planting_date, f.farm_name, c.crop_name, c.variety 
        FROM harvest h
        JOIN planting p ON h.planting_id = p.planting_id
        JOIN farm f ON p.farm_id = f.farm_id
        JOIN crop c ON p.crop_id = c.crop_id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (f.farm_name ILIKE ? OR c.crop_name ILIKE ? OR c.variety ILIKE ? OR h.quality_grade ILIKE ? OR h.remarks ILIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if (!empty($gradeFilter)) {
        $sql .= " AND h.quality_grade = ?";
        array_push($params, $gradeFilter);
    }

    $sql .= " ORDER BY h.harvest_id ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $harvests = $stmt->fetchAll();
    $totalHarvests = count($harvests);
} catch (PDOException $e) {
    $harvests = [];
    $totalHarvests = 0;
}

// Fetch plantings list for dropdowns with farm & crop info
try {
    $plantingsList = $pdo->query("
        SELECT p.planting_id, f.farm_name, c.crop_name, p.planting_date 
        FROM planting p
        JOIN farm f ON p.farm_id = f.farm_id
        JOIN crop c ON p.crop_id = c.crop_id
        ORDER BY p.planting_id DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $plantingsList = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harvest Records - AgriPulse</title>
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
                <li class="nav-item"><a class="nav-link px-3 py-2" href="plantings.php"><i class="bi bi-calendar-check me-1"></i> Planting Records</a></li>
                <li class="nav-item"><a class="nav-link active px-3 py-2" href="harvests.php"><i class="bi bi-basket me-1"></i> Harvest Records</a></li>
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
                    <h1 class="h3 fw-bold text-dark mb-0">Harvest Records Directory</h1>
                    <span class="badge bg-primary text-white px-3 py-1.5 rounded-pill small fw-semibold"><?= $totalHarvests ?> Total Harvests</span>
                </div>
                <p class="text-muted small mb-0">Manage crop harvest yields, pricing per kilogram, quality grades, and remarks.</p>
            </div>
            <div>
                <button class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addHarvestModal">
                    <i class="bi bi-plus-circle-fill fs-5"></i><span>Add New Harvest</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
        <form method="GET" action="harvests.php" class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by farm name, crop, quality grade, or remarks...">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="grade_filter" onchange="this.form.submit()">
                    <option value="">All Quality Grades</option>
                    <option value="Grade A" <?= $gradeFilter === 'Grade A' ? 'selected' : '' ?>>Grade A</option>
                    <option value="Grade B" <?= $gradeFilter === 'Grade B' ? 'selected' : '' ?>>Grade B</option>
                    <option value="Grade C" <?= $gradeFilter === 'Grade C' ? 'selected' : '' ?>>Grade C</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <?php if (!empty($search) || !empty($gradeFilter)): ?>
                    <a href="harvests.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                            <th class="py-3">Crop</th>
                            <th class="py-3">Harvest Date</th>
                            <th class="py-3">Quantity</th>
                            <th class="py-3">Price / kg</th>
                            <th class="py-3">Total Est. Value</th>
                            <th class="py-3">Quality Grade</th>
                            <th class="py-3">Remarks</th>
                            <th class="py-3 pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($harvests)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($harvests as $h): ?>
                                <?php $estValue = $h['quantity_kg'] * $h['selling_price_per_kg']; ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-light text-secondary border px-2 py-1 fw-bold">#<?= $counter++ ?></span></td>
                                    <td class="fw-bold text-dark"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($h['farm_name']) ?></td>
                                    <td>
                                        <span class="text-dark fw-semibold"><?= htmlspecialchars($h['crop_name']) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars($h['variety'] ?? '') ?></small>
                                    </td>
                                    <td class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($h['harvest_date']) ?></td>
                                    <td class="fw-bold text-success"><?= number_format($h['quantity_kg'], 2) ?> kg</td>
                                    <td>₱<?= number_format($h['selling_price_per_kg'], 2) ?></td>
                                    <td class="fw-bold text-primary">₱<?= number_format($estValue, 2) ?></td>
                                    <td>
                                        <?php 
                                            $gradeBg = 'secondary';
                                            if ($h['quality_grade'] === 'Grade A') $gradeBg = 'success';
                                            elseif ($h['quality_grade'] === 'Grade B') $gradeBg = 'primary';
                                            elseif ($h['quality_grade'] === 'Grade C') $gradeBg = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $gradeBg ?> px-2 py-1"><?= htmlspecialchars($h['quality_grade'] ?? 'N/A') ?></span>
                                    </td>
                                    <td><span class="text-muted small"><?= htmlspecialchars($h['remarks'] ?? 'N/A') ?></span></td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-info px-2 py-1" title="View Details"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal" 
                                                    data-farm="<?= htmlspecialchars($h['farm_name'], ENT_QUOTES) ?>"
                                                    data-crop="<?= htmlspecialchars($h['crop_name'] . ' (' . ($h['variety'] ?? 'N/A') . ')', ENT_QUOTES) ?>"
                                                    data-date="<?= htmlspecialchars($h['harvest_date'], ENT_QUOTES) ?>"
                                                    data-qty="<?= number_format($h['quantity_kg'], 2) ?>"
                                                    data-price="<?= number_format($h['selling_price_per_kg'], 2) ?>"
                                                    data-total="<?= number_format($estValue, 2) ?>"
                                                    data-grade="<?= htmlspecialchars($h['quality_grade'] ?? 'N/A', ENT_QUOTES) ?>"
                                                    data-remarks="<?= htmlspecialchars($h['remarks'] ?? 'N/A', ENT_QUOTES) ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1" title="Edit Record"
                                                    data-bs-toggle="modal" data-bs-target="#editModal" 
                                                    data-id="<?= $h['harvest_id'] ?>"
                                                    data-planting-id="<?= $h['planting_id'] ?>"
                                                    data-date="<?= htmlspecialchars($h['harvest_date'], ENT_QUOTES) ?>"
                                                    data-qty="<?= $h['quantity_kg'] ?>"
                                                    data-price="<?= $h['selling_price_per_kg'] ?>"
                                                    data-grade="<?= htmlspecialchars($h['quality_grade'] ?? '', ENT_QUOTES) ?>"
                                                    data-remarks="<?= htmlspecialchars($h['remarks'] ?? '', ENT_QUOTES) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Record"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                    data-id="<?= $h['harvest_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($h['farm_name'] . ' - ' . $h['crop_name'] . ' (' . number_format($h['quantity_kg'], 2) . 'kg)', ENT_QUOTES) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center text-muted py-5"><p class="mb-0 fw-semibold">No harvest records found.</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Harvest Modal -->
<div class="modal fade" id="addHarvestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-basket-fill me-2"></i> Register New Harvest Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Associated Planting Record</label>
                        <select class="form-select form-select-lg fs-6" name="planting_id" required>
                            <option value="" selected disabled>Select planting record...</option>
                            <?php foreach ($plantingsList as $p): ?>
                                <option value="<?= $p['planting_id'] ?>">
                                    <?= htmlspecialchars($p['farm_name'] . ' -> ' . $p['crop_name'] . ' (Planted: ' . $p['planting_date'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Harvest Date</label>
                        <input type="date" class="form-control form-control-lg fs-6" name="harvest_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small text-uppercase">Quantity (kg)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg fs-6" name="quantity_kg" required placeholder="e.g. 500.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small text-uppercase">Price per kg (₱)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg fs-6" name="selling_price_per_kg" required placeholder="e.g. 25.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Quality Grade</label>
                        <select class="form-select form-select-lg fs-6" name="quality_grade">
                            <option value="Grade A">Grade A (High Quality)</option>
                            <option value="Grade B">Grade B (Good Quality)</option>
                            <option value="Grade C">Grade C (Acceptable)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Remarks</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="remarks" placeholder="e.g. High quality harvest">
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_harvest" class="btn btn-primary px-4">Save Harvest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Harvest Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Harvest Record Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Farm</span><span id="viewFarm" class="fw-bold text-dark"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Crop & Variety</span><span id="viewCrop" class="text-dark"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Harvest Date</span><span id="viewDate" class="text-dark"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Quantity</span><span id="viewQty" class="text-success fw-bold"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Price per kg</span><span id="viewPrice" class="text-dark"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Total Estimated Value</span><span id="viewTotal" class="text-primary fw-bold"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Quality Grade</span><span id="viewGrade" class="badge"></span></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3"><span class="text-muted fw-semibold">Remarks</span><span id="viewRemarks" class="text-dark"></span></li>
                </ul>
            </div>
            <div class="modal-footer bg-light p-3 justify-content-center"><button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- Edit Harvest Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <input type="hidden" name="harvest_id" id="editHarvestId">
                <div class="modal-header bg-warning text-dark p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Harvest Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Associated Planting Record</label>
                        <select class="form-select form-select-lg fs-6" name="planting_id" id="editPlantingId" required>
                            <?php foreach ($plantingsList as $p): ?>
                                <option value="<?= $p['planting_id'] ?>">
                                    <?= htmlspecialchars($p['farm_name'] . ' -> ' . $p['crop_name'] . ' (Planted: ' . $p['planting_date'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Harvest Date</label>
                        <input type="date" class="form-control form-control-lg fs-6" name="harvest_date" id="editHarvestDate" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small text-uppercase">Quantity (kg)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg fs-6" name="quantity_kg" id="editQuantityKg" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-secondary small text-uppercase">Price per kg (₱)</label>
                            <input type="number" step="0.01" class="form-control form-control-lg fs-6" name="selling_price_per_kg" id="editSellingPricePerKg" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Quality Grade</label>
                        <select class="form-select form-select-lg fs-6" name="quality_grade" id="editQualityGrade">
                            <option value="Grade A">Grade A (High Quality)</option>
                            <option value="Grade B">Grade B (Good Quality)</option>
                            <option value="Grade C">Grade C (Acceptable)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Remarks</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="remarks" id="editRemarks">
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_harvest" class="btn btn-warning px-4 fw-semibold">Save Changes</button>
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
                <p class="text-muted mb-1">Are you sure you want to delete harvest record:</p>
                <h4 id="deleteHarvestName" class="fw-bold text-dark mb-2"></h4>
                <p class="text-danger small mb-0 fw-semibold">This action is permanent and cannot be undone.</p>
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
    const viewModal = document.getElementById('viewModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('viewFarm').textContent = button.getAttribute('data-farm');
            document.getElementById('viewCrop').textContent = button.getAttribute('data-crop');
            document.getElementById('viewDate').textContent = button.getAttribute('data-date');
            document.getElementById('viewQty').textContent = button.getAttribute('data-qty') + ' kg';
            document.getElementById('viewPrice').textContent = '₱' + button.getAttribute('data-price');
            document.getElementById('viewTotal').textContent = '₱' + button.getAttribute('data-total');
            document.getElementById('viewRemarks').textContent = button.getAttribute('data-remarks');
            
            const grade = button.getAttribute('data-grade');
            const gradeBadge = document.getElementById('viewGrade');
            gradeBadge.textContent = grade;
            gradeBadge.className = 'badge px-2 py-1';
            if (grade === 'Grade A') gradeBadge.classList.add('bg-success');
            else if (grade === 'Grade B') gradeBadge.classList.add('bg-primary');
            else if (grade === 'Grade C') gradeBadge.classList.add('bg-warning');
            else gradeBadge.classList.add('bg-secondary');
        });
    }

    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editHarvestId').value = button.getAttribute('data-id');
            document.getElementById('editPlantingId').value = button.getAttribute('data-planting-id');
            document.getElementById('editHarvestDate').value = button.getAttribute('data-date');
            document.getElementById('editQuantityKg').value = button.getAttribute('data-qty');
            document.getElementById('editSellingPricePerKg').value = button.getAttribute('data-price');
            document.getElementById('editQualityGrade').value = button.getAttribute('data-grade');
            document.getElementById('editRemarks').value = button.getAttribute('data-remarks');
        });
    }

    <?php if (!empty($popupMessage)): ?>
        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
    <?php endif; ?>
</script>
</body>
</html>
