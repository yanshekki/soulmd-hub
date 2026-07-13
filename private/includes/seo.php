<?php
/**
 * SoulMD Hub - SEO Helper
 * Professional, secure, and SEO-optimized meta tags
 * 🚀 V5 SEO Optimized: Advanced Meta, Open Graph, Twitter Cards, and Schema.org JSON-LD
 */

function setSEO($title = '', $description = '', $image = '') {
    global $seoExtraGraph, $seoCanonical, $seoKeywords, $seoNoIndex;

    $baseTitle = 'SoulMD Hub - The Ultimate Multi-Modal AI Agent Platform';
    $baseDesc = 'Discover, interact, and build powerful AI personas. Featuring Elite Reasoning Engine, Vision AI, and smart sliding memory.';
    $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://soulmd-hub.ysk.hk';
    $defaultImage = $baseUrl . '/images/icon-512x512.png'; 

    // Title already including brand should not double-append
    $fullTitle = $title !== '' && $title !== null
        ? (preg_match('/\|\s*SoulMD Hub\s*$/iu', (string)$title) ? (string)$title : ((string)$title . ' | SoulMD Hub'))
        : $baseTitle;
    $fullDesc = $description ?: $baseDesc;
    $ogImage = $image ?: $defaultImage;
    
    // Prefer explicit canonical (clean path without query noise)
    if (!empty($seoCanonical) && is_string($seoCanonical)) {
        $currentUrl = $seoCanonical;
    } else {
        $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($reqPath) || $reqPath === '') {
            $reqPath = '/';
        }
        $currentUrl = $baseUrl . $reqPath;
    }
    
    // 判斷是否為首頁 (用於 Schema.org 動態輸出)
    $pathOnly = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $isHomepage = ($pathOnly === '/' || $pathOnly === '/zh/' || $pathOnly === '/en/' || $pathOnly === '/zh' || $pathOnly === '/en');

    // HTML Meta
    echo '<title>' . htmlspecialchars($fullTitle) . '</title>' . "\n";
    echo '<meta name="description" content="' . htmlspecialchars($fullDesc) . '">' . "\n";
    if (!empty($seoKeywords) && is_string($seoKeywords)) {
        echo '<meta name="keywords" content="' . htmlspecialchars($seoKeywords) . '">' . "\n";
    }
    
    // Advanced Robots Control for Rich Snippets
    $robots = !empty($seoNoIndex)
        ? 'noindex, follow'
        : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    echo '<meta name="robots" content="' . htmlspecialchars($robots) . '">' . "\n";

    // Open Graph (Facebook, LinkedIn, Discord)
    echo '<meta property="og:title" content="' . htmlspecialchars($fullTitle) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($fullDesc) . '">' . "\n";
    echo '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";
    echo '<meta property="og:url" content="' . htmlspecialchars($currentUrl) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:site_name" content="SoulMD Hub">' . "\n";
    echo '<meta property="og:locale" content="' . htmlspecialchars(
        (defined('CURRENT_LANG') && CURRENT_LANG === 'zh') ? 'zh_HK' : 'en_US'
    ) . '">' . "\n";

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:site" content="@soulmd_hub">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($fullTitle) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($fullDesc) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";

    // Canonical URL
    echo '<link rel="canonical" href="' . htmlspecialchars($currentUrl) . '">' . "\n";

    // ==========================================
    // Schema.org JSON-LD (Rich Results for Google)
    // ==========================================
    $schema = [
        "@context" => "https://schema.org",
        "@graph" => [
            [
                "@type" => "Organization",
                "@id" => $baseUrl . "/#organization",
                "name" => "SoulMD Hub",
                "url" => $baseUrl,
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $baseUrl . "/images/icon-512x512.png",
                    "width" => 512,
                    "height" => 512
                ],
                "sameAs" => [
                    "https://github.com/yanshekki/soulmd-hub"
                ]
            ],
            [
                "@type" => "WebSite",
                "@id" => $baseUrl . "/#website",
                "url" => $baseUrl,
                "name" => "SoulMD Hub",
                "description" => "Multi-Modal AI Agent Platform",
                "publisher" => ["@id" => $baseUrl . "/#organization"],
                "potentialAction" => [
                    "@type" => "SearchAction",
                    "target" => [
                        "@type" => "EntryPoint",
                        "urlTemplate" => $baseUrl . "/browse?q={search_term_string}"
                    ],
                    "query-input" => "required name=search_term_string"
                ]
            ],
            [
                "@type" => "SoftwareApplication",
                "@id" => $baseUrl . "/#software",
                "name" => "SoulMD Hub Engine",
                "applicationCategory" => "WebApplication",
                "operatingSystem" => "Web Browser",
                "description" => "The open-source platform to share, discover, and interact with AI agent personas via .md files.",
                "offers" => [
                    "@type" => "Offer",
                    "price" => "0.00",
                    "priceCurrency" => "USD"
                ]
            ]
        ]
    ];

    // 若為首頁，額外注入 WebPage Schema 提升首頁權重
    if ($isHomepage) {
        $schema["@graph"][] = [
            "@type" => "WebPage",
            "@id" => $currentUrl . "#webpage",
            "url" => $currentUrl,
            "name" => $fullTitle,
            "description" => $fullDesc,
            "isPartOf" => ["@id" => $baseUrl . "/#website"],
            "about" => ["@id" => $baseUrl . "/#software"]
        ];
    }

    // Page-level extra nodes (apps, docs, etc.)
    if (!empty($seoExtraGraph) && is_array($seoExtraGraph)) {
        foreach ($seoExtraGraph as $node) {
            if (is_array($node) && !empty($node['@type'])) {
                $schema['@graph'][] = $node;
            }
        }
    }

    echo '<script type="application/ld+json">' . "\n" . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n" . '</script>' . "\n";
}
?>