<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

try {
    $pdo = Database::connect();

    $totalFarmers = $pdo->query("SELECT COUNT(*) FROM farmer")->fetchColumn();
    $totalFarms = $pdo->query("SELECT COUNT(*) FROM farm")->fetchColumn();
    $totalCrops = $pdo->query("SELECT COUNT(*) FROM crop")->fetchColumn();
    $totalPlantings = $pdo->query("SELECT COUNT(*) FROM planting")->fetchColumn();
    $totalHarvests = $pdo->query("SELECT COUNT(*) FROM harvest")->fetchColumn();

    $harvestStats = $pdo->query("SELECT SUM(quantity_kg) as total_qty, SUM(quantity_kg * selling_price_per_kg) as total_val FROM harvest")->fetch();
    $totalQtyKg = $harvestStats['total_qty'] ?? 0;
    $totalEstValue = $harvestStats['total_val'] ?? 0;

    $stmtStatus = $pdo->query("SELECT status, COUNT(*) as count FROM planting GROUP BY status");
    $plantingsByStatus = $stmtStatus->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmtPlantings = $pdo->query("
        SELECT p.*, f.farm_name, c.crop_name 
        FROM planting p
        JOIN farm f ON p.farm_id = f.farm_id
        JOIN crop c ON p.crop_id = c.crop_id
        ORDER BY p.planting_date DESC LIMIT 5
    ");
    $recentPlantings = $stmtPlantings->fetchAll();

    $stmtHarvests = $pdo->query("
        SELECT h.*, p.planting_date, f.farm_name, c.crop_name 
        FROM harvest h
        JOIN planting p ON h.planting_id = p.planting_id
        JOIN farm f ON p.farm_id = f.farm_id
        JOIN crop c ON p.crop_id = c.crop_id
        ORDER BY h.harvest_date DESC LIMIT 5
    ");
    $recentHarvests = $stmtHarvests->fetchAll();

} catch (PDOException $e) {
    $dbError = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AgriPulse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 py-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="dashboard.php">
            <i class="bi bi-flower1 fs-4"></i>
            <span>AgriPulse</span>
            <span class="fs-6 fw-normal text-white-50 d-none d-sm-inline">| Agriculture Info System</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbarNav" aria-controls="topNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="topNavbarNav">
            <ul class="navbar-nav align-items-center gap-1 mt-3 mt-lg-0">
                <li class="nav-item"><a class="nav-link active fw-semibold px-3 py-2" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/farmers.php"><i class="bi bi-people me-1"></i> Farmers</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/farms.php"><i class="bi bi-geo-alt me-1"></i> Farms</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/crops.php"><i class="bi bi-flower1 me-1"></i> Crops</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/plantings.php"><i class="bi bi-calendar-check me-1"></i> Planting Records</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/harvests.php"><i class="bi bi-basket me-1"></i> Harvest Records</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="reports.php"><i class="bi bi-file-earmark-bar-graph me-1"></i> Reports</a></li>
                <li class="nav-item ms-lg-3 mt-2 mt-lg-0 w-100 w-lg-auto"><a href="logout.php" class="btn btn-outline-light btn-sm px-3 py-2 w-100"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
    <!-- Modern Hero Welcome Banner -->
    <div class="card card-header-fancy border-0 p-4 mb-4 bg-white shadow-sm">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h3 fw-bold text-dark mb-0">Agricultural Command Center</h1>
                    <span class="badge bg-primary text-white px-3 py-1.5 rounded-pill small fw-semibold">
                        <i class="bi bi-shield-check me-1"></i> <?= htmlspecialchars($_SESSION['role']) ?>
                    </span>
                </div>
                <p class="text-muted mb-0">Welcome back, <span class="fw-semibold text-dark"><?= htmlspecialchars($_SESSION['email']) ?></span>. Monitor production metrics, farm operations, and real-time yields below.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <div class="d-inline-flex align-items-center gap-2 bg-light px-3 py-2 rounded-pill border text-secondary small">
                    <i class="bi bi-calendar3 text-primary"></i>
                    <span><?= date('F j, Y') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($dbError)): ?>
        <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($dbError) ?>
        </div>
    <?php endif; ?>

    <!-- Core Entity Statistics (4-Column Layout) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase fw-semibold small mb-1">Total Farmers</p>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalFarmers) ?></h2>
                    </div>
                    <div class="icon-box"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase fw-semibold small mb-1">Total Farms</p>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalFarms) ?></h2>
                    </div>
                    <div class="icon-box"><i class="bi bi-geo-alt"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase fw-semibold small mb-1">Registered Crops</p>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalCrops) ?></h2>
                    </div>
                    <div class="icon-box"><i class="bi bi-flower1"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase fw-semibold small mb-1">Planting Records</p>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalPlantings) ?></h2>
                    </div>
                    <div class="icon-box"><i class="bi bi-calendar-check"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial & Yield Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase fw-semibold small mb-1">Total Harvest Yield</p>
                        <h2 class="fw-bold text-success mb-0"><?= number_format($totalQtyKg, 2) ?> <span class="fs-6 fw-normal text-muted">kg</span></h2>
                    </div>
                    <div class="icon-box bg-gradient bg-success"><i class="bi bi-basket"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase fw-semibold small mb-1">Estimated Value</p>
                        <h2 class="fw-bold text-dark mb-0">₱<?= number_format($totalEstValue, 2) ?></h2>
                    </div>
                    <div class="icon-box bg-gradient bg-warning"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12">
            <div class="card card-stat p-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted text-uppercase fw-semibold small mb-1">Harvest Records Count</p>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalHarvests) ?></h2>
                    </div>
                    <div class="icon-box bg-gradient bg-secondary"><i class="bi bi-clipboard-data"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Breakdown Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white p-4">
        <h5 class="fw-bold text-secondary fs-6 mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Planting Status Overview</h5>
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">Planted</span>
                        <h4 class="fw-bold text-primary mb-0"><?= number_format($plantingsByStatus['Planted'] ?? 0) ?></h4>
                    </div>
                    <i class="bi bi-seedling fs-3 text-primary opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">Growing</span>
                        <h4 class="fw-bold text-info mb-0"><?= number_format($plantingsByStatus['Growing'] ?? 0) ?></h4>
                    </div>
                    <i class="bi bi-tree fs-3 text-info opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">Harvested</span>
                        <h4 class="fw-bold text-success mb-0"><?= number_format($plantingsByStatus['Harvested'] ?? 0) ?></h4>
                    </div>
                    <i class="bi bi-check-circle fs-3 text-success opacity-50"></i>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">Failed</span>
                        <h4 class="fw-bold text-danger mb-0"><?= number_format($plantingsByStatus['Failed'] ?? 0) ?></h4>
                    </div>
                    <i class="bi bi-x-circle fs-3 text-danger opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tables Section -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card table-card border-0 bg-white">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 fw-bold text-secondary fs-6"><i class="bi bi-calendar-check me-2 text-primary"></i>Recent Plantings</h5>
                    <a href="modules/plantings.php" class="btn btn-sm btn-outline-primary px-3 rounded-pill">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="py-3 ps-4">Farm</th>
                                    <th class="py-3">Crop</th>
                                    <th class="py-3">Date</th>
                                    <th class="py-3 pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentPlantings)): ?>
                                    <?php foreach ($recentPlantings as $p): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($p['farm_name']) ?></td>
                                            <td class="text-dark"><?= htmlspecialchars($p['crop_name']) ?></td>
                                            <td class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($p['planting_date']) ?></td>
                                            <td class="pe-4">
                                                <span class="badge bg-<?= $p['status'] === 'Harvested' ? 'success' : ($p['status'] === 'Growing' ? 'info' : 'secondary') ?> px-2 py-1">
                                                    <?= htmlspecialchars($p['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No planting records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card table-card border-0 bg-white">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 fw-bold text-secondary fs-6"><i class="bi bi-basket me-2 text-primary"></i>Recent Harvests</h5>
                    <a href="modules/harvests.php" class="btn btn-sm btn-outline-primary px-3 rounded-pill">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="py-3 ps-4">Farm</th>
                                    <th class="py-3">Crop</th>
                                    <th class="py-3">Qty (kg)</th>
                                    <th class="py-3 pe-4">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentHarvests)): ?>
                                    <?php foreach ($recentHarvests as $h): ?>
                                        <tr>
                                            <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($h['farm_name']) ?></td>
                                            <td class="text-dark"><?= htmlspecialchars($h['crop_name']) ?></td>
                                            <td class="fw-bold text-success"><?= number_format($h['quantity_kg'], 2) ?> kg</td>
                                            <td class="pe-4 text-muted small"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($h['harvest_date']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No harvest records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>