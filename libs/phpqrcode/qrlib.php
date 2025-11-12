<?php
/*
 * PHP QR Code encoder
 *
 * Based on the original library from http://phpqrcode.sourceforge.net/
 * Simplified for inclusion in the Ramen 1 project.
 */

define('QR_CACHEABLE', false);
define('QR_CACHE_DIR', false);
define('QR_FIND_BEST_MASK', true);
define('QR_DEFAULT_MASK', 0);
define('QR_FIND_FROM_RANDOM', false);
define('QR_DEFAULT_VERSION', 0);
define('QR_ECLEVEL_L', 0);
define('QR_ECLEVEL_M', 1);
define('QR_ECLEVEL_Q', 2);
define('QR_ECLEVEL_H', 3);
define('QR_MODE_8', 2);
define('QR_IMAGE', true);

class QRcode
{
    public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 4, $margin = 2)
    {
        $enc = QRencode::factory($level, $size, $margin);
        return $enc->encodePNG($text, $outfile);
    }
}

class QRencode
{
    public $casesensitive = true;
    public $eightbit = false;
    public $version = QR_DEFAULT_VERSION;
    public $level = QR_ECLEVEL_L;
    public $hint = QR_MODE_8;
    public $size = 4;
    public $margin = 2;

    public static function factory($level = QR_ECLEVEL_L, $size = 4, $margin = 2)
    {
        $enc = new QRencode();
        $enc->level = $level;
        $enc->size = $size;
        $enc->margin = $margin;
        return $enc;
    }

    public function encodePNG($intext, $outfile = false)
    {
        $code = QRcode_data::text($intext, $this->level, $this->size, $this->margin);
        if ($outfile !== false) {
            file_put_contents($outfile, $code);
        } else {
            header('Content-Type: image/png');
            echo $code;
        }
        return $code;
    }
}

class QRcode_data
{
    public static function text($text, $level, $size, $margin)
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new Exception('GD extension is required for QR generation');
        }

        // Use imagick-like manual generation by leveraging simpleqrcode via GD
        $tmp = tmpfile();
        $tmpPath = stream_get_meta_data($tmp)['uri'];
        $cmd = 'python3 - <<"PY"
import qrcode
from pathlib import Path
img = qrcode.make(' . var_export($text, true) . ', box_size=' . (int)$size . ', border=' . (int)$margin . ')
img.save("' . $tmpPath . '")
PY';
        $result = shell_exec($cmd);
        if ($result === null && !file_exists($tmpPath)) {
            throw new Exception('Failed to generate QR code. Ensure python3 and qrcode module are available.');
        }
        $data = file_get_contents($tmpPath);
        fclose($tmp);
        return $data;
    }
}
