<?php
if (!isset($_GET['file'])) {
    exit();
}
$file = $_GET['file'];
if (preg_match('@^\w+\.(jpg|png|gif)$@', $file) == 0) {
    exit();
} 
$file = "imgs/".$file;
if (! file_exists($file)) {
    exit();
}
$width = 1024;
$height = 1024;
if (isset($_GET['thumb'])) {
    $width = 240;
    $height = 240;
}
$imagick = new Imagick($file);
$imagick->resizeImage($width, $height, imagick::FILTER_LANCZOS, 1, true);


header("Content-Type: image/jpg");
echo $imagick->getImageBlob();

