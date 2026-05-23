<?php
require_once '../../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

if (!user_has_role(['admin'])) redirect_to('home.php');

$success = $error = '';

// ── Handle enroll ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $userId   = (int) ($_POST['user_id']   ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    if ($userId && $courseId) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO enrollments (user_id, course_id, progress) VALUES (?, ?, 0)
        ");
        $stmt->bind_param('ii', $userId, $courseId);
        $stmt->execute();
        $success = 'Student enrolled successfully.';
        $stmt->close();
    } else {
        $error = 'Please select both a student and a course.';
    }
}

// ── Handle unenroll ────────────────────────────────────────────────
if (isset($_GET['unenroll'])) {
    [$uid, $cid] = array_map('intval', explode('-', $_GET['unenroll'] . '-0'));
    $stmt = $conn->prepare("DELETE FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param('ii', $uid, $cid);
    $stmt->execute();
    $stmt->close();
    $success = 'Student unenrolled.';
}

// ── Handle progress update ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $uid      = (int) ($_POST['user_id']   ?? 0);
    $cid      = (int) ($_POST['course_id'] ?? 0);
    $progress = max(0, min(100, (int) ($_POST['progress'] ?? 0)));
    $stmt = $conn->prepare("UPDATE enrollments SET progress = ? WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param('iii', $progress, $uid, $cid);
    $stmt->execute();
    $stmt->close();
    $success = 'Progress updated.';
}

// ── Handle XP update ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_xp'])) {
    $uid   = (int) ($_POST['xp_user_id'] ?? 0);
    $xp    = max(0, (int) ($_POST['xp'] ?? 0));
    $level = max(1, (int) ($_POST['level'] ?? 1));
    $stmt  = $conn->prepare("
        INSERT INTO student_xp (user_id, xp, level)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE xp = VALUES(xp), level = VALUES(level)
    ");
    $stmt->bind_param('iii', $uid, $xp, $level);
    $stmt->execute();
    $stmt->close();
    $success = 'XP and level updated.';
}

// ── Handle badge award ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_badge'])) {
    $uid   = (int) ($_POST['badge_user_id']    ?? 0);
    $achId = (int) ($_POST['achievement_id']   ?? 0);
    if ($uid && $achId) {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO student_achievements (user_id, achievement_id) VALUES (?, ?)
        ");
        $stmt->bind_param('ii', $uid, $achId);
        $stmt->execute();
        $inserted = $stmt->affected_rows > 0;
        $stmt->close();
        $success = 'Badge awarded.';
        
        if ($inserted) {
            $stmtAch = $conn->prepare("SELECT name, icon FROM achievements WHERE id = ?");
            if ($stmtAch) {
                $stmtAch->bind_param('i', $achId);
                $stmtAch->execute();
                $resAch = $stmtAch->get_result()->fetch_assoc();
                $stmtAch->close();
                
                if ($resAch) {
                    require_once BASE_PATH . '/includes/notifications_helper.php';
                    send_notification(
                        $conn, $uid, 'achievement', 
                        "Badge Unlocked!", 
                        "You earned the '" . $resAch['name'] . "' badge.", 
                        $resAch['icon'] ?: 'bi-trophy-fill', 'warning', 
                        app_url('home.php')
                    );
                }
            }
        }
    }
}

