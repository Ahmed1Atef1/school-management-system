<?php

require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';
require_role('admin');
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
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE username LIKE ? OR role LIKE ?");
    $searchWildcard = "%$search%";
    $countStmt->bind_param("ss", $searchWildcard, $searchWildcard);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE username LIKE ? OR role LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $searchWildcard, $searchWildcard, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $totalRows = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

$totalPages = ceil($totalRows / $limit);

// --- 4. Role Badge Renderer ---
function get_role_badge(string $role): string
{
    $roleLower = strtolower($role);
    if ($roleLower === 'admin') {
        return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fw-semibold"><i class="bi bi-shield-lock-fill me-1"></i> Admin</span>';
    } elseif ($roleLower === 'teacher') {
        return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-semibold"><i class="bi bi-person-video3 me-1"></i> Teacher</span>';
    } elseif ($roleLower === 'student') {
        return '<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-semibold"><i class="bi bi-mortarboard-fill me-1"></i> Student</span>';
    } else {
        return '<span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 fw-semibold">' . htmlspecialchars($role) . '</span>';
    }
}
?>

<!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Users</h2>
            <p class="text-muted small mb-0">System user credentials, global roles, and creation timestamps.</p>
        </div>
        <a href="<?= app_url('modules/admin/users/create.php'); ?>"
           class="btn btn-success rounded-pill d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i> Add User
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
                                   placeholder="Search users by username or role..." 
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
                        <th class="py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">Username</th>
                        <th class="py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">Role</th>
                        <th class="py-3" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);">Added</th>
                        <th class="pe-4 py-3 text-end" style="color: var(--app-muted); font-size: 0.85rem; font-weight: 700; border-bottom: 1px solid var(--app-border);" width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4 fw-medium text-muted" style="border-bottom: 1px solid var(--app-border);"><?= $row['id']; ?></td>
                            <td style="border-bottom: 1px solid var(--app-border);">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle d-flex align-items-center justify-content-center fw-bold bg-secondary-subtle text-secondary rounded-circle" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($row['username'], 0, 1)); ?>
                                    </div>
                                    <span class="fw-semibold"><?= htmlspecialchars($row['username']); ?></span>
                                </div>
                            </td>
                            <td style="border-bottom: 1px solid var(--app-border);">
                                <?= get_role_badge($row['role']); ?>
                            </td>
                            <td class="text-muted" style="border-bottom: 1px solid var(--app-border);">
                                <i class="bi bi-calendar3 me-1 text-muted opacity-70"></i> 
                                <?= date('M j, Y \a\t g:i A', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="pe-4 text-end" style="border-bottom: 1px solid var(--app-border);">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?= app_url('modules/admin/users/update.php?id=' . $row['id']); ?>"
                                       class="btn btn-warning btn-sm rounded-pill d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </a>

                                    <a href="<?= app_url('modules/admin/users/delete.php?id=' . $row['id']); ?>"
                                       class="btn btn-danger btn-sm rounded-pill d-inline-flex align-items-center gap-1"
                                       onclick="return confirm('Are you sure you want to delete this user?')">
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
                <i class="bi bi-person-gear text-muted" style="font-size: 3rem; opacity: 0.6;"></i>
            </div>
            <?php if ($search !== ''): ?>
                <h4 class="empty-state-title fw-bold">No Results Found</h4>
                <p class="empty-state-text text-muted">We couldn't find any matches for "<strong><?= htmlspecialchars($search); ?></strong>". Try checking the spelling or query.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="?" class="btn btn-outline-secondary rounded-pill">Clear Search</a>
                </div>
            <?php else: ?>
                <h4 class="empty-state-title fw-bold">No Users Found</h4>
                <p class="empty-state-text text-muted">Get started by adding your first user to the system.</p>
                <div class="d-flex justify-content-center">
                    <a href="<?= app_url('modules/admin/users/create.php'); ?>" class="btn btn-success rounded-pill">
                        <i class="bi bi-plus-lg"></i> Add User
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>


