<?php
if (empty($source) || !is_readable(MODX_BASE_PATH . $source)) {
    return '';
}

$source = MODX_BASE_PATH . $source;

$type = exif_imagetype($source);

if (!in_array($type, [1,2,3,6])) {
    return '';
}

try {
    switch ($type) {
        case 1: $image = imagecreatefromgif($source);  break;
        case 2: $image = imagecreatefromjpeg($source); break;
        case 3: $image = imagecreatefrompng($source);  break;
        case 6: $image = imagecreatefrombmp($source);  break;
    }

    $scaled = imagescale($image, 1, 1, IMG_BICUBIC_FIXED);
    $index  = imagecolorat($scaled, 0, 0);
    $rgb    = imagecolorsforindex($scaled, $index);
    imagedestroy($image);
    imagedestroy($scaled);
} catch (\Exception $e) {
    return '';
}

$color = sprintf('#%02X%02X%02X', $rgb['red'], $rgb['green'], $rgb['blue']);

if (!empty($contrast)) {
    $dark = isset($dark) ? $dark : '#000';
    $light = isset($light) ? $light : '#fff';
    $color = (hexdec($color) > 0xffffff/2) ? $dark : $light;
}

if (isset($output) && $output == 'image') {
    $width  = isset($width) ? $width : 1;
    $height = isset($height) ? $height : 1;

    if (!empty($contrast)) {
        if (strlen($color) == 4) {
            preg_match('/^#(.)(.)(.)$/', $color, $m);
            $rgb = [
                'red'   => hexdec($m[1] . $m[1]),
                'green' => hexdec($m[2] . $m[2]),
                'blue'  => hexdec($m[3] . $m[3]),
            ];
        } else {
            preg_match('/^#(..)(..)(..)$/', $color, $m);
            $rgb = [
                'red'   => hexdec($m[1]),
                'green' => hexdec($m[2]),
                'blue'  => hexdec($m[3]),
            ];
        }
    }

    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, $rgb['red'], $rgb['green'], $rgb['blue']));

    ob_start();
    imagepng($image);
    $bin = ob_get_clean();
    imagedestroy($image);

    return 'data:image/png;base64,' . base64_encode($bin);
}

return $color;
