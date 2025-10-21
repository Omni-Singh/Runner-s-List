<?php
/**
 * Image Validation and Content Moderation with Sightengine
 */

// Load API credentials from .env
define('SIGHTENGINE_API_USER', getenv('SIGHTENGINE_API_USER') ?: '');
define('SIGHTENGINE_API_SECRET', getenv('SIGHTENGINE_API_SECRET') ?: '');
define('ENABLE_SIGHTENGINE_MODERATION', true);

function validate_and_moderate_image($file) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return "Error uploading file.";
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024;
    
    if ($file['size'] > $max_size) {
        return "Image must be less than 5MB.";
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return "Only JPG, PNG, and WEBP images are allowed.";
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($detected_type, $allowed_types)) {
        return "Invalid image file detected. Please upload a real image.";
    }
    
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return "File is not a valid image.";
    }
    
    $max_width = 4000;
    $max_height = 4000;
    
    if ($image_info[0] > $max_width || $image_info[1] > $max_height) {
        return "Image dimensions too large. Maximum {$max_width}x{$max_height} pixels.";
    }
    
    if (ENABLE_SIGHTENGINE_MODERATION) {
        $moderation_result = moderate_with_sightengine($file['tmp_name']);
    } else {
        $moderation_result = check_image_content_basic($file['tmp_name'], $image_info['mime']);
    }
    
    if ($moderation_result !== true) {
        return $moderation_result;
    }
    
    return true;
}

function moderate_with_sightengine($filepath) {
    if (empty(SIGHTENGINE_API_USER) || empty(SIGHTENGINE_API_SECRET)) {
        error_log("Sightengine API credentials not configured. Using basic moderation.");
        return check_image_content_basic($filepath, mime_content_type($filepath));
    }
    
    try {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.sightengine.com/1.0/check.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'media' => new CURLFile($filepath),
            'models' => 'nudity,wad,offensive,gore',
            'api_user' => SIGHTENGINE_API_USER,
            'api_secret' => SIGHTENGINE_API_SECRET
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Sightengine API error: " . curl_error($ch));
            curl_close($ch);
            return check_image_content_basic($filepath, mime_content_type($filepath));
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log("Sightengine API returned HTTP $http_code");
            return check_image_content_basic($filepath, mime_content_type($filepath));
        }
        
        $result = json_decode($response, true);
        
        if (!$result || $result['status'] !== 'success') {
            error_log("Sightengine API error: " . print_r($result, true));
            return check_image_content_basic($filepath, mime_content_type($filepath));
        }
        
        if (isset($result['nudity']['sexual_activity']) && $result['nudity']['sexual_activity'] > 0.5) {
            return "Image contains inappropriate sexual content and cannot be uploaded.";
        }
        
        if (isset($result['nudity']['sexual_display']) && $result['nudity']['sexual_display'] > 0.5) {
            return "Image contains explicit nudity and cannot be uploaded.";
        }
        
        if (isset($result['weapon']) && $result['weapon'] > 0.6) {
            return "Image contains weapons and cannot be uploaded.";
        }
        
        if (isset($result['drugs']) && $result['drugs'] > 0.6) {
            return "Image contains drug-related content and cannot be uploaded.";
        }
        
        if (isset($result['alcohol']) && $result['alcohol'] > 0.7) {
            return "Image contains alcohol and cannot be uploaded.";
        }
        
        if (isset($result['offensive']['prob']) && $result['offensive']['prob'] > 0.6) {
            return "Image contains offensive or inappropriate content and cannot be uploaded.";
        }
        
        if (isset($result['gore']['prob']) && $result['gore']['prob'] > 0.6) {
            return "Image contains violent or disturbing content and cannot be uploaded.";
        }
        
        error_log("Sightengine moderation passed for image: " . basename($filepath));
        return true;
        
    } catch (Exception $e) {
        error_log("Sightengine exception: " . $e->getMessage());
        return check_image_content_basic($filepath, mime_content_type($filepath));
    }
}

function check_image_content_basic($filepath, $mime_type) {
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = @imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($filepath);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($filepath);
            break;
        default:
            return true;
    }
    
    if (!$image) {
        return true;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    $skin_tone_count = 0;
    $total_samples = 0;
    $sample_rate = 10;
    
    for ($x = 0; $x < $width; $x += $sample_rate) {
        for ($y = 0; $y < $height; $y += $sample_rate) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            if (is_skin_tone($r, $g, $b)) {
                $skin_tone_count++;
            }
            $total_samples++;
        }
    }
    
    imagedestroy($image);
    
    $skin_percentage = ($skin_tone_count / $total_samples) * 100;
    
    if ($skin_percentage > 60) {
        error_log("Image flagged by basic moderation: {$skin_percentage}% skin tones in {$filepath}");
        return "This image was flagged for review. Please upload a clear photo of the lost/found item.";
    }
    
    return true;
}

function is_skin_tone($r, $g, $b) {
    if ($r > 95 && $g > 40 && $b > 20 &&
        $r > $g && $r > $b &&
        abs($r - $g) > 15 &&
        $r < 250 && $g < 250 && $b < 250) {
        return true;
    }
    return false;
}
?>