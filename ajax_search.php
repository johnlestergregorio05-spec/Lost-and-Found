<?php
// AJAX endpoint: returns item cards (HTML) for the Browse page
require_once __DIR__ . '/includes/functions.php';

[$where, $params] = build_item_filter($_GET, true);
$perPage = 9;
$page = max(1, (int)($_GET['page'] ?? 1));

$cnt = $pdo->prepare("SELECT COUNT(*) FROM items i $where");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT i.*, c.category_name, l.location_name
        FROM items i
        JOIN categories c ON c.category_id = i.category_id
        JOIN locations  l ON l.location_id = i.location_id
        $where
        ORDER BY i.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/html; charset=utf-8');
echo '<p class="results-count">' . $total . ' item' . ($total === 1 ? '' : 's') . ' found</p>';
if (!$rows) {
    echo '<p class="empty-box">No items match your search. Try clearing a filter.</p>';
} else {
    echo '<div class="grid">';
    foreach ($rows as $it) echo render_item_card($it);
    echo '</div>';
}
if ($pages > 1) {
    echo '<nav class="mt-4"><ul class="pagination justify-content-center mb-0">';
    for ($p = 1; $p <= $pages; $p++) {
        echo '<li class="page-item' . ($p === $page ? ' active' : '') . '"><a class="page-link" href="#" data-page="' . $p . '">' . $p . '</a></li>';
    }
    echo '</ul></nav>';
}
