<?php
require_once "../includes/auth_admin.php";
require_once "../includes/db_connect.php";

// Mark as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    mysqli_query($conn, "UPDATE contact_messages SET status='read' WHERE id=$id");
    header("Location: view_messages.php");
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    mysqli_query($conn, "UPDATE contact_messages SET status='read' WHERE status='unread'");
    header("Location: view_messages.php");
    exit;
}

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM contact_messages WHERE id=$id");
    header("Location: view_messages.php");
    exit;
}

$filter   = $_GET['filter'] ?? 'all';
$where    = $filter === 'unread' ? "WHERE status='unread'" : ($filter === 'read' ? "WHERE status='read'" : '');
$messages = mysqli_query($conn, "SELECT * FROM contact_messages $where ORDER BY created_at DESC");
$unread   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS t FROM contact_messages WHERE status='unread'"))['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Messages - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-wrapper">
<?php $active_page = 'messages'; include "../includes/admin_sidebar.php"; ?>

<div class="main-content">
    <div class="topbar">
        <h1>📬 Contact Messages</h1>
        <div class="topbar-right">
            <?php if ($unread > 0): ?>
                <span class="badge badge-danger"><?= $unread ?> Unread</span>
            <?php endif; ?>
            <span>📅 <?= date('D, d M Y') ?></span>
        </div>
    </div>

    <!-- Filter tabs + mark all read -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <div class="filter-tabs" style="margin-bottom:0;">
            <a href="view_messages.php?filter=all"    class="filter-tab <?= $filter==='all'    ? 'active' : '' ?>">All</a>
            <a href="view_messages.php?filter=unread" class="filter-tab <?= $filter==='unread' ? 'active' : '' ?>">
                Unread <?php if ($unread > 0): ?><span class="badge badge-danger" style="margin-left:4px;padding:2px 7px;"><?= $unread ?></span><?php endif; ?>
            </a>
            <a href="view_messages.php?filter=read"   class="filter-tab <?= $filter==='read'   ? 'active' : '' ?>">Read</a>
        </div>
        <?php if ($unread > 0): ?>
            <a href="view_messages.php?mark_all_read=1" class="btn btn-outline btn-sm"
               onclick="return confirm('Mark all messages as read?')">✅ Mark All Read</a>
        <?php endif; ?>
    </div>

    <!-- Messages -->
    <?php if (mysqli_num_rows($messages) === 0): ?>
        <div class="card" style="text-align:center; padding:48px 24px; color:var(--text-muted);">
            <div style="font-size:48px; margin-bottom:12px;">📭</div>
            <h3 style="font-size:16px; font-weight:600;">No messages found</h3>
            <p style="font-size:14px; margin-top:6px;">
                <?= $filter !== 'all' ? 'Try switching to "All" tab.' : 'When users submit the contact form, messages will appear here.' ?>
            </p>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:14px;">
        <?php while ($m = mysqli_fetch_assoc($messages)):
            $is_unread = $m['status'] === 'unread';
        ?>
            <div class="msg-card <?= $is_unread ? 'msg-unread' : '' ?>">
                <div class="msg-card-header">
                    <div class="msg-meta">
                        <?php if ($is_unread): ?>
                            <span class="msg-dot"></span>
                        <?php endif; ?>
                        <span class="msg-name"><?= htmlspecialchars($m['name']) ?></span>
                        <span class="msg-email">
                            <a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a>
                        </span>
                        <?php if ($m['phone']): ?>
                            <span class="msg-phone">📞 <?= htmlspecialchars($m['phone']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="msg-actions">
                        <span class="msg-time">🕐 <?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></span>
                        <?php if ($is_unread): ?>
                            <a href="view_messages.php?mark_read=<?= $m['id'] ?>" class="btn btn-success btn-sm">✅ Mark Read</a>
                        <?php else: ?>
                            <span class="badge badge-success">Read</span>
                        <?php endif; ?>
                        <a href="view_messages.php?delete=<?= $m['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this message?')">🗑️</a>
                    </div>
                </div>
                <div class="msg-subject">
                    <span class="badge badge-info">📌 <?= htmlspecialchars($m['subject']) ?></span>
                </div>
                <div class="msg-body"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
            </div>
        <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</div>

<style>
.msg-card {
    background: var(--card-bg);
    border: 1.5px solid var(--border);
    border-radius: 14px;
    padding: 20px 24px;
    box-shadow: var(--shadow);
    transition: box-shadow .2s;
}
.msg-card:hover { box-shadow: 0 8px 28px rgba(26,35,126,.12); }
.msg-unread {
    border-left: 4px solid var(--primary);
    background: #f8f9ff;
}
body.dark .msg-unread { background: #1a1e2e; border-left-color: #90caf9; }
body.dark .msg-card { background: #1e1e1e; border-color: #2a2a2a; }

.msg-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}
.msg-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.msg-dot {
    width: 9px; height: 9px;
    border-radius: 50%;
    background: var(--primary);
    flex-shrink: 0;
}
body.dark .msg-dot { background: #90caf9; }
.msg-name  { font-size: 15px; font-weight: 700; color: var(--text); }
.msg-email { font-size: 13px; color: var(--primary-light); }
.msg-email a { color: inherit; text-decoration: none; }
.msg-email a:hover { text-decoration: underline; }
.msg-phone { font-size: 13px; color: var(--text-muted); }
body.dark .msg-name { color: #e0e0e0; }

.msg-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.msg-time { font-size: 12px; color: var(--text-muted); white-space: nowrap; }

.msg-subject { margin-bottom: 10px; }

.msg-body {
    font-size: 14px;
    color: var(--text-muted);
    line-height: 1.75;
    background: var(--bg);
    border-radius: 8px;
    padding: 12px 16px;
    border: 1px solid var(--border);
}
body.dark .msg-body { background: #252525; border-color: #333; color: #aaa; }
</style>
</body>
</html>
