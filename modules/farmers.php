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

// Handle Delete Farmer Request
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM farmer WHERE farmer_id = ?");
        $stmt->execute([$deleteId]);
        header("Location: farmers.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        header("Location: farmers.php?status=delete_error");
        exit();
    }
}

// Handle Add Farmer Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_farmer'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $contactNumber = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    if (!empty($firstName) && !empty($lastName)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO farmer (first_name, last_name, contact_number, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $contactNumber, $address]);
            header("Location: farmers.php?status=added");
            exit();
        } catch (PDOException $e) {
            header("Location: farmers.php?status=add_error");
            exit();
        }
    } else {
        header("Location: farmers.php?status=empty_error");
        exit();
    }
}

// Handle Update Farmer Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_farmer'])) {
    $farmerId = trim($_POST['farmer_id']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $contactNumber = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    if (!empty($farmerId) && !empty($firstName) && !empty($lastName)) {
        try {
            $stmt = $pdo->prepare("UPDATE farmer SET first_name = ?, last_name = ?, contact_number = ?, address = ? WHERE farmer_id = ?");
            $stmt->execute([$firstName, $lastName, $contactNumber, $address, $farmerId]);
            header("Location: farmers.php?status=updated");
            exit();
        } catch (PDOException $e) {
            header("Location: farmers.php?status=update_error");
            exit();
        }
    } else {
        header("Location: farmers.php?status=empty_error");
        exit();
    }
}

// Check URL status for pop-up triggers
$status = $_GET['status'] ?? '';
if ($status === 'added') {
    $popupMessage = "New farmer added and registered successfully!";
    $popupType = "success";
} elseif ($status === 'updated') {
    $popupMessage = "Farmer record updated successfully!";
    $popupType = "success";
} elseif ($status === 'deleted') {
    $popupMessage = "Farmer record deleted successfully!";
    $popupType = "success";
} elseif ($status === 'delete_error') {
    $popupMessage = "Cannot delete farmer: This farmer has active farm records linked to them.";
    $popupType = "error";
} elseif ($status === 'add_error' || $status === 'update_error' || $status === 'empty_error') {
    $popupMessage = "Operation failed. Please check your inputs and try again.";
    $popupType = "error";
}

// Search parameter
$search = trim($_GET['search'] ?? '');

