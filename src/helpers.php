<?php

if (!function_exists('is_valid_ssml')) {
    /**
     * Validates the basic XML structure for SSML using DOMDocument.
     * This function is a safeguard against obvious XML errors.
     */
    function is_valid_ssml(string $content): bool
    {
        // Ensure the content is clean before parsing
        $content = trim($content);

        // Check if the content is empty after trimming
        if (empty($content)) {
            return false;
        }

        // 1. Check for nested <speak> tags
        $speakTagCount = preg_match_all('/<[sS][pP][eE][aA][kK]\b[^>]*>/', $content, $matches);
        $closeSpeakTagCount = substr_count(strtolower($content), '</speak>');
        
        // If there's more than one opening <speak> tag or mismatched tags, it's invalid
        if ($speakTagCount !== 1 || $closeSpeakTagCount !== 1) {
            return false;
        }

        // 2. Create a temporary copy of the content with normalized speak tags
        $normalizedContent = preg_replace_callback(
            '/<([\/]?)([sS][pP][eE][aA][kK])([^>]*)>/',
            function($matches) {
                return '<' . $matches[1] . 'speak' . $matches[3] . '>';
            },
            $content
        );

        // 3. Attempt to load the normalized content as XML via DOMDocument
        $doc = new DOMDocument();
        
        // Save current error handling state
        $originalErrorState = libxml_use_internal_errors(true);

        try {
            // Try to load the XML with LIBXML_NOERROR | LIBXML_NOWARNING to suppress warnings
            $loaded = @$doc->loadXML($normalizedContent, LIBXML_NOERROR | LIBXML_NOWARNING);

            // If loading failed (malformed XML)
            if (!$loaded) {
                return false;
            }

            // Ensure we have a document element
            if (!$doc->documentElement) {
                return false;
            }

            // Check if the root element is 'speak'
            return $doc->documentElement->nodeName === 'speak';
        } finally {
            // Always restore original error handling state and clear errors
            libxml_clear_errors();
            libxml_use_internal_errors($originalErrorState);
        }
    }
}