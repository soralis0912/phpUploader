<?php
$escapeMeta = $escapeMeta ?? static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$siteTitle = (string)($siteTitle ?? $title ?? 'PHP Uploader');
$ogTitle = (string)($ogTitle ?? $siteTitle);
$ogDescription = (string)($ogDescription ?? '');
$ogType = (string)($ogType ?? 'website');
$ogUrl = (string)($ogUrl ?? '');
$ogImageUrl = (string)($ogImageUrl ?? '');
?>
    <meta name="description" content="<?php echo $escapeMeta($ogDescription); ?>">
    <link rel="canonical" href="<?php echo $escapeMeta($ogUrl); ?>">
    <meta property="og:site_name" content="<?php echo $escapeMeta($siteTitle); ?>">
    <meta property="og:title" content="<?php echo $escapeMeta($ogTitle); ?>">
    <meta property="og:description" content="<?php echo $escapeMeta($ogDescription); ?>">
    <meta property="og:type" content="<?php echo $escapeMeta($ogType); ?>">
    <meta property="og:url" content="<?php echo $escapeMeta($ogUrl); ?>">
    <meta property="og:image" content="<?php echo $escapeMeta($ogImageUrl); ?>">
    <meta property="og:image:width" content="1526">
    <meta property="og:image:height" content="894">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $escapeMeta($ogTitle); ?>">
    <meta name="twitter:description" content="<?php echo $escapeMeta($ogDescription); ?>">
    <meta name="twitter:image" content="<?php echo $escapeMeta($ogImageUrl); ?>">
