<?php
/**
 * modules/student/dashboard.php
 *
 * Student LMS portal partial — included by home.php when role === 'student'.
 * All widgets query real DB tables with safe array fallbacks if tables are
 * empty or not yet populated.
 *
 * Requires: $conn (mysqli), current_user_id(), app_url().
 */

$userId   = current_user_id();
$username = htmlspecialchars($_SESSION['username'] ?? 'Student', ENT_QUOTES, 'UTF-8');

// ═══════════════════════════════════════════════════════════════════════
// 1. XP & LEVEL  →  student_xp
// ═══════════════════════════════════════════════════════════════════════
$xpCurrent = 0; $xpNext = 500; $level = 1; $rank = 'New Student';

$stmtXp = $conn->prepare(
    "SELECT xp, level FROM student_xp WHERE user_id = ? LIMIT 1"
);
if ($stmtXp) {
    $stmtXp->bind_param('i', $userId);
    $stmtXp->execute();
    $rowXp = $stmtXp->get_result()->fetch_assoc();
    $stmtXp->close();

    if ($rowXp) {
        $xpCurrent = (int) $rowXp['xp'];
        $level     = (int) $rowXp['level'];
        // XP thresholds: each level needs level*500 XP total
        $xpNext    = $level * 500;
        $ranks     = ['New Student','Apprentice','Explorer','Achiever',
                      'Rising Scholar','Advanced Scholar','Expert','Master',
                      'Grand Master','Legend'];
        $rank      = $ranks[min($level - 1, count($ranks) - 1)];
    } else {
        // student_xp row missing — show sensible defaults
        $xpCurrent = 0; $xpNext = 500; $level = 1; $rank = 'New Student';
    }
}
$xpPercent = $xpNext > 0 ? (int) min(round(($xpCurrent / $xpNext) * 100), 100) : 0;

// ═══════════════════════════════════════════════════════════════════════
// 2. ENROLLED COURSES  →  enrollments + courses + teachers
// ═══════════════════════════════════════════════════════════════════════
$courses = [];

