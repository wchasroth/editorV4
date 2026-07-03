<?php
declare(strict_types=1);

namespace CharlesRothDotNet\EditorV4;

/**
 * PhotoGrabber: 'grab' an image from a URL, and copy it into a local folder.
 *     Optionally attempt to identify a headshot in the image, and crop it.
 *
 * First version: Will Jaynes
 * Modified by:   Charles Roth   (make an instantiable object, return a 'Photo' object.)
 */

class PhotoGrabber
{
   private string $targetFolder;
   private string $python;
   private string $facecropScript;

    /**
     * @param string $targetFolder    Destination folder path (must already exist)
     * @param string $python          full pathname of Python executable to run
     * @param string $facecropScript  full pathname of Python 'face cropping' script to run
     */
   function __construct(string $targetFolder, string $python, string $facecropScript) {
      $this->targetFolder = $targetFolder;
      $this->python = $python;
      $this->facecropScript = $facecropScript;
   }

   // Download photo, store locally.  Optionally generate cropped photo as well.
   public function downloadPhoto(string $url, string $nameBase, string $cropBase, bool $extractHeadshot = false): Photo {
        // 1. Validate destination folder existence
        if (!is_dir($this->targetFolder))  return new Photo("", "", "Error: Destination folder does not exist: $this->targetFolder");

        // 2. Sanitize and clean URL; // Replace spaces with %20
        $cleanUrl = str_replace(' ', '%20', $url);

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

        $data  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        // Fallback: If cURL failed due to SSL handshake/cert issues (e.g. error code 35, 51, 60),
        // retry the request without SSL verification.
        if ($data === false  &&  in_array($errno, [35, 51, 60])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $data  = curl_exec($ch);
            $error = curl_error($ch);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($data     === false)  return new Photo("", "", "Error fetching URL '$cleanUrl': $error");

        if ($httpCode !== 200)    return new Photo("", "", "Error: URL '$cleanUrl' returned HTTP Status Code $httpCode");

        if (empty($data))         return new Photo("", "", "Error: URL '$cleanUrl' returned empty data");

        // 4. Determine image type and extension
        $ext = $this->determineExtension($data, $cleanUrl);
        if ($ext === null)        return new Photo("", "", "Error: Could not determine valid image extension for '$cleanUrl'");

        // 5. Construct destination filepath and save
        $filename = "$nameBase.$ext";
        $filepath = rtrim($this->targetFolder, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($filepath, $data) === false)  return new Photo("", "", "Error: Failed to write downloaded data to '$filepath'");

        // 6. Optionally extract a headshot
        $cropname = "";
        $error = "";
        if ($extractHeadshot) {
            $cropname = "$cropBase.$ext";
            $croppath = rtrim($this->targetFolder, '/\\') . DIRECTORY_SEPARATOR . $cropname;

            $cmd = sprintf( '%s %s %s %s 2>&1', $this->python, escapeshellarg($this->facecropScript), $filepath, $croppath);
            exec($cmd, $output, $returnCode);
                
            if ($returnCode !== 0) {
                $outputStr = implode("\n", $output);
                $cropname  = "";
                $error = "Warning: Headshot extraction failed (exit code $returnCode). Output: $outputStr";
            }
        }

        return new Photo($filename, $cropname, $error);
    }

    /**
     * Determines the file extension from the raw data (using MIME type detection)
     * or falls back to the file extension in the URL path.
     *
     * @param string $data     Raw binary data of the file
     * @param string $cleanUrl Sanitized URL
     * @return string|null     The determined extension (e.g. "jpg", "png"), or null if undetermined
     */
    private function determineExtension(string $data, string $cleanUrl): ?string {
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
            if ($urlExt === 'jpeg') return 'jpg';
            if (!empty($urlExt))    return $urlExt;
        }

        return null;
    }
}
