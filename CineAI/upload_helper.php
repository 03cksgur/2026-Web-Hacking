<?php
function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function handlePosterUpload($file) {
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // A08: Validate File Size (Max 5MB) to prevent storage exhaustion
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($mime, $allowedMime) || !in_array($ext, $allowedExt)) {
        return null; // Block malicious/unsupported files
    }

    $uploadDir = __DIR__ . '/uploads/';
    
    // Create directory and web.config if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $webConfigPath = $uploadDir . 'web.config';
    if (!file_exists($webConfigPath)) {
        $webConfigContent = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <handlers>
            <clear />
            <add name="StaticFile" path="*" verb="*" modules="StaticFileModule" resourceType="Either" />
        </handlers>
        <security>
            <requestFiltering>
                <fileExtensions allowUnlisted="false">
                    <add fileExtension=".jpg" allowed="true" />
                    <add fileExtension=".png" allowed="true" />
                    <add fileExtension=".webp" allowed="true" />
                    <add fileExtension=".gif" allowed="true" />
                </fileExtensions>
            </requestFiltering>
        </security>
    </system.webServer>
</configuration>';
        file_put_contents($webConfigPath, $webConfigContent);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uuid = generateUUID();
    $newFileName = $uuid . ($ext ? '.' . $ext : '');
    
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
        return $newFileName;
    }
    return null;
}
?>
