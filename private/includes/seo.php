<?php
/**
 * SoulMD Hub - SEO Helper
 * Professional, secure, and SEO-optimized meta tags
 */

function setSEO($title = '', $description = '', $image = '') {
    $baseTitle = 'SoulMD Hub';
    $baseDesc = 'The simplest platform to share, discover, and fork AI agent souls as .md files. Human & AI friendly.';
    $baseUrl = 'https://soulmd-hub.ysk.hk';
    $defaultImage = $baseUrl . '/og-image.png'; // Add your OG image later

    $fullTitle = $title ? $title . ' | ' . $baseTitle : $baseTitle;
    $fullDesc = $description ?: $baseDesc;
    $ogImage = $image ?: $defaultImage;

    // HTML meta
    echo '<title>' . htmlspecialchars($fullTitle) . '</title>' . "\n";
    echo '<meta name="description" content="' . htmlspecialchars($fullDesc) . '">' . "\n";

    // Open Graph
    echo '<meta property="og:title" content="' . htmlspecialchars($fullTitle) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($fullDesc) . '">' . "\n";
    echo '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";
    echo '<meta property="og:url" content="' . $baseUrl . $_SERVER['REQUEST_URI'] . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="' . $baseTitle . '">' . "\n";

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($fullTitle) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($fullDesc) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";

    // Additional SEO
    echo '<meta name="robots" content="index, follow">' . "\n";
    echo '<link rel="canonical" href="' . $baseUrl . $_SERVER['REQUEST_URI'] . '">' . "\n";
}