$stmtC = $conn->prepare("
    SELECT
        c.id,
        c.name,
        c.icon,
        c.color,
        c.description,
        COALESCE(t.name, 'TBA') AS teacher,
        e.progress
    FROM enrollments e
    JOIN courses     c ON c.id = e.course_id
    LEFT JOIN teachers t ON t.id = c.teacher_id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at ASC
");
if ($stmtC) {
    $stmtC->bind_param('i', $userId);
    $stmtC->execute();
    $resC = $stmtC->get_result();
    while ($row = $resC->fetch_assoc()) {
        $courses[] = [
            'id'       => (int)$row['id'],
            'name'     => $row['name'],
            'icon'     => $row['icon']  ?: 'bi-book-fill',
            'color'    => $row['color'] ?: 'primary',
            'progress' => (int)$row['progress'],
            'teacher'  => htmlspecialchars($row['teacher'], ENT_QUOTES, 'UTF-8'),
            'next'     => htmlspecialchars($row['description'] ?? 'Continue learning', ENT_QUOTES, 'UTF-8'),
        ];
    }
    $stmtC->close();
}

// Fallback when student has no enrollments yet
if (empty($courses)) {
    $courses = [
        ['id'=>0,'name'=>'Mathematics','icon'=>'bi-calculator',            'color'=>'primary','progress'=>0,'teacher'=>'TBA','next'=>'Not enrolled yet'],
        ['id'=>0,'name'=>'Science',    'icon'=>'bi-lightning-charge-fill', 'color'=>'success','progress'=>0,'teacher'=>'TBA','next'=>'Not enrolled yet'],
    ];
}

$avgCompletion = (int) round(
    array_sum(array_column($courses, 'progress')) / max(count($courses), 1)
);

// ═══════════════════════════════════════════════════════════════════════
// 3. ATTENDANCE STREAK  →  attendance
// ═══════════════════════════════════════════════════════════════════════
$streak     = 0;
$weeklyDays = [];
$dayLabels  = ['M','T','W','T','F','S','S'];

// Fetch the last 7 distinct dates for this student (any course)
$stmtAtt = $conn->prepare("
    SELECT DISTINCT date, status
    FROM attendance
    WHERE user_id = ?
      AND date >= CURDATE() - INTERVAL 6 DAY
    ORDER BY date ASC
");
if ($stmtAtt) {
    $stmtAtt->bind_param('i', $userId);
    $stmtAtt->execute();
    $attRows = $stmtAtt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtAtt->close();

    // Build a map date→status
    $attMap = [];
    foreach ($attRows as $ar) {
        // If multiple courses on same day, 'present' wins
        $d = $ar['date'];
        if (!isset($attMap[$d]) || $ar['status'] === 'present') {
            $attMap[$d] = $ar['status'];
        }
    }

    // Build 7-day display (Mon–Sun of the current week)
    $monday = strtotime('monday this week');
    for ($i = 0; $i < 7; $i++) {
        $dayDate = date('Y-m-d', strtotime("+{$i} days", $monday));
        $status  = $attMap[$dayDate] ?? null;
        $done    = in_array($status, ['present', 'late', 'excused'], true);
        $weeklyDays[] = ['day' => $dayLabels[$i], 'done' => $done];
    }

    // Consecutive streak ending today
    $streak = 0;
    $check  = strtotime('today');
    while (true) {
        $d = date('Y-m-d', $check);
        if (isset($attMap[$d]) && in_array($attMap[$d], ['present','late','excused'], true)) {
            $streak++;
            $check = strtotime('-1 day', $check);
        } else {
            break;
        }
    }
}

// Fallback: no attendance data yet
if (empty($weeklyDays)) {
    foreach ($dayLabels as $lbl) {
        $weeklyDays[] = ['day' => $lbl, 'done' => false];
    }
}

// ═══════════════════════════════════════════════════════════════════════
// 4. ACHIEVEMENTS  →  student_achievements + achievements
// ═══════════════════════════════════════════════════════════════════════
$badges = [];

$stmtBadge = $conn->prepare("
    SELECT a.name, a.icon, a.color, a.description AS desc_, sa.earned_at
    FROM student_achievements sa
    JOIN achievements a ON a.id = sa.achievement_id
    WHERE sa.user_id = ?
    ORDER BY sa.earned_at DESC
    LIMIT 5
");
if ($stmtBadge) {
    $stmtBadge->bind_param('i', $userId);
    $stmtBadge->execute();
    $resBadge = $stmtBadge->get_result();
    while ($row = $resBadge->fetch_assoc()) {
        $badges[] = [
            'icon'  => $row['icon']  ?: 'bi-trophy-fill',
            'color' => $row['color'] ?: 'warning',
            'name'  => htmlspecialchars($row['name'],  ENT_QUOTES, 'UTF-8'),
            'desc'  => htmlspecialchars($row['desc_'], ENT_QUOTES, 'UTF-8'),
        ];
    }
    $stmtBadge->close();
}

// Fallback
if (empty($badges)) {
    $badges = [
        ['icon'=>'bi-lock-fill','color'=>'secondary','name'=>'No badges yet','desc'=>'Complete courses to earn badges'],
    ];
}

// ═══════════════════════════════════════════════════════════════════════
// 5. UPCOMING DEADLINES  →  assignments + enrollments
// ═══════════════════════════════════════════════════════════════════════
$deadlines = [];

$stmtDl = $conn->prepare("
    SELECT a.title, c.name AS subject, a.due_date,
           DATEDIFF(a.due_date, CURDATE()) AS days_left
    FROM assignments a
    JOIN courses     c  ON c.id  = a.course_id
    JOIN enrollments e  ON e.course_id = a.course_id AND e.user_id = ?
    WHERE a.due_date >= CURDATE()
    ORDER BY a.due_date ASC
    LIMIT 4
");
if ($stmtDl) {
    $stmtDl->bind_param('i', $userId);
    $stmtDl->execute();
    $resDl = $stmtDl->get_result();
    while ($row = $resDl->fetch_assoc()) {
        $d = (int) $row['days_left'];
        $deadlines[] = [
            'subject' => htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8'),
            'task'    => htmlspecialchars($row['title'],   ENT_QUOTES, 'UTF-8'),
            'due'     => $d === 0 ? 'Today' : ($d === 1 ? 'Tomorrow' : "In {$d} days"),
            'urgent'  => $d <= 1,
        ];
    }
    $stmtDl->close();
}

// Fallback
if (empty($deadlines)) {
    $deadlines = [
        ['subject'=>'—','task'=>'No upcoming deadlines','due'=>'—','urgent'=>false],
    ];
}

// ═══════════════════════════════════════════════════════════════════════
// 6. NOTIFICATIONS  →  From centralized persistent table
// ═══════════════════════════════════════════════════════════════════════
require_once BASE_PATH . '/includes/notifications_helper.php';
$dbNotifs = get_notifications($conn, $userId, 4);

$notifications = [];
foreach ($dbNotifs as $n) {
    $notifications[] = [
        'icon'  => $n['icon'] ?: 'bi-bell-fill',
        'color' => $n['color'] ?: 'primary',
        'text'  => htmlspecialchars($n['title'], ENT_QUOTES) . ' — ' . htmlspecialchars($n['message'], ENT_QUOTES),
        'time'  => _relative_time($n['created_at'])
    ];
}

// Fallback
if (empty($notifications)) {
    $notifications = [
        ['icon'=>'bi-bell-fill','color'=>'secondary','text'=>'No new notifications','time'=>'—'],
    ];
}

// ═══════════════════════════════════════════════════════════════════════
// 7. RECENT ACTIVITY FEED  →  grades + attendance + student_achievements
// ═══════════════════════════════════════════════════════════════════════
$activity = [];

// Grades
$stmtAG = $conn->prepare("
    SELECT 'grade' AS type, g.graded_at AS ts,
           CONCAT(c.name, ' — ', a.title, ': ', g.score, '/', a.max_score) AS text_
    FROM grades      g
    JOIN assignments a ON a.id = g.assignment_id
    JOIN courses     c ON c.id = a.course_id
    WHERE g.user_id = ?
    ORDER BY g.graded_at DESC LIMIT 3
");
if ($stmtAG) {
    $stmtAG->bind_param('i', $userId);
    $stmtAG->execute();
    $resAG = $stmtAG->get_result();
    while ($r = $resAG->fetch_assoc()) {
        $activity[] = ['icon'=>'bi-star-fill','color'=>'warning',
            'text'=>htmlspecialchars($r['text_'], ENT_QUOTES, 'UTF-8'),
            'time'=>_relative_time($r['ts'])];
    }
    $stmtAG->close();
}

// Attendance
$stmtAA = $conn->prepare("
    SELECT 'attendance' AS type, recorded_at AS ts, date, status,
           c.name AS course
    FROM attendance att
    JOIN courses c ON c.id = att.course_id
    WHERE att.user_id = ?
    ORDER BY att.recorded_at DESC LIMIT 3
");
if ($stmtAA) {
    $stmtAA->bind_param('i', $userId);
    $stmtAA->execute();
    $resAA = $stmtAA->get_result();
    while ($r = $resAA->fetch_assoc()) {
        $label = ucfirst($r['status']) . ' — ' . htmlspecialchars($r['course'], ENT_QUOTES) . ' on ' . date('d M', strtotime($r['date']));
        $color = $r['status'] === 'present' ? 'success' : ($r['status'] === 'absent' ? 'danger' : 'warning');
        $activity[] = ['icon'=>'bi-person-check-fill','color'=>$color,
            'text'=>$label,'time'=>_relative_time($r['ts'])];
    }
    $stmtAA->close();
}

// Achievements
$stmtAAch = $conn->prepare("
    SELECT a.name, sa.earned_at AS ts
    FROM student_achievements sa
    JOIN achievements a ON a.id = sa.achievement_id
    WHERE sa.user_id = ?
    ORDER BY sa.earned_at DESC LIMIT 2
");
if ($stmtAAch) {
    $stmtAAch->bind_param('i', $userId);
    $stmtAAch->execute();
    $resAAch = $stmtAAch->get_result();
    while ($r = $resAAch->fetch_assoc()) {
        $activity[] = ['icon'=>'bi-trophy-fill','color'=>'warning',
            'text'=>'Earned "' . htmlspecialchars($r['name'], ENT_QUOTES) . '" badge',
            'time'=>_relative_time($r['ts'])];
    }
    $stmtAAch->close();
}

// Sort all feed items newest first, cap at 5
usort($activity, fn($a, $b) => 0); // already ordered by recency per source
$activity = array_slice($activity, 0, 5);

// Fallback
if (empty($activity)) {
    $activity = [
        ['icon'=>'bi-info-circle-fill','color'=>'secondary',
         'text'=>'No recent activity yet — start a course!','time'=>'—'],
    ];
}

// ── Helper: human-readable relative time ─────────────────────────────
function _relative_time(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return (int)($diff/60)   . ' min ago';
    if ($diff < 86400)  return (int)($diff/3600)  . ' hours ago';
    if ($diff < 604800) return (int)($diff/86400) . ' days ago';
    return date('d M', strtotime($ts));
}
?>

<!-- ══════════════════════════════════════════════════════════════
     STUDENT DASHBOARD  —  2-column: main content + activity panel
     ══════════════════════════════════════════════════════════════ -->
<div class="student-layout">

    <!-- ══ LEFT: Main content ══════════════════════════════════ -->
    <div class="student-main">

        <!-- XP Hero Card -->
        <div class="xp-hero-card mb-4">
            <div class="xp-hero-left">
                <div class="xp-level-badge">
                    <span class="xp-level-number"><?= $level; ?></span>
                    <span class="xp-level-label">Level</span>
                </div>
                <div class="xp-hero-info">
                    <span class="xp-greeting">Welcome back</span>
                    <h1 class="xp-hero-name"><?= $username; ?></h1>
                    <span class="xp-rank-label"><i class="bi bi-mortarboard-fill me-1"></i><?= $rank; ?></span>
                </div>
            </div>
            <div class="xp-hero-right">
                <div class="xp-bar-wrapper">
                    <div class="xp-bar-header">
                        <span class="xp-bar-label"><i class="bi bi-star-fill me-1"></i>Experience Points</span>
                        <span class="xp-bar-value"><?= number_format($xpCurrent); ?> / <?= number_format($xpNext); ?> XP</span>
                    </div>
                    <div class="xp-bar-track">
                        <div class="xp-bar-fill" style="--xp-w: <?= $xpPercent; ?>%"></div>
                    </div>
                    <div class="xp-bar-footer">
                        <span><?= $xpPercent; ?>% to Level <?= $level + 1; ?></span>
                        <span><?= number_format($xpNext - $xpCurrent); ?> XP to go</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gamification Stat Cards -->
        <div class="gamification-grid mb-4">
            <div class="gamification-stat gamification-stat-fire">
                <div class="gstat-icon"><i class="bi bi-fire"></i></div>
                <div class="gstat-body">
                    <div class="gstat-value"><?= $streak; ?>-Day</div>
                    <div class="gstat-label">Attendance Streak</div>
                </div>
            </div>
            <div class="gamification-stat gamification-stat-primary">
                <div class="gstat-icon">
                    <svg class="progress-ring" viewBox="0 0 36 36" aria-hidden="true">
                        <path class="progress-ring-bg"   d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="progress-ring-fill" stroke-dasharray="<?= $avgCompletion; ?>,100"
                              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <text x="18" y="20.5" class="progress-ring-text"><?= $avgCompletion; ?>%</text>
                    </svg>
                </div>
                <div class="gstat-body">
                    <div class="gstat-value"><?= $avgCompletion; ?>%</div>
                    <div class="gstat-label">Course Completion</div>
                </div>
            </div>
            <div class="gamification-stat gamification-stat-gold">
                <div class="gstat-icon"><i class="bi bi-trophy-fill"></i></div>
                <div class="gstat-body">
                    <div class="gstat-value"><?= count($badges); ?> Earned</div>
                    <div class="gstat-label">Achievements</div>
                </div>
            </div>
            <div class="gamification-stat gamification-stat-success">
                <div class="gstat-icon"><i class="bi bi-book-fill"></i></div>
                <div class="gstat-body">
                    <div class="gstat-value"><?= count($courses); ?> Active</div>
                    <div class="gstat-label">Enrolled Courses</div>
                </div>
            </div>
        </div>

        <!-- My Courses -->
        <div class="student-section-header mb-3">
            <div>
                <h2 class="student-section-title">My Courses</h2>
                <p class="student-section-sub">Your active learning paths</p>
            </div>
            <a href="<?= app_url('modules/placeholder.php?module=My+Courses'); ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                View all <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="courses-grid mb-4">
            <?php foreach ($courses as $course): ?>
            <div class="course-card">
                <div class="course-card-header course-color-<?= $course['color']; ?>">
                    <i class="bi <?= $course['icon']; ?> course-card-icon"></i>
                    <span class="course-card-progress-chip"><?= $course['progress']; ?>%</span>
                </div>
                <div class="course-card-body">
                    <h5 class="course-card-name"><?= $course['name']; ?></h5>
                    <p class="course-card-teacher"><i class="bi bi-person-fill me-1"></i><?= $course['teacher']; ?></p>
                    <div class="course-progress-track">
                        <div class="course-progress-fill" style="--prog: <?= $course['progress']; ?>%"></div>
                    </div>
                    <p class="course-card-next">
                        <i class="bi bi-play-circle-fill me-1"></i>
                        <span><?= $course['next']; ?></span>
                    </p>
                </div>
                <a href="<?= app_url('modules/student/courses.php#course-' . $course['id']); ?>"
                   class="course-card-cta stretched-link" aria-label="Open <?= htmlspecialchars($course['name']); ?>"></a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Activity -->
        <div class="student-section-header mb-3">
            <div>
                <h2 class="student-section-title">Recent Activity</h2>
                <p class="student-section-sub">Your last interactions</p>
            </div>
        </div>

        <div class="dashboard-card activity-feed-card mb-4">
            <?php foreach ($activity as $i => $item): ?>
            <div class="activity-feed-item <?= $i < count($activity) - 1 ? 'activity-feed-item-border' : ''; ?>">
                <div class="activity-feed-dot activity-dot-<?= $item['color']; ?>">
                    <i class="bi <?= $item['icon']; ?>"></i>
                </div>
                <div class="activity-feed-content">
                    <p class="activity-feed-text"><?= $item['text']; ?></p>
                    <span class="activity-feed-time"><i class="bi bi-clock me-1"></i><?= $item['time']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div><!-- /.student-main -->

    <!-- ══ RIGHT: Activity Panel ════════════════════════════════ -->
    <div class="student-panel">

        <!-- Weekly Streak -->
        <div class="dashboard-card streak-panel mb-3">
            <div class="streak-panel-header">
                <h6 class="streak-panel-title"><i class="bi bi-fire text-danger me-1"></i>Weekly Streak</h6>
                <p class="streak-panel-sub"><?= $streak; ?> days in a row — keep it up!</p>
            </div>
            <div class="streak-days-row">
                <?php foreach ($weeklyDays as $day): ?>
                <div class="streak-day <?= $day['done'] ? 'streak-day-done' : ''; ?>">
                    <div class="streak-day-dot">
                        <?php if ($day['done']): ?><i class="bi bi-check-lg"></i><?php endif; ?>
                    </div>
                    <span class="streak-day-label"><?= $day['day']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Notifications -->
        <div class="dashboard-card panel-card mb-3">
            <div class="panel-card-header">
                <h6 class="panel-card-title"><i class="bi bi-bell-fill me-2"></i>Notifications</h6>
                <span class="panel-card-badge"><?= count($notifications); ?></span>
            </div>
            <div class="panel-card-body">
                <?php foreach ($notifications as $n): ?>
                <div class="notif-item">
                    <span class="notif-icon notif-icon-<?= $n['color']; ?>"><i class="bi <?= $n['icon']; ?>"></i></span>
                    <div class="notif-content">
                        <p class="notif-text"><?= $n['text']; ?></p>
                        <span class="notif-time"><?= $n['time']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="dashboard-card panel-card mb-3">
            <div class="panel-card-header">
                <h6 class="panel-card-title"><i class="bi bi-calendar-event-fill me-2"></i>Upcoming</h6>
            </div>
            <div class="panel-card-body">
                <?php foreach ($deadlines as $dl): ?>
                <div class="deadline-item <?= $dl['urgent'] ? 'deadline-urgent' : ''; ?>">
                    <div class="deadline-left">
                        <span class="deadline-subject"><?= $dl['subject']; ?></span>
                        <span class="deadline-task"><?= $dl['task']; ?></span>
                    </div>
                    <span class="deadline-due <?= $dl['urgent'] ? 'deadline-due-urgent' : ''; ?>">
                        <?= $dl['urgent'] ? '<i class="bi bi-exclamation-triangle-fill me-1"></i>' : ''; ?>
                        <?= $dl['due']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Achievement Badges -->
        <div class="dashboard-card panel-card">
            <div class="panel-card-header">
                <h6 class="panel-card-title"><i class="bi bi-trophy-fill me-2"></i>Achievements</h6>
                <span class="panel-card-link">
                    <a href="<?= app_url('modules/student/achievements.php'); ?>">View all</a>
                </span>
            </div>
            <div class="panel-card-body">
                <?php foreach ($badges as $badge): ?>
                <div class="achievement-badge-item">
                    <div class="achievement-badge-icon achievement-badge-<?= $badge['color']; ?>">
                        <i class="bi <?= $badge['icon']; ?>"></i>
                    </div>
                    <div class="achievement-badge-info">
                        <span class="achievement-badge-name"><?= $badge['name']; ?></span>
                        <span class="achievement-badge-desc"><?= $badge['desc']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /.student-panel -->

</div><!-- /.student-layout -->

