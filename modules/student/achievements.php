<?php
require_once '../../config/app.php';
require_once BASE_PATH . '/config/connect.php';
require_once BASE_PATH . '/config/auth.php';

require_role('student');
$userId = current_user_id();

// Fetch XP Data
$stmtXp = $conn->prepare("SELECT xp, level FROM student_xp WHERE user_id = ?");
$stmtXp->bind_param('i', $userId);
$stmtXp->execute();
$xpData = $stmtXp->get_result()->fetch_assoc();
$stmtXp->close();
$xp = $xpData['xp'] ?? 0;
$level = $xpData['level'] ?? 1;

// Fetch All Achievements
$allAchievements = $conn->query("SELECT * FROM achievements ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch Unlocked Achievements
$stmtUnlocked = $conn->prepare("SELECT achievement_id, earned_at FROM student_achievements WHERE user_id = ?");
$stmtUnlocked->bind_param('i', $userId);
$stmtUnlocked->execute();
$unlockedRaw = $stmtUnlocked->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtUnlocked->close();

$unlockedIds = [];
$earnedDates = [];
foreach ($unlockedRaw as $u) {
    $unlockedIds[] = $u['achievement_id'];
    $earnedDates[$u['achievement_id']] = $u['earned_at'];
}

// Fetch user stats for progression calculation
$stats = [];

// Max score %
$stmtMaxScore = $conn->prepare("
    SELECT MAX(g.score / a.max_score * 100) AS max_pct
    FROM grades g JOIN assignments a ON a.id = g.assignment_id
    WHERE g.user_id = ? AND a.max_score > 0
");
$stmtMaxScore->bind_param('i', $userId);
$stmtMaxScore->execute();
$stats['max_score_pct'] = (int) ($stmtMaxScore->get_result()->fetch_assoc()['max_pct'] ?? 0);
$stmtMaxScore->close();

// Completed assignments count
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM grades WHERE user_id = ?");
$stmtCount->bind_param('i', $userId);
$stmtCount->execute();
$stats['completed_assignments'] = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

// Present days count (Approximation for streak for simplicity of progress bar)
$stmtAtt = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE user_id = ? AND status = 'present'");
$stmtAtt->bind_param('i', $userId);
$stmtAtt->execute();
$stats['present_days'] = (int) ($stmtAtt->get_result()->fetch_assoc()['total'] ?? 0);
$stmtAtt->close();

require_once BASE_PATH . '/includes/header.php';
?>

<div class="dashboard-hero mb-4">
    <div class="dashboard-hero-text">
        <h1 class="dashboard-title">My Achievements</h1>
        <p class="text-muted mb-0">Track your progress, earn XP, and unlock gamification badges.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-4">
        <div class="dashboard-card h-100 border-0 shadow-sm text-center bg-primary text-white">
            <h6 class="text-white-50 text-uppercase mb-3">Current Level</h6>
            <h1 class="display-3 fw-bold mb-0"><?= $level; ?></h1>
            <p class="text-white-50 mt-2 mb-0">Keep earning XP to rank up!</p>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-4">
        <div class="dashboard-card h-100 border-0 shadow-sm text-center">
            <h6 class="text-muted text-uppercase mb-3">Total XP</h6>
            <h1 class="display-3 fw-bold text-warning mb-0"><i class="bi bi-star-fill fs-2"></i> <?= number_format($xp); ?></h1>
            <p class="text-muted mt-2 mb-0">Experience Points</p>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="dashboard-card h-100 border-0 shadow-sm text-center">
            <h6 class="text-muted text-uppercase mb-3">Badges Unlocked</h6>
            <h1 class="display-3 fw-bold text-success mb-0"><?= count($unlockedIds); ?> <span class="fs-4 text-muted">/ <?= count($allAchievements); ?></span></h1>
            <p class="text-muted mt-2 mb-0">Achievements earned</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php foreach ($allAchievements as $ach): 
        $isUnlocked = in_array($ach['id'], $unlockedIds);
        $color = $ach['color'] ?: 'primary';
        $icon = $ach['icon'] ?: 'bi-trophy-fill';
        
        // Calculate Progression %
        $progPct = 0;
        $progText = '';
        
        if ($isUnlocked) {
            $progPct = 100;
            $progText = "Unlocked on " . date('M d, Y', strtotime($earnedDates[$ach['id']]));
        } else {
            // Hardcoded progression logic based on achievement ID/Name
            if (strpos($ach['name'], 'Top Performer') !== false) {
                $progPct = min(100, round(($stats['max_score_pct'] / 90) * 100));
                $progText = "Max score: {$stats['max_score_pct']}% / 90%";
            } elseif (strpos($ach['name'], 'Perfect Week') !== false) {
                $progPct = min(100, round(($stats['present_days'] / 7) * 100));
                $progText = "Days: {$stats['present_days']} / 7";
            } elseif (strpos($ach['name'], 'Lightning Learner') !== false) {
                $progPct = min(100, round(($stats['completed_assignments'] / 5) * 100));
                $progText = "Assignments: {$stats['completed_assignments']} / 5";
            } elseif (strpos($ach['name'], 'LMS Settler') !== false) {
                $progPct = min(100, round(($stats['completed_assignments'] / 1) * 100));
                $progText = "Assignments: {$stats['completed_assignments']} / 1";
            } elseif (strpos($ach['name'], 'Legend') !== false) {
                $progPct = min(100, round(($level / 8) * 100));
                $progText = "Level: {$level} / 8";
            } elseif (strpos($ach['name'], 'Star Pupil') !== false) {
                $progPct = min(100, round(($stats['max_score_pct'] / 100) * 100));
                $progText = "Max score: {$stats['max_score_pct']}% / 100%";
            } elseif (strpos($ach['name'], 'Scholar') !== false) {
                $progPct = min(100, round(($stats['present_days'] / 5) * 100));
                $progText = "Days: {$stats['present_days']} / 5";
            } else {
                $progPct = 0;
                $progText = "Locked";
            }
        }
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="dashboard-card h-100 border-0 shadow-sm" style="<?= $isUnlocked ? '' : 'background: var(--app-surface-soft); opacity: 0.8;'; ?> padding: 1.5rem;">
            <div class="position-relative h-100 d-flex flex-column">
                <?php if ($isUnlocked): ?>
                    <div class="position-absolute top-0 end-0 p-3">
                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-box icon-box-<?= $isUnlocked ? $color : 'secondary'; ?> me-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="bi <?= $icon; ?>"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold <?= $isUnlocked ? '' : 'text-muted'; ?>" style="<?= $isUnlocked ? 'color: var(--app-text);' : ''; ?>"><?= htmlspecialchars($ach['name']); ?></h5>
                        <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i> <?= $ach['xp_reward']; ?> XP</span>
                    </div>
                </div>
                
                <p class="<?= $isUnlocked ? '' : 'text-muted'; ?> mb-4" style="font-size: 0.9rem; min-height: 40px; <?= $isUnlocked ? 'color: var(--app-text); opacity: 0.9;' : ''; ?>">
                    <?= htmlspecialchars($ach['description']); ?>
                </p>

                <div class="mt-auto">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted fw-bold"><?= $progText; ?></small>
                        <small class="text-muted fw-bold"><?= $progPct; ?>%</small>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar <?= $isUnlocked ? 'bg-success' : 'bg-secondary'; ?>" role="progressbar" style="width: <?= $progPct; ?>%" aria-valuenow="<?= $progPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
