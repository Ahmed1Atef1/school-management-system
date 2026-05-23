<?php

require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_once BASE_PATH . '/includes/header.php';

// --- 1. Search Query ---
$search = trim($_GET['search'] ?? '');

// --- 2. Pagination Configuration ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit < 1) $limit = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// --- 3. Total Count and Fetch Statements ---
if ($search !== '') {
    $countStmt = $conn->prepare("SELECT COUNT(DISTINCT t.id) as total FROM teachers t LEFT JOIN courses c ON c.teacher_id = t.id WHERE t.name LIKE ? OR t.email LIKE ? OR c.name LIKE ?");
    $searchWildcard = "%$search%";
    $countStmt->bind_param("sss", $searchWildcard, $searchWildcard, $searchWildcard);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $stmt = $conn->prepare("SELECT t.id, t.name, t.email, t.phone, GROUP_CONCAT(c.name SEPARATOR ', ') as assigned_courses FROM teachers t LEFT JOIN courses c ON c.teacher_id = t.id WHERE t.name LIKE ? OR t.email LIKE ? OR c.name LIKE ? GROUP BY t.id LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii", $searchWildcard, $searchWildcard, $searchWildcard, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $totalRows = $conn->query("SELECT COUNT(*) as total FROM teachers")->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT t.id, t.name, t.email, t.phone, GROUP_CONCAT(c.name SEPARATOR ', ') as assigned_courses FROM teachers t LEFT JOIN courses c ON c.teacher_id = t.id GROUP BY t.id LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

$totalPages = ceil($totalRows / $limit);
?>

<!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Teachers</h2>
            <p class="text-muted small mb-0">Manage faculty staff members, details, and permissions.</p>
        </div>
        <a href="<?= app_url('modules/admin/teachers/create.php'); ?>"
           class="btn btn-success rounded-pill d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i> Add Teacher
        </a>
    </div>

    <!-- Search/Filter Toolbar -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--app-surface); border: 1px solid var(--app-border) !important; border-radius: 18px;">
        <div class="card-body p-3">
            <form method="GET" action="" id="searchForm">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-color: var(--app-border);">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   class="form-control border-start-0 ps-0" 
                                   placeholder="Search teachers by name or email..." 
                                   value="<?= htmlspecialchars($search); ?>"
                                   style="border-color: var(--app-border);">
                            <?php if ($search !== ''): ?>
                                <a href="?" class="btn btn-outline-secondary d-flex align-items-center" style="border-color: var(--app-border);">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small text-nowrap">Show:</span>
                            <select name="limit" class="form-select form-select-sm" onchange="document.getElementById('searchForm').submit()" style="width: auto; min-width: 70px;">
                                <option value="5" <?= $limit === 5 ? 'selected' : ''; ?>>5</option>
                                <option value="10" <?= $limit === 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?= $limit === 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?= $limit === 50 ? 'selected' : ''; ?>>50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Container -->
    <?php if ($totalRows > 0): ?>
        <div class="table-wrapper table-responsive border-0 shadow-sm" style="border-radius: 20px; overflow: hidden; padding: 0; background: var(--app-surface); border: 1px solid var(--app-border) !important;">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light" style="background-color: var(--app-surface-soft) !important;">
                    <tr>
                        <th class="ps-4 py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">ID</th>
                        <th class="py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">Name</th>
                        <th class="py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">Email</th>
                        <th class="py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">Assigned Courses</th>
                        <th class="py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">Phone</th>
                        <th class="pe-4 py-3 text-end" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);" width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-medium text-muted" style="border-bottom: 1px solid var(--app-border);"><?= $row['id']; ?></td>
                            <td style="border-bottom: 1px solid var(--app-border);">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle d-flex align-items-center justify-content-center fw-bold bg-success-subtle text-success rounded-circle" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($row['name'], 0, 1)); ?>
                                    </div>
                                    <span class="fw-semibold" style="color: var(--app-text);"><?= htmlspecialchars($row['name']); ?></span>
                                </div>
                            </td>
                            <td class="text-muted" style="border-bottom: 1px solid var(--app-border);"><?= htmlspecialchars($row['email']); ?></td>
                            <td style="border-bottom: 1px solid var(--app-border);">
                                <?php if (!empty($row['assigned_courses'])): ?>
                                    <?php 
                                    $courses = explode(', ', $row['assigned_courses']); 
                                    foreach ($courses as $idx => $course): 
                                        if ($idx > 2) {
                                            echo '<span class="badge bg-secondary-subtle text-secondary rounded-pill me-1">+'.(count($courses)-3).' more</span>';
                                            break;
                                        }
                                    ?>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill me-1"><?= htmlspecialchars($course); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic small">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="border-bottom: 1px solid var(--app-border);"><?= htmlspecialchars($row['phone'] ?: 'N/A'); ?></td>
                            <td class="pe-4 text-end" style="border-bottom: 1px solid var(--app-border);">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= app_url('modules/admin/teachers/update.php?id=' . $row['id']); ?>"
                                       class="btn btn-warning btn-sm rounded-pill d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </a>

                                    <a href="<?= app_url('modules/admin/teachers/delete.php?id=' . $row['id']); ?>"
                                       class="btn btn-danger btn-sm rounded-pill d-inline-flex align-items-center gap-1"
                                       onclick="return confirm('Are you sure you want to delete this teacher?')">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Section -->
        <?php if ($totalPages > 1): ?>
            <nav class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 gap-3">
                <div class="text-muted small">
                    Showing <strong><?= min($offset + 1, $totalRows); ?></strong> to <strong><?= min($offset + $limit, $totalRows); ?></strong> of <strong><?= $totalRows; ?></strong> entries
                </div>
                <ul class="pagination pagination-rounded mb-0">
                    <!-- Previous Button -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>"><?= $totalPages; ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state card border-0 shadow-sm p-5 text-center mb-4" style="background: var(--app-surface); border: 1px solid var(--app-border) !important; border-radius: 28px;">
            <div class="empty-state-icon mb-3">
                <i class="bi bi-person-video3 text-muted" style="font-size: 3rem; opacity: 0.6;"></i>
            </div>
            <?php if ($search !== ''): ?>
                <h4 class="empty-state-title fw-bold">No Results Found</h4>
                <p class="empty-state-text text-muted">We couldn't find any matches for "<strong><?= htmlspecialchars($search); ?></strong>". Try checking the spelling or query.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="?" class="btn btn-outline-secondary rounded-pill">Clear Search</a>
                </div>
            <?php else: ?>
                <h4 class="empty-state-title fw-bold">No Teachers Found</h4>
                <p class="empty-state-text text-muted">Get started by adding your first teacher to the system.</p>
                <div class="d-flex justify-content-center">
                    <a href="<?= app_url('modules/admin/teachers/create.php'); ?>" class="btn btn-success rounded-pill">
                        <i class="bi bi-plus-lg"></i> Add Teacher
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


