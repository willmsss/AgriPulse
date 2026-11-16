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
        $stmt = $pdo->prepare("DELETE FROM crop WHERE crop_id = ?");
        $stmt->execute([$deleteId]);
        header("Location: crops.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        header("Location: crops.php?status=delete_error");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_crop'])) {
    $cropName = trim($_POST['crop_name']);
    $variety = trim($_POST['variety']);
    $cropCategory = trim($_POST['crop_category']);
    $growingPeriodDays = trim($_POST['growing_period_days']);
    $recommendedSeason = trim($_POST['recommended_season']);

    if (!empty($cropName)) {
        try {
            // Flexible query handling columns if they exist in schema variations
            $stmt = $pdo->prepare("INSERT INTO crop (crop_name, variety, crop_category, growing_period_days, recommended_season) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$cropName, $variety, $cropCategory, $growingPeriodDays, $recommendedSeason]);
            header("Location: crops.php?status=added");
            exit();
        } catch (PDOException $e) {
            try {
                // Fallback for base schema without extra category/season columns
                $stmt = $pdo->prepare("INSERT INTO crop (crop_name, variety, growing_period_days) VALUES (?, ?, ?)");
                $stmt->execute([$cropName, $variety, $growingPeriodDays]);
                header("Location: crops.php?status=added");
                exit();
            } catch (PDOException $ex) {
                header("Location: crops.php?status=add_error");
                exit();
            }
        }
    } else {
        header("Location: crops.php?status=empty_error");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_crop'])) {
    $cropId = trim($_POST['crop_id']);
    $cropName = trim($_POST['crop_name']);
    $variety = trim($_POST['variety']);
    $cropCategory = trim($_POST['crop_category']);
    $growingPeriodDays = trim($_POST['growing_period_days']);
    $recommendedSeason = trim($_POST['recommended_season']);

    if (!empty($cropId) && !empty($cropName)) {
        try {
            $stmt = $pdo->prepare("UPDATE crop SET crop_name = ?, variety = ?, crop_category = ?, growing_period_days = ?, recommended_season = ? WHERE crop_id = ?");
            $stmt->execute([$cropName, $variety, $cropCategory, $growingPeriodDays, $recommendedSeason, $cropId]);
            header("Location: crops.php?status=updated");
            exit();
        } catch (PDOException $e) {
            try {
                $stmt = $pdo->prepare("UPDATE crop SET crop_name = ?, variety = ?, growing_period_days = ? WHERE crop_id = ?");
                $stmt->execute([$cropName, $variety, $growingPeriodDays, $cropId]);
                header("Location: crops.php?status=updated");
                exit();
            } catch (PDOException $ex) {
                header("Location: crops.php?status=update_error");
                exit();
            }
        }
    } else {
        header("Location: crops.php?status=empty_error");
        exit();
    }
}

$status = $_GET['status'] ?? '';
if ($status === 'added') { $popupMessage = "Crop added successfully!"; $popupType = "success"; }
elseif ($status === 'updated') { $popupMessage = "Crop updated successfully!"; $popupType = "success"; }
elseif ($status === 'deleted') { $popupMessage = "Crop deleted successfully!"; $popupType = "success"; }
elseif ($status === 'delete_error') { $popupMessage = "Cannot delete crop: Active plantings linked."; $popupType = "error"; }
elseif ($status === 'add_error' || $status === 'update_error' || $status === 'empty_error') { $popupMessage = "Operation failed."; $popupType = "error"; }

$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category_filter'] ?? '');
$seasonFilter = trim($_GET['season_filter'] ?? '');

try {
    $sql = "SELECT * FROM crop WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (crop_name ILIKE ? OR variety ILIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm);
    }
    if (!empty($categoryFilter)) {
        $sql .= " AND (crop_category ILIKE ? OR category ILIKE ?)";
        array_push($params, $categoryFilter, $categoryFilter);
    }
    if (!empty($seasonFilter)) {
        $sql .= " AND recommended_season ILIKE ?";
        array_push($params, $seasonFilter);
    }
    $sql .= " ORDER BY crop_id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $crops = $stmt->fetchAll();
    $totalCrops = count($crops);
} catch (PDOException $e) {
    $crops = []; $totalCrops = 0;
}

// Standardized Lookup Lists
$categoriesList = ['Cereal', 'Vegetable', 'Root Crop', 'Fruit', 'Legume', 'Industrial'];
$seasonsList = ['Wet Season', 'Dry Season', 'Year-Round'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crops Master - AgriPulse</title>
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
                <li class="nav-item"><a class="nav-link active px-3 py-2" href="crops.php"><i class="bi bi-flower1 me-1"></i> Crops</a></li>
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
                    <h1 class="h3 fw-bold text-dark mb-0">Crops Master Directory</h1>
                    <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill small fw-semibold"><?= $totalCrops ?> Registered</span>
                </div>
                <p class="text-muted small mb-0">Manage crop types, varieties, categories, growing periods, and recommended seasons.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-warning text-dark px-3 py-2 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-filter fs-5"></i><span>Filter</span>
                </button>
                <button class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addCropModal">
                    <i class="bi bi-plus-circle-fill fs-5"></i><span>Add New Crop</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
        <form method="GET" action="crops.php" class="row g-3 align-items-center">
            <input type="hidden" name="category_filter" value="<?= htmlspecialchars($categoryFilter) ?>">
            <input type="hidden" name="season_filter" value="<?= htmlspecialchars($seasonFilter) ?>">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by crop name or variety...">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
                <?php if (!empty($search) || !empty($categoryFilter) || !empty($seasonFilter)): ?>
                    <a href="crops.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!empty($categoryFilter) || !empty($seasonFilter)): ?>
            <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted">Active Filters:</span>
                <?php if (!empty($categoryFilter)): ?>
                    <span class="badge bg-secondary">Category: <?= htmlspecialchars($categoryFilter) ?></span>
                <?php endif; ?>
                <?php if (!empty($seasonFilter)): ?>
                    <span class="badge bg-secondary">Season: <?= htmlspecialchars($seasonFilter) ?></span>
                <?php endif; ?>
                <a href="crops.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" class="badge bg-danger text-decoration-none">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="card table-card border-0 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="py-3 ps-4">No.</th>
                            <th class="py-3">Crop Name</th>
                            <th class="py-3">Variety</th>
                            <th class="py-3">Category</th>
                            <th class="py-3">Growing Period</th>
                            <th class="py-3">Season</th>
                            <th class="py-3 pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($crops)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($crops as $c): ?>
                                <?php
                                    $catVal = $c['crop_category'] ?? ($c['category'] ?? 'N/A');
                                    $seasVal = $c['recommended_season'] ?? 'N/A';
                                ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-light text-secondary border px-2 py-1 fw-bold">#<?= $counter++ ?></span></td>
                                    <td class="fw-bold text-dark"><i class="bi bi-flower1 text-warning me-1"></i><?= htmlspecialchars($c['crop_name']) ?></td>
                                    <td><?= htmlspecialchars($c['variety'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($catVal) ?></span></td>
                                    <td class="text-muted small"><?= htmlspecialchars($c['growing_period_days'] ?? 'N/A') ?> days</td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($seasVal) ?></span></td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group" role="group">
                                            <!-- View Button -->
                                            <button type="button" class="btn btn-sm btn-outline-info px-2 py-1" title="View Details"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal"
                                                    data-name="<?= htmlspecialchars($c['crop_name'], ENT_QUOTES) ?>"
                                                    data-variety="<?= htmlspecialchars($c['variety'] ?? 'N/A', ENT_QUOTES) ?>"
                                                    data-category="<?= htmlspecialchars($catVal, ENT_QUOTES) ?>"
                                                    data-days="<?= htmlspecialchars($c['growing_period_days'] ?? 'N/A', ENT_QUOTES) ?>"
                                                    data-season="<?= htmlspecialchars($seasVal, ENT_QUOTES) ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1" title="Edit Record" data-bs-toggle="modal" data-bs-target="#editModal"
                                                    data-id="<?= $c['crop_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($c['crop_name'], ENT_QUOTES) ?>"
                                                    data-variety="<?= htmlspecialchars($c['variety'] ?? '', ENT_QUOTES) ?>" 
                                                    data-category="<?= htmlspecialchars($catVal, ENT_QUOTES) ?>"
                                                    data-days="<?= $c['growing_period_days'] ?? '' ?>"
                                                    data-season="<?= htmlspecialchars($seasVal, ENT_QUOTES) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Record" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-id="<?= $c['crop_id'] ?>" data-name="<?= htmlspecialchars($c['crop_name'] . ' (' . ($c['variety'] ?? '') . ')', ENT_QUOTES) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-5"><p class="mb-0 fw-semibold">No crops found.</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Crop Modal -->
<div class="modal fade" id="addCropModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-flower1 me-2"></i> Register New Crop</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Crop Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="crop_name" required placeholder="e.g. Tomato">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Variety</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="variety" placeholder="e.g. Roma">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Crop Category</label>
                        <select class="form-select form-select-lg fs-6" name="crop_category" required>
                            <option value="" selected disabled>Select category...</option>
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Growing Period (Days)</label>
                        <input type="number" class="form-control form-control-lg fs-6" name="growing_period_days" placeholder="e.g. 90" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Recommended Season</label>
                        <select class="form-select form-select-lg fs-6" name="recommended_season" required>
                            <option value="" selected disabled>Select season...</option>
                            <?php foreach ($seasonsList as $seas): ?>
                                <option value="<?= $seas ?>"><?= $seas ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_crop" class="btn btn-primary px-4">Save Crop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="GET" action="crops.php">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <div class="modal-header bg-secondary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-filter me-2"></i> Filter Crops Directory</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Crop Category</label>
                        <select class="form-select form-select-lg fs-6" name="category_filter">
                            <option value="">All Categories</option>
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= $cat ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Recommended Season</label>
                        <select class="form-select form-select-lg fs-6" name="season_filter">
                            <option value="">All Seasons</option>
                            <?php foreach ($seasonsList as $seas): ?>
                                <option value="<?= $seas ?>" <?= $seasonFilter === $seas ? 'selected' : '' ?>><?= $seas ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4 justify-content-between">
                    <a href="crops.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" class="btn btn-outline-danger">Clear Filters</a>
                    <button type="submit" class="btn btn-primary px-4">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Crop Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Crop Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Crop Name</span>
                        <span id="viewCropName" class="fw-bold text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Variety</span>
                        <span id="viewVariety" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Category</span>
                        <span id="viewCategory" class="badge bg-light text-dark border"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Growing Period</span>
                        <span id="viewDays" class="text-primary fw-bold"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Recommended Season</span>
                        <span id="viewSeason" class="badge bg-info text-dark"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light p-3 justify-content-center">
                <button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Crop Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <input type="hidden" name="crop_id" id="editCropId">
                <div class="modal-header bg-warning text-dark p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Crop Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Crop Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="crop_name" id="editCropName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Variety</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="variety" id="editVariety">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Crop Category</label>
                        <select class="form-select form-select-lg fs-6" name="crop_category" id="editCropCategory" required>
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Growing Period (Days)</label>
                        <input type="number" class="form-control form-control-lg fs-6" name="growing_period_days" id="editGrowingPeriodDays" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Recommended Season</label>
                        <select class="form-select form-select-lg fs-6" name="recommended_season" id="editRecommendedSeason" required>
                            <?php foreach ($seasonsList as $seas): ?>
                                <option value="<?= $seas ?>"><?= $seas ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_crop" class="btn btn-warning px-4 fw-semibold">Save Changes</button>
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
                <p class="text-muted mb-1">Are you sure you want to delete crop:</p>
                <h4 id="deleteCropName" class="fw-bold text-dark mb-2"></h4>
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
            document.getElementById('viewCropName').textContent = button.getAttribute('data-name');
            document.getElementById('viewVariety').textContent = button.getAttribute('data-variety');
            document.getElementById('viewCategory').textContent = button.getAttribute('data-category');
            document.getElementById('viewDays').textContent = button.getAttribute('data-days') + ' days';
            document.getElementById('viewSeason').textContent = button.getAttribute('data-season');
        });
    }

    // Handle Edit Modal Data Binding
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editCropId').value = button.getAttribute('data-id');
            document.getElementById('editCropName').value = button.getAttribute('data-name');
            document.getElementById('editVariety').value = button.getAttribute('data-variety');
            document.getElementById('editCropCategory').value = button.getAttribute('data-category');
            document.getElementById('editGrowingPeriodDays').value = button.getAttribute('data-days');
            document.getElementById('editRecommendedSeason').value = button.getAttribute('data-season');
        });
    }

    // Handle Delete Modal Data Binding
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('deleteCropName').textContent = button.getAttribute('data-name');
            document.getElementById('confirmDeleteBtn').href = 'crops.php?delete_id=' + button.getAttribute('data-id');
        });
    }

    <?php if (!empty($popupMessage)): ?>
        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
    <?php endif; ?>
</script>
</body>
</html>