// Build dynamic SQL query with search support
try {
    $sql = "SELECT * FROM farmer WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (first_name ILIKE ? OR last_name ILIKE ? OR contact_number ILIKE ? OR address ILIKE ?)";
        $searchTerm = "%{$search}%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    $sql .= " ORDER BY farmer_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $farmers = $stmt->fetchAll();
    $totalFarmers = count($farmers);
} catch (PDOException $e) {
    $farmers = [];
    $totalFarmers = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Directory - AgriPulse</title>
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
                <li class="nav-item"><a class="nav-link active px-3 py-2" href="farmers.php"><i class="bi bi-people me-1"></i> Farmers</a></li>
                <li class="nav-item"><a class="nav-link px-3 py-2" href="farms.php"><i class="bi bi-geo-alt me-1"></i> Farms</a></li>
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
                    <h1 class="h3 fw-bold text-dark mb-0">Farmers Directory</h1>
                    <span class="badge bg-primary text-white px-3 py-1.5 rounded-pill small fw-semibold"><?= $totalFarmers ?> Registered</span>
                </div>
                <p class="text-muted small mb-0">Manage registered farmers, monitor contact information, and handle records efficiently.</p>
            </div>
            <div>
                <button class="btn btn-primary px-4 py-2 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addFarmerModal">
                    <i class="bi bi-person-plus-fill fs-5"></i><span>Add New Farmer</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
        <form method="GET" action="farmers.php" class="row g-3 align-items-center">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by first name, last name, contact number, or address...">
                </div>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="farmers.php" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                            <th class="py-3">First Name</th>
                            <th class="py-3">Last Name</th>
                            <th class="py-3">Contact Number</th>
                            <th class="py-3">Address</th>
                            <th class="py-3">Date Registered</th>
                            <th class="py-3 pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($farmers)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($farmers as $f): ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-light text-secondary border px-2 py-1 fw-bold">#<?= $counter++ ?></span></td>
                                    <td class="text-dark"><?= htmlspecialchars($f['first_name']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($f['last_name']) ?></td>
                                    <td>
                                        <?php if (!empty($f['contact_number'])): ?>
                                            <span class="text-muted small"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($f['contact_number']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($f['address'])): ?>
                                            <span class="text-muted small"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($f['address']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($f['date_registered'] ?? 'N/A') ?></td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group" role="group">
                                            <!-- View Button -->
                                            <button type="button" class="btn btn-sm btn-outline-info px-2 py-1" title="View Details"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal"
                                                    data-first-name="<?= htmlspecialchars($f['first_name'], ENT_QUOTES) ?>"
                                                    data-last-name="<?= htmlspecialchars($f['last_name'], ENT_QUOTES) ?>"
                                                    data-contact="<?= htmlspecialchars($f['contact_number'] ?? 'N/A', ENT_QUOTES) ?>"
                                                    data-address="<?= htmlspecialchars($f['address'] ?? 'N/A', ENT_QUOTES) ?>"
                                                    data-registered="<?= htmlspecialchars($f['date_registered'] ?? 'N/A', ENT_QUOTES) ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-warning px-2 py-1" title="Edit Farmer"
                                                    data-bs-toggle="modal" data-bs-target="#editModal"
                                                    data-id="<?= $f['farmer_id'] ?>"
                                                    data-first-name="<?= htmlspecialchars($f['first_name'], ENT_QUOTES) ?>"
                                                    data-last-name="<?= htmlspecialchars($f['last_name'], ENT_QUOTES) ?>"
                                                    data-contact="<?= htmlspecialchars($f['contact_number'] ?? '', ENT_QUOTES) ?>"
                                                    data-address="<?= htmlspecialchars($f['address'] ?? '', ENT_QUOTES) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" title="Delete Farmer"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                    data-id="<?= $f['farmer_id'] ?>" 
                                                    data-name="<?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name'], ENT_QUOTES) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-5"><p class="mb-0 fw-semibold">No registered farmers found.</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Farmer Modal -->
<div class="modal fade" id="addFarmerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i> Register New Farmer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">First Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="first_name" required placeholder="e.g. Juan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Last Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="last_name" required placeholder="e.g. Dela Cruz">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Contact Number</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="contact_number" placeholder="e.g. 09171234567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Address / Location</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="address" placeholder="e.g. Cebu City">
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_farmer" class="btn btn-primary px-4">Save Farmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Farmer Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-info text-white p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Farmer Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">First Name</span>
                        <span id="viewFirstName" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Last Name</span>
                        <span id="viewLastName" class="fw-bold text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Contact Number</span>
                        <span id="viewContact" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Address</span>
                        <span id="viewAddress" class="text-dark"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <span class="text-muted fw-semibold">Date Registered</span>
                        <span id="viewRegistered" class="text-primary fw-bold"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light p-3 justify-content-center">
                <button type="button" class="btn btn-secondary px-5" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Farmer Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST">
                <input type="hidden" name="farmer_id" id="editFarmerId">
                <div class="modal-header bg-warning text-dark p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Farmer Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">First Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="first_name" id="editFirstName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Last Name</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="last_name" id="editLastName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Contact Number</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="contact_number" id="editContactNumber">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small text-uppercase">Address / Location</label>
                        <input type="text" class="form-control form-control-lg fs-6" name="address" id="editAddress">
                    </div>
                </div>
                <div class="modal-footer bg-light p-3 px-4">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_farmer" class="btn btn-warning px-4 fw-semibold">Save Changes</button>
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
                <p class="text-muted mb-1">Are you sure you want to delete farmer record:</p>
                <h4 id="deleteFarmerName" class="fw-bold text-dark mb-2"></h4>
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
    // Handle View Modal Data Binding
    const viewModal = document.getElementById('viewModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('viewFirstName').textContent = button.getAttribute('data-first-name');
            document.getElementById('viewLastName').textContent = button.getAttribute('data-last-name');
            document.getElementById('viewContact').textContent = button.getAttribute('data-contact');
            document.getElementById('viewAddress').textContent = button.getAttribute('data-address');
            document.getElementById('viewRegistered').textContent = button.getAttribute('data-registered');
        });
    }

    // Handle Edit Modal Data Binding
    const editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editFarmerId').value = button.getAttribute('data-id');
            document.getElementById('editFirstName').value = button.getAttribute('data-first-name');
            document.getElementById('editLastName').value = button.getAttribute('data-last-name');
            document.getElementById('editContactNumber').value = button.getAttribute('data-contact');
            document.getElementById('editAddress').value = button.getAttribute('data-address');
        });
    }

    <?php if (!empty($popupMessage)): ?>
        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
    <?php endif; ?>
</script>
</body>
</html>