// ── Data for dropdowns ─────────────────────────────────────────────
$students     = $conn->query("SELECT id, username FROM users WHERE role='student' ORDER BY username")->fetch_all(MYSQLI_ASSOC);
$courses      = $conn->query("SELECT id, name FROM courses ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$achievements = $conn->query("SELECT id, name, icon FROM achievements ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// ── Current enrollments ────────────────────────────────────────────
$enrollments = $conn->query("
    SELECT u.id AS user_id, u.username, c.id AS course_id, c.name AS course, e.progress
    FROM enrollments e
    JOIN users   u ON u.id = e.user_id
    JOIN courses c ON c.id = e.course_id
    ORDER BY u.username, c.name
")->fetch_all(MYSQLI_ASSOC);

// ── XP data ────────────────────────────────────────────────────────
$xpData = $conn->query("
    SELECT u.id, u.username, COALESCE(x.xp,0) AS xp, COALESCE(x.level,1) AS level
    FROM users u
    LEFT JOIN student_xp x ON x.user_id = u.id
    WHERE u.role = 'student'
    ORDER BY u.username
")->fetch_all(MYSQLI_ASSOC);

require_once BASE_PATH . '/includes/header.php';
?>

<div class="mb-4">
    <h2 class="mb-1">Course Enrollments & Gamification</h2>
    <p class="text-muted small mb-0">Manage student enrollments, progress, XP levels, and achievement badges.</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Tab Nav -->
<ul class="nav nav-pills mb-4 gap-2" id="enrollTabs">
    <li class="nav-item">
        <button class="nav-link active rounded-pill" data-bs-toggle="pill" data-bs-target="#tab-enroll">
            <i class="bi bi-person-plus-fill me-2"></i>Enrollments
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill" data-bs-toggle="pill" data-bs-target="#tab-xp">
            <i class="bi bi-star-fill me-2"></i>XP & Levels
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill" data-bs-toggle="pill" data-bs-target="#tab-badges">
            <i class="bi bi-trophy-fill me-2"></i>Award Badges
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ── TAB 1: Enrollments ──────────────────────────────────── -->
    <div class="tab-pane fade show active" id="tab-enroll">

        <!-- Enroll form -->
        <div class="card border-0 shadow-sm mb-4" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:18px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Enroll Student in Course</h6>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Student</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— Select student —</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id']; ?>"><?= htmlspecialchars($s['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">— Select course —</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="enroll" class="btn btn-primary rounded-pill w-100">
                            <i class="bi bi-plus-lg me-1"></i>Enroll
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Enrollment list with inline progress edit -->
        <?php if (!empty($enrollments)): ?>
        <div class="table-wrapper table-responsive border-0 shadow-sm" style="border-radius:20px;overflow:hidden;background:var(--app-surface);border:1px solid var(--app-border)!important;">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background:var(--app-surface-soft);">
                    <tr>
                        <th class="ps-4 py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Student</th>
                        <th class="py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Course</th>
                        <th class="py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Progress</th>
                        <th class="pe-4 py-3 text-end" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrollments as $e): ?>
                    <tr>
                        <td class="ps-4 fw-semibold" style="border-bottom:1px solid var(--app-border);">
                            <?= htmlspecialchars($e['username']); ?>
                        </td>
                        <td style="border-bottom:1px solid var(--app-border);" class="text-muted">
                            <?= htmlspecialchars($e['course']); ?>
                        </td>
                        <td style="border-bottom:1px solid var(--app-border);">
                            <form method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="user_id"   value="<?= $e['user_id']; ?>">
                                <input type="hidden" name="course_id" value="<?= $e['course_id']; ?>">
                                <input type="range" name="progress" class="form-range" style="width:120px;"
                                       min="0" max="100" value="<?= $e['progress']; ?>"
                                       oninput="this.nextElementSibling.textContent=this.value+'%'">
                                <span class="badge bg-primary-subtle text-primary" style="min-width:42px;">
                                    <?= $e['progress']; ?>%
                                </span>
                                <button type="submit" name="update_progress"
                                        class="btn btn-sm btn-outline-primary rounded-pill">Save</button>
                            </form>
                        </td>
                        <td class="pe-4 text-end" style="border-bottom:1px solid var(--app-border);">
                            <a href="?unenroll=<?= $e['user_id'] . '-' . $e['course_id']; ?>"
                               class="btn btn-sm btn-outline-danger rounded-pill"
                               onclick="return confirm('Unenroll this student from the course?')">
                                <i class="bi bi-x-lg me-1"></i>Unenroll
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm p-5 text-center" style="background:var(--app-surface);border-radius:20px;">
            <i class="bi bi-person-x text-muted" style="font-size:3rem;opacity:.5;"></i>
            <h5 class="mt-3">No enrollments yet. Use the form above to enroll students.</h5>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── TAB 2: XP & Levels ──────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-xp">
        <div class="table-wrapper table-responsive border-0 shadow-sm" style="border-radius:20px;overflow:hidden;background:var(--app-surface);border:1px solid var(--app-border)!important;">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background:var(--app-surface-soft);">
                    <tr>
                        <th class="ps-4 py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Student</th>
                        <th class="py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Current XP</th>
                        <th class="py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Level</th>
                        <th class="pe-4 py-3" style="color:var(--app-muted);font-size:.85rem;font-weight:700;">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($xpData as $x): ?>
                    <tr>
                        <td class="ps-4 fw-semibold" style="border-bottom:1px solid var(--app-border);">
                            <?= htmlspecialchars($x['username']); ?>
                        </td>
                        <td style="border-bottom:1px solid var(--app-border);">
                            <span class="badge bg-warning-subtle text-warning rounded-pill px-3">
                                ⭐ <?= number_format($x['xp']); ?> XP
                            </span>
                        </td>
                        <td style="border-bottom:1px solid var(--app-border);" class="text-muted">
                            Lv. <?= $x['level']; ?>
                        </td>
                        <td class="pe-4" style="border-bottom:1px solid var(--app-border);">
                            <form method="POST" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="xp_user_id" value="<?= $x['id']; ?>">
                                <input type="number" name="xp" class="form-control form-control-sm" style="width:90px;"
                                       value="<?= $x['xp']; ?>" min="0" placeholder="XP">
                                <input type="number" name="level" class="form-control form-control-sm" style="width:70px;"
                                       value="<?= $x['level']; ?>" min="1" max="10" placeholder="Lvl">
                                <button type="submit" name="update_xp" class="btn btn-sm btn-warning rounded-pill">
                                    <i class="bi bi-save me-1"></i>Save
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── TAB 3: Award Badges ─────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-badges">
        <div class="card border-0 shadow-sm mb-4" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:18px;">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill me-2 text-warning"></i>Award Achievement Badge</h6>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Student</label>
                        <select name="badge_user_id" class="form-select" required>
                            <option value="">— Select student —</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id']; ?>"><?= htmlspecialchars($s['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small">Achievement Badge</label>
                        <select name="achievement_id" class="form-select" required>
                            <option value="">— Select badge —</option>
                            <?php foreach ($achievements as $a): ?>
                                <option value="<?= $a['id']; ?>"><?= htmlspecialchars($a['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="award_badge" class="btn btn-warning rounded-pill w-100">
                            <i class="bi bi-trophy-fill me-1"></i>Award
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Achievement definitions reference -->
        <h6 class="fw-bold mb-3">Available Badges</h6>
        <div class="row g-3">
            <?php foreach ($achievements as $a): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3" style="background:var(--app-surface);border:1px solid var(--app-border)!important;border-radius:14px;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center"
                             style="width:40px;height:40px;font-size:1.2rem;">
                            <i class="bi <?= htmlspecialchars($a['icon']); ?>"></i>
                        </div>
                        <span class="fw-semibold"><?= htmlspecialchars($a['name']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

