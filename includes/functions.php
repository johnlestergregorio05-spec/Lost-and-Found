<?php
require_once __DIR__ . '/config.php';

// ---------- Output / redirect helpers ----------
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }

function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }

function get_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back and try again.');
    }
}

// ---------- Auth / roles ----------
function is_logged_in(): bool { return !empty($_SESSION['user']); }

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function has_role(string ...$roles): bool {
    return is_logged_in() && in_array($_SESSION['user']['role_name'], $roles, true);
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        flash('danger', 'Access denied: your role cannot open that page.');
        redirect('dashboard.php');
    }
}

// ---------- Audit log ----------
function audit(string $action, string $entity, ?int $entityId = null, ?string $details = null, ?int $userId = null): void {
    global $pdo;
    $uid = $userId ?? ($_SESSION['user']['user_id'] ?? null);
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([$uid, $action, $entity, $entityId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

// ---------- Photo upload ----------
/** Returns stored filename, null if no file sent, or throws RuntimeException on error. */
function handle_photo_upload(string $field = 'photo'): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Photo upload failed. Try again.');
    if ($f['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('Photo must be 2 MB or smaller.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Could not save the photo.');
    return $name;
}

function delete_photo(?string $name): void {
    if ($name && is_file(UPLOAD_DIR . $name)) @unlink(UPLOAD_DIR . $name);
}

function photo_url(?string $name): string {
    return $name ? url('uploads/' . rawurlencode($name)) : url('assets/img/no-photo.svg');
}

// ---------- Lookups ----------
function get_categories(): array {
    global $pdo;
    return $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();
}

function get_locations(): array {
    global $pdo;
    return $pdo->query('SELECT * FROM locations ORDER BY location_name')->fetchAll();
}

/** Builds WHERE clause + params for item search/filter. Used by both page and AJAX endpoint. */
function build_item_filter(array $src, bool $publicOnly = true): array {
    $where = []; $params = [];
    if ($publicOnly) $where[] = "i.approval_status = 'Approved'";

    if (!empty($src['q'])) {
        $where[] = '(i.item_name LIKE ? OR i.description LIKE ?)';
        $like = '%' . $src['q'] . '%';
        array_push($params, $like, $like);
    }
    if (!empty($src['type']) && in_array($src['type'], ['Lost', 'Found'], true)) {
        $where[] = 'i.report_type = ?'; $params[] = $src['type'];
    }
    if (!empty($src['status']) && in_array($src['status'], ['Claimed', 'Unclaimed'], true)) {
        $where[] = 'i.claim_status = ?'; $params[] = $src['status'];
    }
    if (!empty($src['category'])) { $where[] = 'i.category_id = ?'; $params[] = (int)$src['category']; }
    if (!empty($src['location'])) { $where[] = 'i.location_id = ?'; $params[] = (int)$src['location']; }
    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

function status_badge(string $status): string {
    $map = ['Unclaimed' => 'warning', 'Claimed' => 'success', 'Pending' => 'secondary',
            'Approved' => 'success', 'Rejected' => 'danger', 'Lost' => 'danger', 'Found' => 'primary'];
    return '<span class="badge bg-' . ($map[$status] ?? 'secondary') . '">' . e($status) . '</span>';
}


/** One item "tag" card (design from the group's index.html). Used by index.php and ajax_search.php */
function render_item_card(array $it): string {
    $isFound = $it['report_type'] === 'Found';
    $claimed = $it['claim_status'] === 'Claimed';
    $btn = $claimed ? 'View details' : ($isFound ? 'Claim this item' : 'I found this');
    $desc = mb_strimwidth($it['description'], 0, 90, '...');
    return '<article class="tag ' . ($isFound ? 'tag-found' : 'tag-lost') . '">'
        . '<div class="tag-top">' . e($it['report_type']) . '<span class="tag-status">' . e($it['claim_status']) . '</span></div>'
        . '<img class="tag-photo" src="' . e(photo_url($it['photo_path'])) . '" alt="" loading="lazy">'
        . '<div class="tag-body"><h3>' . e($it['item_name']) . '</h3><p>' . e($desc) . '</p>'
        . '<dl><dt>Where</dt><dd>' . e($it['location_name']) . '</dd>'
        . '<dt>Date</dt><dd>' . e(date('M j, Y', strtotime($it['date_happened']))) . '</dd>'
        . '<dt>Category</dt><dd>' . e($it['category_name']) . '</dd></dl>'
        . '<a class="btn btn-primary btn-small" href="' . e(url('item_view.php?id=' . (int)$it['item_id'])) . '">' . $btn . '</a>'
        . '</div></article>';
}
