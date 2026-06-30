<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

class PhotoGrabber
{
    /**
     * Downloads a photo from a URL, sanitizes the URL, determines the image type,
     * and saves it to the specified folder with the name format "$code-$name.$ext".
     *
     * @param string $code   Unique identifier/code for the photo (e.g., "123")
     * @param string $name   Name associated with the photo (e.g., "Aaron_Iturralde")
     * @param string $folder Destination folder path (must already exist)
     * @param string $url    URL of the photo to download
     * @param bool   $extractHeadshot Whether to attempt extracting a headshot (face crop) from the image
     * @return string        "OK" on success, or a descriptive error message on failure
     */
    public static function downloadPhoto(string $code, string $name, string $folder, string $url, bool $extractHeadshot = false): string
    {
        // 1. Validate destination folder existence
        if (!is_dir($folder)) {
            return "Error: Destination folder does not exist: '$folder'";
        }

        // 2. Sanitize and clean URL
        // Replace spaces with %20
        $cleanUrl = str_replace(' ', '%20', $url);

//        // Strip everything after a (non-escaped) '?'
//        $queryPos = strpos($cleanUrl, '?');
//        if ($queryPos !== false) {
//            $cleanUrl = substr($cleanUrl, 0, $queryPos);
//        }

        // 3. Fetch data via cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $cleanUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // Use a common browser user agent to avoid being blocked by servers
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        // Enable SSL verification by default
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $data = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        // Fallback: If cURL failed due to SSL handshake/cert issues (e.g. error code 35, 51, 60),
        // retry the request without SSL verification.
        if ($data === false && in_array($errno, [35, 51, 60])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $data = curl_exec($ch);
            $error = curl_error($ch);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data === false) {
            return "Error fetching URL '$cleanUrl': $error";
        }

        if ($httpCode !== 200) {
            return "Error: URL '$cleanUrl' returned HTTP Status Code $httpCode";
        }

        if (empty($data)) {
            return "Error: URL '$cleanUrl' returned empty data";
        }

        // 4. Determine image type and extension
        $ext = self::determineExtension($data, $cleanUrl);
        if ($ext === null) {
            return "Error: Could not determine valid image extension for '$cleanUrl'";
        }

        // 5. Construct destination filepath and save
        $filename = "{$code}-{$name}.{$ext}";
        $filepath = rtrim($folder, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($filepath, $data) === false) {
            return "Error: Failed to write downloaded data to '$filepath'";
        }

        // 6. Optionally extract a headshot
        if ($extractHeadshot) {
            // Check if Python is available (try 'python' first, then 'python3')
            $pythonCmd = 'python';
            exec('python --version 2>&1', $out, $code);
            if ($code !== 0) {
                exec('python3 --version 2>&1', $out3, $code3);
                if ($code3 === 0) {
                    $pythonCmd = 'python3';
                } else {
                    return "Error: Headshot extraction requested, but Python is not available on this system.";
                }
            }

            $pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'crop_face.py';
            $tempFile = $filepath . '.tmp';
            
            // Rename downloaded file to tempFile for processing
            if (rename($filepath, $tempFile)) {
                $cmd = sprintf(
                    '%s %s %s %s 2>&1',
                    $pythonCmd,
                    escapeshellarg($pythonScript),
                    escapeshellarg($tempFile),
                    escapeshellarg($filepath)
                );
                
                exec($cmd, $output, $returnCode);
                
                // Clean up temporary file
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
                
                if ($returnCode !== 0) {
                    $outputStr = implode("\n", $output);
                    return "Error: Headshot extraction failed (exit code $returnCode). Output: $outputStr";
                }
            } else {
                return "Error: Failed to rename file to temporary path for headshot extraction";
            }
        }

        return "OK $filename";
    }

    /**
     * Determines the file extension from the raw data (using MIME type detection)
     * or falls back to the file extension in the URL path.
     *
     * @param string $data     Raw binary data of the file
     * @param string $cleanUrl Sanitized URL
     * @return string|null     The determined extension (e.g. "jpg", "png"), or null if undetermined
     */
    private static function determineExtension(string $data, string $cleanUrl): ?string
    {
        $mime = null;

        // Try to get MIME type using PHP's finfo extension
        if (class_exists('finfo')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectedMime = finfo_buffer($finfo, $data);
                finfo_close($finfo);
                if ($detectedMime) {
                    $mime = strtolower($detectedMime);
                }
            }
        }

        // Mapping from MIME types to standard file extensions
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/x-ms-bmp' => 'bmp',
            'image/vnd.microsoft.icon' => 'ico',
            'image/x-icon' => 'ico'
        ];

        if ($mime !== null && isset($mimeToExt[$mime])) {
            return $mimeToExt[$mime];
        }

        // Fallback: extract extension from the clean URL path
        $path = parse_url($cleanUrl, PHP_URL_PATH);
        if ($path) {
            $urlExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            // Normalize common extension names
            if ($urlExt === 'jpeg') {
                return 'jpg';
            }
            if (!empty($urlExt)) {
                return $urlExt;
            }
        }

        return null;
    }
}
