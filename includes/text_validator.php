<?php
/**
 * Text Moderation using Sightengine API
 * Filters profanity, personal information, and inappropriate content in posts
 */

// Load API credentials from .env
define('SIGHTENGINE_API_USER', getenv('SIGHTENGINE_API_USER') ?: '');
define('SIGHTENGINE_API_SECRET', getenv('SIGHTENGINE_API_SECRET') ?: '');

/**
 * Validate text content using Sightengine API
 * 
 * @param string $text The text to moderate
 * @param string $field_name Name of field for error messages
 * @return mixed True if valid, error string if blocked
 */
function validate_text_content($text, $field_name = 'text') {
    // Check if text is empty
    if (empty(trim($text))) {
        return "{$field_name} cannot be empty.";
    }
    
    // Check if Sightengine credentials are configured
    if (empty(SIGHTENGINE_API_USER) || empty(SIGHTENGINE_API_SECRET)) {
        error_log("Sightengine API credentials not configured for text moderation. Skipping moderation.");
        return true; // Allow if not configured
    }
    
    try {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, 'https://api.sightengine.com/1.0/text/check.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'text' => $text,
            'lang' => 'en',
            'mode' => 'standard',
            'categories' => 'profanity,personal,link',
            'api_user' => SIGHTENGINE_API_USER,
            'api_secret' => SIGHTENGINE_API_SECRET
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Sightengine text API error: " . curl_error($ch));
            curl_close($ch);
            return true; // Allow on API error
        }
        
        curl_close($ch);
        
        if ($http_code !== 200) {
            error_log("Sightengine text API returned HTTP $http_code");
            return true; // Allow on API error
        }
        
        $result = json_decode($response, true);
        
        if (!$result || $result['status'] !== 'success') {
            error_log("Sightengine text API error: " . print_r($result, true));
            return true; // Allow on API error
        }
        
        // Check for profanity
        if (isset($result['profanity']['matches']) && !empty($result['profanity']['matches'])) {
            error_log("Text flagged for profanity in {$field_name}");
            return "Your {$field_name} contains inappropriate language. Please remove profanity and try again.";
        }
        
        // Check for personal information (emails, phone numbers, etc)
        if (isset($result['personal']['matches']) && !empty($result['personal']['matches'])) {
            foreach ($result['personal']['matches'] as $match) {
                $type = $match['type'] ?? '';
                if (in_array($type, ['email', 'phone', 'address'])) {
                    error_log("Text flagged for personal info ({$type}) in {$field_name}");
                    return "Your {$field_name} contains personal information ({$type}). For your safety, please remove it and use the messaging system to share contact details.";
                }
            }
        }
        
        // Check for excessive links (spam indicator)
        if (isset($result['link']['matches']) && count($result['link']['matches']) > 2) {
            error_log("Text flagged for excessive links in {$field_name}");
            return "Your {$field_name} contains too many links. Please limit external links.";
        }
        
        // All checks passed
        error_log("Sightengine text moderation passed for {$field_name}");
        return true;
        
    } catch (Exception $e) {
        error_log("Sightengine text moderation exception: " . $e->getMessage());
        return true; // Allow on exception
    }
}
?>