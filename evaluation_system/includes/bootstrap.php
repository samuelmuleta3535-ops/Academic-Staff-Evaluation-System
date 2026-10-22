<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
// Language preference is stored in the PHP session for every actor.
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'am', 'om'], true)) {
    $_SESSION['ases_lang'] = $_GET['lang'];
}
function app_lang(): string
{
    return $_SESSION['ases_lang'] ?? 'en';
}
function t(string $key): string
{
    $dict = [
        'en' => ['Dashboard' => 'Dashboard', 'My evaluation form' => 'My evaluation form', 'My submissions' => 'My submissions', 'My result' => 'My result', 'Results & workflow' => 'Results & workflow', 'User directory' => 'User directory', 'Actors & responsibilities' => 'Actors & responsibilities', 'Messages & notifications' => 'Messages & notifications', 'ADMINISTRATION' => 'ADMINISTRATION', 'Actors & accounts' => 'Actors & accounts', 'My profile' => 'My profile', 'WORKSPACE' => 'WORKSPACE', 'Welcome' => 'Welcome', 'to ASES' => 'to ASES', 'Evaluation deadline' => 'Evaluation deadline', 'No deadline set' => 'No deadline set', 'Evaluation deadline has passed. Submissions are closed.' => 'Evaluation deadline has passed. Submissions are closed.', 'Language' => 'Language', 'English' => 'English', 'Amharic' => 'Amharic', 'Afaan Oromoo' => 'Afaan Oromoo', 'Save deadline' => 'Save deadline', 'Deadline saved successfully.' => 'Deadline saved successfully.', 'Deadline settings' => 'Deadline settings', 'Deadline date and time' => 'Deadline date and time', 'No deadline' => 'No deadline'],
        'am' => ['Dashboard' => 'ዳሽቦርድ', 'My evaluation form' => 'የግምገማ ቅጼ', 'My submissions' => 'ያስገባኋቸው ግምገማዎች', 'My result' => 'ውጤቴ', 'Results & workflow' => 'ውጤቶች እና የሥራ ሂደት', 'User directory' => 'የተጠቃሚዎች ዝርዝር', 'Actors & responsibilities' => 'ተሳታፊዎች እና ኃላፊነቶች', 'Messages & notifications' => 'መልዕክቶች እና ማሳወቂያዎች', 'ADMINISTRATION' => 'አስተዳደር', 'Actors & accounts' => 'ተሳታፊዎች እና መለያዎች', 'My profile' => 'የእኔ መገለጫ', 'WORKSPACE' => 'የሥራ ቦታ', 'Secure local workspace' => 'ደህንነቱ የተጠበቀ ሥርዓት', 'Welcome' => 'እንኳን ደህና መጡ', 'to ASES' => 'ወደ ASES', 'Evaluation deadline' => 'የግምገማ ማብቂያ ቀን', 'No deadline set' => 'የማብቂያ ቀን አልተወሰነም', 'Evaluation deadline has passed. Submissions are closed.' => 'የግምገማ ማብቂያ ቀን አልፏል። ማስገባት ተዘግቷል።', 'Language' => 'ቋንቋ', 'English' => 'እንግሊዝኛ', 'Amharic' => 'አማርኛ', 'Afaan Oromoo' => 'Afaan Oromoo', 'Save deadline' => 'የማብቂያ ቀን አስቀምጥ', 'Deadline saved successfully.' => 'የማብቂያ ቀኑ ተቀምጧል።', 'Deadline settings' => 'የማብቂያ ቀን ቅንብር', 'Deadline date and time' => 'የማብቂያ ቀንና ሰዓት', 'No deadline' => 'ማብቂያ ቀን የለም'],
        'om' => ['Dashboard' => 'Daashboordii', 'My evaluation form' => 'Unka madaallii koo', 'My submissions' => 'Madaalliiwwan ani galche', 'My result' => 'Bu’aa koo', 'Results & workflow' => 'Bu’aawwan fi adeemsa hojii', 'User directory' => 'Tarree fayyadamtootaa', 'Actors & responsibilities' => 'Hirmaattota fi itti gaafatamummaa', 'Messages & notifications' => 'Ergaawwan fi beeksisawwan', 'ADMINISTRATION' => 'BULCHIINSA', 'Actors & accounts' => 'Hirmaattotaa fi herrega', 'My profile' => 'Piroofaayila koo', 'WORKSPACE' => 'BAKKA HOJII', 'Welcome' => 'Baga nagaan dhuftan', 'to ASES' => 'gara ASES', 'Evaluation deadline' => 'Guyyaa xumuraa madaallii', 'No deadline set' => 'Guyyaan xumuraa hin murtoofne', 'Evaluation deadline has passed. Submissions are closed.' => 'Guyyaan xumuraa darbeera. Galchuun cufameera.', 'Language' => 'Afaan', 'English' => 'Ingiliffa', 'Amharic' => 'Amaaraa', 'Afaan Oromoo' => 'Afaan Oromoo', 'Save deadline' => 'Guyyaa xumuraa olkaa’i', 'Deadline saved successfully.' => 'Guyyaan xumuraa olkaa’ameera.', 'Deadline settings' => 'Qindaa’ina guyyaa xumuraa', 'Deadline date and time' => 'Guyyaa fi sa’aatii xumuraa', 'No deadline' => 'Guyyaan xumuraa hin jiru']
    ];
    return $dict[app_lang()][$key] ?? $dict['en'][$key] ?? $key;
}
function evaluation_deadline(): ?string
{
    global $pdo;
    try {
        $q = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key='evaluation_deadline' LIMIT 1");
        $q->execute();
        $v = $q->fetchColumn();
        return ($v && $v !== '') ? (string)$v : null;
    } catch (Throwable $e) {
        return null;
    }
}
function evaluation_is_closed(): bool
{
    $d = evaluation_deadline();
    return $d !== null && strtotime($d) < time();
}
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function redirect(string $p): never
{
    header('Location: ' . BASE_URL . '/' . ltrim($p, '/'));
    exit;
}
function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}
function current_user(): array
{
    return $_SESSION['user'] ?? [];
}
function require_login(): void
{
    if (!is_logged_in()) redirect('login.php');
}
function roles(): array
{
    return ['admin' => 'System Administrator', 'student' => 'Student Evaluator', 'department_head' => 'Department Head', 'peer' => 'Peer Instructor', 'self' => 'Instructor / Self-Evaluator', 'academic_admin' => 'Academic Administration / Evaluation Committee', 'committee' => 'Ad-hoc Evaluation Committee / Officially Delegated Body', 'college_admin' => 'College / School / Institute Administration', 'dean' => 'College / School Dean / Vice Dean', 'academic_affairs' => 'Academic Affairs Vice President / Academic Affairs Vice Director', 'archive' => 'University / Department Archives', 'concerned_office' => 'Concerned Offices and Directorates'];
}
function require_role(array $r): void
{
    require_login();
    if (!in_array(current_user()['role'], $r, true)) {
        http_response_code(403);
        page_header('Access denied');
        echo '<div class="panel"><h1>Access denied</h1><p>Your role cannot access this feature.</p></div>';
        page_footer();
        exit;
    }
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(419);
        exit('Form expired. Refresh and try again.');
    }
}
function flash(string $t, string $m): void
{
    $_SESSION['flash'] = [$t, $m];
}
function show_flash(): void
{
    if (isset($_SESSION['flash'])) {
        [$t, $m] = $_SESSION['flash'];
        echo '<div class="alert ' . e($t) . '">' . e($m) . '</div>';
        unset($_SESSION['flash']);
    }
}
function unread_notifications_count(): int
{
    if (!is_logged_in()) return 0;
    global $pdo;
    try {
        $q = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE recipient_user_id=? AND read_at IS NULL');
        $q->execute([(int)current_user()['id']]);
        return (int)$q->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
function page_header(string $title): void
{
    $u = current_user();
    $loggedIn = is_logged_in();
    $role = (string)($u['role'] ?? '');
    $displayName = trim((string)($u['full_name'] ?? $u['name'] ?? $u['username'] ?? 'User'));
    if ($displayName === '') $displayName = 'User';
    echo '<!doctype html><html lang="' . e(app_lang()) . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($title) . ' · ' . e(APP_NAME) . '</title><link rel="stylesheet" href="' . BASE_URL . '/assets/style.css"><script defer src="' . BASE_URL . '/assets/app.js"></script></head><body><aside class="sidebar"><a class="brand" href="' . BASE_URL . '/dashboard.php"><span class="brand-mark">ASES</span><span>Academic Staff / Instructor<small>Evaluation System (ASES)</small></span></a>';
    if ($loggedIn) {
        echo '<div class="nav-label">' . e(t('WORKSPACE')) . '</div><a class="nav-link" href="' . BASE_URL . '/dashboard.php">◫ &nbsp;' . e(t('Dashboard')) . '</a>';
        if (in_array($role, ['student', 'department_head', 'peer', 'self'], true)) echo '<a class="nav-link" href="' . BASE_URL . '/evaluate.php">☷ &nbsp;My evaluation form</a><a class="nav-link" href="' . BASE_URL . '/my_evaluations.php">▤ &nbsp;My submissions</a>';
        if ($role === 'self') echo '<a class="nav-link" href="' . BASE_URL . '/my_result.php">▥ &nbsp;My result</a>';
        if (in_array($role, ['admin', 'department_head', 'academic_admin', 'committee', 'college_admin', 'dean', 'academic_affairs', 'archive', 'concerned_office'], true)) echo '<a class="nav-link" href="' . BASE_URL . '/reports.php">▥ &nbsp;Results &amp; institutional workflow</a>';
        if (in_array($role, ['admin', 'department_head', 'academic_admin', 'committee', 'college_admin', 'dean', 'academic_affairs', 'archive', 'concerned_office'], true)) echo '<a class="nav-link" href="' . BASE_URL . '/actors.php">♙ &nbsp;' . e(t('Actors & responsibilities')) . '</a>';
        echo '<a class="nav-link" href="' . BASE_URL . '/messages.php">✉ &nbsp;Messages &amp; notifications' . (unread_notifications_count() > 0 ? ' <b class="nav-count">' . unread_notifications_count() . '</b>' : '') . '</a>';
        if ($role === 'admin') echo '<div class="nav-label">' . e(t('ADMINISTRATION')) . '</div><a class="nav-link" href="' . BASE_URL . '/users.php">⚙ &nbsp;User Accounts</a><a class="nav-link" href="' . BASE_URL . '/course_management.php">▦ &nbsp;Course &amp; department management</a><a class="nav-link" href="' . BASE_URL . '/settings.php">◷ &nbsp;' . e(t('Deadline settings')) . '</a>';
        echo '<a class="nav-link" href="' . BASE_URL . '/profile.php">♙ &nbsp;' . e(t('My profile')) . '</a>';
    }
    echo '</aside><div class="main-shell"><header class="topbar"><div class="topbar-title"><small>UNIVERSITY / ACADEMIC AFFAIRS</small><strong>' . e($title) . '</strong></div><div class="topbar-right"><form class="language-picker" method="get"><label for="ases-lang">' . e(t('Language')) . '</label><select id="ases-lang" name="lang" onchange="this.form.submit()"><option value="en" ' . (app_lang() === 'en' ? 'selected' : '') . '>English</option><option value="am" ' . (app_lang() === 'am' ? 'selected' : '') . '>አማርኛ</option><option value="om" ' . (app_lang() === 'om' ? 'selected' : '') . '>Afaan Oromoo</option></select></form><button type="button" class="icon-action theme-toggle" id="theme-toggle" aria-label="Switch to dark mode" title="Switch theme"><span class="theme-icon" aria-hidden="true">☾</span></button>';
    if ($loggedIn) {
        $notificationCount = unread_notifications_count();
        echo '<a class="icon-action notification-action" href="' . BASE_URL . '/messages.php" title="Notifications" aria-label="Notifications"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>';
        if ($notificationCount > 0) echo '<span class="notification-badge">' . ($notificationCount > 99 ? '99+' : $notificationCount) . '</span>';
        echo '</a><div class="topbar-profile">';
        echo '<a class="profile-shortcut" href="' . BASE_URL . '/profile.php" title="My profile" aria-label="My profile">';
        if (!empty($u['profile_image'])) echo '<img class="avatar avatar-image" src="' . BASE_URL . '/' . e($u['profile_image']) . '" alt="Profile photo">';
        else echo '<span class="avatar">' . e(strtoupper(substr($displayName, 0, 1))) . '</span>';
        echo '</a><a class="topbar-user profile-user-link" href="' . BASE_URL . '/profile.php"><b>' . e($displayName) . '</b><small>' . e(roles()[$role] ?? ($role !== '' ? $role : 'User')) . '</small></a><a class="logout icon-action" href="' . BASE_URL . '/logout.php" title="Log out" aria-label="Log out"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></a></div>';
    }
    echo '</div></header><main class="content">';
}
function page_footer(): void
{
    echo '</main><footer>Academic Staff / Instructor Evaluation System (ASES) · Handle evaluation data according to institutional privacy rules.</footer></div></body></html>';
}
function weights(): array
{
    return ['student' => 40, 'department_head' => 35, 'peer' => 15, 'self' => 10];
}
