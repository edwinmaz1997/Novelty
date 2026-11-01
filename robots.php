<?php
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
$b = base_path();
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: {$b}/admin/\n";
echo "Disallow: {$b}/api/\n";
echo "Disallow: {$b}/instalar/\n";
echo "Disallow: {$b}/app/\n";
echo "Disallow: {$b}/*?*q=\n";
echo "\nSitemap: " . abs_url('sitemap.xml') . "\n";
