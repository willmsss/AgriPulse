<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/Database.php';
requireLogin();

$pdo = Database::connect();
$reportType = $_GET['type'] ?? 'farmers_farms';
$statusFilter = $_GET['status'] ?? '';

$data = [];
$totals = ['qty' => 0, 'val' => 0];

try {
    if ($reportType === 'farmers_farms') {
        $stmt = $pdo->query("
            SELECT f.farm_name, f.location, f.area_hectares, f.soil_type, f.irrigation_type,
                   fr.first_name, fr.last_name, fr.contact_number, fr.address
            FROM farm f
            JOIN farmer fr ON f.farmer_id = fr.farmer_id
            ORDER BY fr.last_name ASC
        ");
        $data = $stmt->fetchAll();
    } elseif ($reportType === 'crops_planted') {
        $stmt = $pdo->query("
            SELECT p.planting_id, f.farm_name, c.crop_name, c.variety, c.crop_category, p.planting_date, p.status
            FROM planting p
            JOIN farm f ON p.farm_id = f.farm_id
            JOIN crop c ON p.crop_id = c.crop_id
            ORDER BY p.planting_date DESC
        ");
        $data = $stmt->fetchAll();
    } elseif ($reportType === 'plantings_status') {
        $sql = "
            SELECT p.*, f.farm_name, c.crop_name, c.variety 
            FROM planting p 
            JOIN farm f ON p.farm_id = f.farm_id 
            JOIN crop c ON p.crop_id = c.crop_id
        ";
        if (!empty($statusFilter)) {
            $sql .= " WHERE p.status = " . $pdo->quote($statusFilter);
        }
        $sql .= " ORDER BY p.planting_date DESC";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll();
    } elseif ($reportType === 'harvest_summary') {
        $stmt = $pdo->query("
            SELECT h.*, p.planting_date, f.farm_name, c.crop_name, c.variety,
                   (h.quantity_kg * h.selling_price_per_kg) as est_value
            FROM harvest h
            JOIN planting p ON h.planting_id = p.planting_id
            JOIN farm f ON p.farm_id = f.farm_id
            JOIN crop c ON p.crop_id = c.crop_id
            ORDER BY h.harvest_date DESC
        ");
        $data = $stmt->fetchAll();

        // Calculate summary totals
        $totalsStmt = $pdo->query("SELECT SUM(quantity_kg) as total_qty, SUM(quantity_kg * selling_price_per_kg) as total_val FROM harvest");
        $tResult = $totalsStmt->fetch();
        $totals['qty'] = $tResult['total_qty'] ?? 0;
        $totals['val'] = $tResult['total_val'] ?? 0;
    }
} catch (PDOException $e) {
    $dbError = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agricultural Reports - AgriPulse</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; }
        .navbar-custom { background-color: #0284c7; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .navbar-custom .nav-link { 
            color: #e0f2fe; 
            border-radius: 0.375rem; 
            transition: all 0.2s; 
            font-size: 0.875rem; 
            font-weight: 500;
            white-space: nowrap;
        }
        .navbar-custom .nav-link:hover, .navbar-custom .nav-link.active { background-color: #0369a1; color: #fff; }
        .card-header-fancy {
            background: #ffffff;
            border-radius: 0.75rem !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            border-left: 4px solid #0284c7 !important;
        }
        .table-card { border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02); }
        .table th { font-weight: 600; color: #475569; background-color: #f8fafc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        .btn-primary { background-color: #0284c7; border-color: #0284c7; transition: all 0.2s; }
        .btn-primary:hover { background-color: #0369a1; border-color: #0369a1; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>

<!-- Top Horizontal Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 py-3 no-print">
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
                <li class="nav-item"><a class="nav-link px-3 py-2" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/farmers.php"><i class="bi bi-people me-1"></i> Farmers</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/farms.php"><i class="bi bi-geo-alt me-1"></i> Farms</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/crops.php"><i class="bi bi-flower1 me-1"></i> Crops Master</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/plantings.php"><i class="bi bi-calendar-check me-1"></i> Planting Records</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="modules/harvests.php"><i class="bi bi-basket me-1"></i> Harvest Records</a></li>
                <li class="nav-item"><a class="nav-link active fw-semibold px-3 py-2" href="reports.php"><i class="bi bi-file-earmark-bar-graph me-1"></i> Reports</a></li>
                <li class="nav-item ms-lg-3 mt-2 mt-lg-0 w-100 w-lg-auto">
                    <a href="logout.php" class="btn btn-outline-light btn-sm px-3 py-2 w-100"><i class="bi bi-box-arrow-right me-1"></i> Sign Out</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Container -->
<div class="container py-4">
    
    <!-- Fancy Top Header Bar -->
    <div class="card card-header-fancy border-0 p-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h3 fw-bold text-dark mb-0">System Agricultural Reports</h1>
                    <span class="badge bg-primary text-white px-3 py-1.5 rounded-pill small fw-semibold shadow-sm">
                        Official Analytics
                    </span>
                </div>
                <p class="text-muted small mb-0">Generate, review, and print dynamic agricultural reports retrieved directly from Supabase PostgreSQL[cite: 5].</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-outline-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-printer-fill"></i> 
                    <span>Print Report</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Report Selection Tabs -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white no-print">
        <div class="d-flex flex-wrap gap-2">
            <a href="reports.php?type=farmers_farms" class="btn <?= $reportType === 'farmers_farms' ? 'btn-primary' : 'btn-outline-secondary' ?> px-3 py-2 fw-semibold">
                <i class="bi bi-people me-1"></i> Farmers & Their Farms
            </a>
            <a href="reports.php?type=crops_planted" class="btn <?= $reportType === 'crops_planted' ? 'btn-primary' : 'btn-outline-secondary' ?> px-3 py-2 fw-semibold">
                <i class="bi bi-flower1 me-1"></i> Crops Planted per Farm
            </a>
            <a href="reports.php?type=plantings_status" class="btn <?= $reportType === 'plantings_status' ? 'btn-primary' : 'btn-outline-secondary' ?> px-3 py-2 fw-semibold">
                <i class="bi bi-calendar-check me-1"></i> Plantings by Status
            </a>
            <a href="reports.php?type=harvest_summary" class="btn <?= $reportType === 'harvest_summary' ? 'btn-primary' : 'btn-outline-secondary' ?> px-3 py-2 fw-semibold">
                <i class="bi bi-basket me-1"></i> Harvest Yield & Value
            </a>
        </div>
    </div>

    <?php if ($reportType === 'plantings_status'): ?>
        <!-- Status Filter Sub-bar -->
        <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white no-print">
            <form method="GET" action="reports.php" class="row g-3 align-items-center">
                <input type="hidden" name="type" value="plantings_status">
                <div class="col-md-4">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="">All Statuses (Planted, Growing, Harvested, Failed)</option>
                        <option value="Planted" <?= $statusFilter === 'Planted' ? 'selected' : '' ?>>Planted</option>
                        <option value="Growing" <?= $statusFilter === 'Growing' ? 'selected' : '' ?>>Growing</option>
                        <option value="Harvested" <?= $statusFilter === 'Harvested' ? 'selected' : '' ?>>Harvested</option>
                        <option value="Failed" <?= $statusFilter === 'Failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($reportType === 'harvest_summary'): ?>
        <!-- Harvest Summary Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 bg-white border-start border-success border-4 rounded-3">
                    <p class="text-muted text-uppercase fw-semibold small mb-1">Total Harvested Quantity</p>
                    <h3 class="fw-bold text-dark mb-0"><?= number_format($totals['qty'], 2) ?> kg</h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 bg-white border-start border-primary border-4 rounded-3">
                    <p class="text-muted text-uppercase fw-semibold small mb-1">Total Estimated Harvest Value</p>
                    <h3 class="fw-bold text-dark mb-0">₱<?= number_format($totals['val'], 2) ?></h3>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Report Table Card -->
    <div class="card table-card border-0 bg-white">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-secondary fs-6">
                <i class="bi bi-table me-2 text-primary"></i>
                <?php
                    if ($reportType === 'farmers_farms') echo 'Report: Farmers and Their Farms Directory';
                    elseif ($reportType === 'crops_planted') echo 'Report: Crops Planted per Farm';
                    elseif ($reportType === 'plantings_status') echo 'Report: Planting Records by Status';
                    elseif ($reportType === 'harvest_summary') echo 'Report: Harvest Yield & Estimated Value Summary';
                ?>
            </h5>
            <span class="text-muted small">Generated: <?= date('F j, Y') ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="py-3 ps-4">No.</th>
                            <?php if ($reportType === 'farmers_farms'): ?>
                                <th class="py-3">Farmer Name</th>
                                <th class="py-3">Contact</th>
                                <th class="py-3">Farm Name</th>
                                <th class="py-3">Location</th>
                                <th class="py-3">Area (ha)</th>
                                <th class="py-3 pe-4">Soil & Irrigation</th>
                            <?php elseif ($reportType === 'crops_planted'): ?>
                                <th class="py-3">Farm Name</th>
                                <th class="py-3">Crop Name</th>
                                <th class="py-3">Variety</th>
                                <th class="py-3">Category</th>
                                <th class="py-3">Planting Date</th>
                                <th class="py-3 pe-4">Status</th>
                            <?php elseif ($reportType === 'plantings_status'): ?>
                                <th class="py-3">Farm Name</th>
                                <th class="py-3">Crop Name</th>
                                <th class="py-3">Planting Date</th>
                                <th class="py-3">Area (ha)</th>
                                <th class="py-3">Seed Qty (kg)</th>
                                <th class="py-3 pe-4">Status</th>
                            <?php elseif ($reportType === 'harvest_summary'): ?>
                                <th class="py-3">Farm Name</th>
                                <th class="py-3">Crop Name</th>
                                <th class="py-3">Harvest Date</th>
                                <th class="py-3">Quantity (kg)</th>
                                <th class="py-3">Price / kg</th>
                                <th class="py-3">Est. Value (₱)</th>
                                <th class="py-3 pe-4">Quality & Remarks</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-secondary border px-2 py-1 fw-bold">#<?= $counter++ ?></span>
                                    </td>
                                    <?php if ($reportType === 'farmers_farms'): ?>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($row['contact_number'] ?? 'N/A') ?></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($row['farm_name']) ?></td>
                                        <td><?= htmlspecialchars($row['location']) ?></td>
                                        <td><?= number_format($row['area_hectares'], 2) ?> ha</td>
                                        <td class="pe-4"><small class="text-muted"><?= htmlspecialchars($row['soil_type']) ?> / <?= htmlspecialchars($row['irrigation_type']) ?></small></td>
                                    <?php elseif ($reportType === 'crops_planted'): ?>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['farm_name']) ?></td>
                                        <td class="text-primary fw-semibold"><?= htmlspecialchars($row['crop_name']) ?></td>
                                        <td><?= htmlspecialchars($row['variety'] ?? 'N/A') ?></td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border px-2 py-1"><?= htmlspecialchars($row['crop_category']) ?></span></td>
                                        <td class="text-muted small"><?= htmlspecialchars($row['planting_date']) ?></td>
                                        <td class="pe-4"><span class="badge bg-info px-2 py-1"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <?php elseif ($reportType === 'plantings_status'): ?>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['farm_name']) ?></td>
                                        <td>
                                            <span class="text-dark fw-semibold"><?= htmlspecialchars($row['crop_name']) ?></span>
                                            <small class="text-muted d-block"><?= htmlspecialchars($row['variety']) ?></small>
                                        </td>
                                        <td class="text-muted small"><?= htmlspecialchars($row['planting_date']) ?></td>
                                        <td><?= number_format($row['area_planted'], 2) ?> ha</td>
                                        <td><?= number_format($row['seed_quantity'], 2) ?> kg</td>
                                        <td class="pe-4"><span class="badge bg-primary px-2 py-1"><?= htmlspecialchars($row['status']) ?></span></td>
                                    <?php elseif ($reportType === 'harvest_summary'): ?>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['farm_name']) ?></td>
                                        <td>
                                            <span class="text-dark fw-semibold"><?= htmlspecialchars($row['crop_name']) ?></span>
                                            <small class="text-muted d-block"><?= htmlspecialchars($row['variety']) ?></small>
                                        </td>
                                        <td class="text-muted small"><?= htmlspecialchars($row['harvest_date']) ?></td>
                                        <td class="fw-bold text-success"><?= number_format($row['quantity_kg'], 2) ?> kg</td>
                                        <td>₱<?= number_format($row['selling_price_per_kg'], 2) ?></td>
                                        <td class="fw-bold text-primary">₱<?= number_format($row['est_value'], 2) ?></td>
                                        <td class="pe-4">
                                            <span class="badge bg-warning bg-opacity-10 text-dark border px-2 py-1"><?= htmlspecialchars($row['quality_grade']) ?></span>
                                            <small class="text-muted d-block"><?= htmlspecialchars($row['remarks']) ?></small>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <div class="py-4">
                                        <i class="bi bi-file-earmark-x fs-1 text-muted opacity-50 mb-2 d-block"></i>
                                        <p class="mb-0 fw-semibold">No report data found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
