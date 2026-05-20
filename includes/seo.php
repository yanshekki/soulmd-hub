<?php
/**
 * SoulMD Hub - SEO Helper
 * Include this in every page for consistent SEO
 */

function setSEO($title = '', $description = '', $image = '') {
    $baseTitle = 'SoulMD Hub';
    $baseDesc = 'The simplest platform to share, discover, and fork AI agent souls as .md files.';
    $baseUrl = 'https://soulmd-hub.ysk.hk';
    $defaultImage = $baseUrl . '/og-image.png'; // TODO: add og image later

    $fullTitle = $title ? $title . ' | ' . $baseTitle : $baseTitle;
    $fullDesc = $description ?: $baseDesc;
    $ogImage = $image ?: $defaultImage;

    echo '<title>' . htmlspecialchars($fullTitle) . '</title>';
    echo '<meta name="description" content="' . htmlspecialchars($fullDesc) . '">';

    // Open Graph
    echo '<meta property="og:title" content="' . htmlspecialchars($fullTitle) . '">';
    echo '<meta property="og:description" content="' . htmlspecialchars($fullDesc) . '">';
    echo '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">';
    echo '<meta property="og:url" content="' . $baseUrl . $_SERVER['REQUEST_URI'] . '">';
    echo '<meta property="og:type" content="website">';

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="twitter:title" content="' . htmlspecialchars($fullTitle) . '">';
    echo '<meta name="twitter:description" content="' . htmlspecialchars($fullDesc) . '">';
    echo '<meta name="twitter:image" content="' . htmlspecialchars($ogImage) . '">';
}
