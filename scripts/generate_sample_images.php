<?php
declare(strict_types=1);
$dir = __DIR__ . '/../uploads/sample';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$items = [
    ['shoyu', 'Classic Shoyu Ramen'],
    ['miso', 'Spicy Miso Ramen'],
    ['tonkotsu', 'Tonkotsu Ramen'],
    ['salmon', 'Salmon Aburi Sushi'],
    ['unagi', 'Unagi Nigiri'],
    ['tsukemen', 'Tsukemen Yuzu'],
    ['bento', 'Chicken Katsu Bento'],
    ['gyudon', 'Gyudon Bento'],
    ['ocha', 'Ocha Panas'],
    ['matcha', 'Matcha Latte'],
];
$colors = [
    [239, 125, 87], [214, 82, 92], [217, 151, 82], [115, 169, 173],
    [156, 115, 70], [243, 196, 77], [186, 120, 120], [132, 164, 204],
    [167, 196, 156], [158, 203, 149]
];
foreach ($items as $idx => [$slug, $label]) {
    $img = imagecreatetruecolor(640, 400);
    [$r, $g, $b] = $colors[$idx % count($colors)];
    $bg = imagecolorallocate($img, $r, $g, $b);
    imagefill($img, 0, 0, $bg);
    $white = imagecolorallocate($img, 255, 255, 255);
    $lines = explode("\n", wordwrap($label, 18));
    $y = 160 - (count($lines) * 15);
    foreach ($lines as $line) {
        $textWidth = imagefontwidth(5) * strlen($line);
        $x = (640 - $textWidth) / 2;
        imagestring($img, 5, (int) $x, (int) $y, $line, $white);
        $y += 30;
    }
    imagestring($img, 4, 20, 360, 'Ramen 1 Photo', $white);
    imagejpeg($img, "$dir/$slug.jpg", 90);
    imagedestroy($img);
}
