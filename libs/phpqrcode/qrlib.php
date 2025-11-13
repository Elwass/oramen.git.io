<?php
/**
 * Minimal QR code generator for Byte-mode, ECC level L, version 1-4.
 *
 * This implementation focuses on generating PNG images for typical URL strings
 * used in the Ramen 1 ordering system. It does not rely on external binaries
 * or Python modules, so QR generation works inside restricted hosting
 * environments.
 */

declare(strict_types=1);

const QR_ECLEVEL_L = 0;
const QR_ECLEVEL_M = 1;
const QR_ECLEVEL_Q = 2;
const QR_ECLEVEL_H = 3;

class QRcode
{
    public static function png(string $text, string $outfile = '', int $level = QR_ECLEVEL_L, int $size = 6, int $margin = 2): string
    {
        $generator = new SimpleQRCodeGenerator();
        $pngData = $generator->renderPng($text, $size, $margin);
        if ($outfile !== '') {
            file_put_contents($outfile, $pngData);
        } else {
            header('Content-Type: image/png');
            echo $pngData;
        }
        return $pngData;
    }
}

class SimpleQRCodeGenerator
{
    /**
     * Version definitions for Byte mode, error correction level L.
     * @var array<int,array<string,mixed>>
     */
    private array $versions = [
        1 => ['dimension' => 21, 'dataCodewords' => 19, 'ecCodewords' => 7, 'alignment' => []],
        2 => ['dimension' => 25, 'dataCodewords' => 34, 'ecCodewords' => 10, 'alignment' => [6, 18]],
        3 => ['dimension' => 29, 'dataCodewords' => 55, 'ecCodewords' => 15, 'alignment' => [6, 22]],
        4 => ['dimension' => 33, 'dataCodewords' => 80, 'ecCodewords' => 20, 'alignment' => [6, 26]],
    ];

    private array $expTable = [];
    private array $logTable = [];

    public function __construct()
    {
        $this->initGaloisTables();
    }

    public function renderPng(string $text, int $moduleSize, int $margin): string
    {
        [$version, $matrix] = $this->buildMatrix($text);
        $dimension = count($matrix);
        $moduleSize = max(1, $moduleSize);
        $margin = max(0, $margin);
        $imageSize = ($dimension + $margin * 2) * $moduleSize;
        $img = imagecreatetruecolor($imageSize, $imageSize);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);
        for ($row = 0; $row < $dimension; $row++) {
            for ($col = 0; $col < $dimension; $col++) {
                if ($matrix[$row][$col] === 1) {
                    $x1 = ($margin + $col) * $moduleSize;
                    $y1 = ($margin + $row) * $moduleSize;
                    imagefilledrectangle($img, $x1, $y1, $x1 + $moduleSize - 1, $y1 + $moduleSize - 1, $black);
                }
            }
        }
        ob_start();
        imagepng($img);
        imagedestroy($img);
        return (string) ob_get_clean();
    }

    /**
     * @return array{int,array<int,array<int,int>>}
     */
    private function buildMatrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text));
        $version = $this->chooseVersion(count($bytes));
        $info = $this->versions[$version];
        $dataCodewords = $info['dataCodewords'];
        $ecCodewords = $info['ecCodewords'];
        $dimension = $info['dimension'];

        $dataBits = $this->buildDataBits($bytes, $dataCodewords);
        $codewords = $this->applyErrorCorrection($dataBits, $dataCodewords, $ecCodewords);
        $bitStream = $this->codewordsToBits($codewords);

        [$matrix, $reserved] = $this->initializeMatrix($dimension, $info['alignment']);
        $this->placeDataBits($matrix, $reserved, $bitStream);
        $this->applyMask($matrix, $reserved);
        $this->placeFormatInfo($matrix, $reserved);

        return [$version, $matrix];
    }

    private function chooseVersion(int $byteLength): int
    {
        foreach ($this->versions as $version => $info) {
            if ($byteLength <= $info['dataCodewords'] - 2) { // leave headroom for metadata
                return $version;
            }
        }
        throw new RuntimeException('QR data too long for built-in generator');
    }

    /**
     * @param array<int,int> $bytes
     * @return array<int,int>
     */
    private function buildDataBits(array $bytes, int $capacity): array
    {
        $bits = [];
        $this->appendBits($bits, 0b0100, 4); // byte mode
        $lengthBits = $capacity <= 80 ? 8 : 16;
        $this->appendBits($bits, count($bytes), $lengthBits);
        foreach ($bytes as $byte) {
            $this->appendBits($bits, $byte, 8);
        }
        $maxBits = $capacity * 8;
        $terminator = min(4, $maxBits - count($bits));
        $this->appendBits($bits, 0, $terminator);
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }
        $padBytes = [0b11101100, 0b00010001];
        $padIndex = 0;
        while ((count($bits) / 8) < $capacity) {
            $this->appendBits($bits, $padBytes[$padIndex % 2], 8);
            $padIndex++;
        }
        return $bits;
    }

    /**
     * @param array<int,int> $bits
     * @return array<int,int>
     */
    private function applyErrorCorrection(array $bits, int $dataCodewords, int $ecCodewords): array
    {
        $data = $this->bitsToCodewords($bits);
        $data = array_slice($data, 0, $dataCodewords);
        $generator = $this->buildGeneratorPolynomial($ecCodewords);
        $message = array_merge($data, array_fill(0, $ecCodewords, 0));
        $remainder = $this->polyMod($message, $generator);
        $ec = array_fill(0, $ecCodewords, 0);
        $offset = $ecCodewords - count($remainder);
        foreach ($remainder as $index => $value) {
            $ec[$index + $offset] = $value;
        }
        return array_merge($data, $ec);
    }

    /**
     * @param array<int,int> $codewords
     * @return array<int,int>
     */
    private function codewordsToBits(array $codewords): array
    {
        $bits = [];
        foreach ($codewords as $cw) {
            $this->appendBits($bits, $cw, 8);
        }
        return $bits;
    }

    /**
     * @return array{array<int,array<int,int>>,array<int,array<int,bool>>}
     */
    private function initializeMatrix(int $dimension, array $alignmentCenters): array
    {
        $matrix = [];
        $reserved = [];
        for ($i = 0; $i < $dimension; $i++) {
            $matrix[$i] = array_fill(0, $dimension, null);
            $reserved[$i] = array_fill(0, $dimension, false);
        }

        $this->placeFinderPatterns($matrix, $reserved, 0, 0);
        $this->placeFinderPatterns($matrix, $reserved, $dimension - 7, 0);
        $this->placeFinderPatterns($matrix, $reserved, 0, $dimension - 7);
        $this->placeTimingPatterns($matrix, $reserved);
        $this->placeAlignmentPatterns($matrix, $reserved, $alignmentCenters);
        $this->placeDarkModule($matrix, $reserved);
        $this->reserveFormatAreas($matrix, $reserved);

        return [$matrix, $reserved];
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function placeFinderPatterns(array &$matrix, array &$reserved, int $row, int $col): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $value = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) ? 1 : 0;
                $this->setModule($matrix, $reserved, $row + $r, $col + $c, $value, true);
            }
        }
        $this->drawSeparator($matrix, $reserved, $row, $col, 7);
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function drawSeparator(array &$matrix, array &$reserved, int $row, int $col, int $size): void
    {
        $dimension = count($matrix);
        for ($i = -1; $i <= $size; $i++) {
            $this->setModule($matrix, $reserved, $row - 1, $col + $i, 0, true);
            $this->setModule($matrix, $reserved, $row + $size, $col + $i, 0, true);
            $this->setModule($matrix, $reserved, $row + $i, $col - 1, 0, true);
            $this->setModule($matrix, $reserved, $row + $i, $col + $size, 0, true);
        }
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function placeTimingPatterns(array &$matrix, array &$reserved): void
    {
        $dimension = count($matrix);
        for ($i = 0; $i < $dimension; $i++) {
            if ($matrix[6][$i] === null) {
                $this->setModule($matrix, $reserved, 6, $i, $i % 2 === 0 ? 1 : 0, true);
            }
            if ($matrix[$i][6] === null) {
                $this->setModule($matrix, $reserved, $i, 6, $i % 2 === 0 ? 1 : 0, true);
            }
        }
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function placeAlignmentPatterns(array &$matrix, array &$reserved, array $centers): void
    {
        if (empty($centers)) {
            return;
        }
        $dimension = count($matrix);
        foreach ($centers as $rowCenter) {
            foreach ($centers as $colCenter) {
                if ($rowCenter <= 7 && $colCenter <= 7) {
                    continue;
                }
                if ($rowCenter <= 7 && $colCenter >= $dimension - 8) {
                    continue;
                }
                if ($colCenter <= 7 && $rowCenter >= $dimension - 8) {
                    continue;
                }
                $this->drawAlignmentPattern($matrix, $reserved, $rowCenter, $colCenter);
            }
        }
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function drawAlignmentPattern(array &$matrix, array &$reserved, int $rowCenter, int $colCenter): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $value = (max(abs($r), abs($c)) === 2 || ($r === 0 && $c === 0)) ? 1 : 0;
                $this->setModule($matrix, $reserved, $rowCenter + $r, $colCenter + $c, $value, true);
            }
        }
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function placeDarkModule(array &$matrix, array &$reserved): void
    {
        $size = count($matrix);
        $row = 4 * $this->getVersionFromSize($size) + 9;
        $col = 8;
        if ($row < $size) {
            $this->setModule($matrix, $reserved, $row, $col, 1, true);
        }
    }

    private function getVersionFromSize(int $size): int
    {
        return (int) floor(($size - 21) / 4) + 1;
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function reserveFormatAreas(array &$matrix, array &$reserved): void
    {
        $size = count($matrix);
        $coords = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8], [7, 8],
            [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
            [$size - 1, 8], [$size - 2, 8], [$size - 3, 8], [$size - 4, 8], [$size - 5, 8], [$size - 6, 8], [$size - 7, 8],
            [8, $size - 8], [8, $size - 7], [8, $size - 6], [8, $size - 5], [8, $size - 4], [8, $size - 3], [8, $size - 2], [8, $size - 1],
        ];
        foreach ($coords as [$r, $c]) {
            if ($r >= 0 && $r < $size && $c >= 0 && $c < $size) {
                $this->setModule($matrix, $reserved, $r, $c, 0, true);
            }
        }
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function placeDataBits(array &$matrix, array &$reserved, array $bits): void
    {
        $size = count($matrix);
        $col = $size - 1;
        $bitIndex = 0;
        $directionUp = true;
        while ($col > 0) {
            if ($col === 6) {
                $col--;
            }
            for ($i = 0; $i < $size; $i++) {
                $row = $directionUp ? ($size - 1 - $i) : $i;
                for ($c = 0; $c < 2; $c++) {
                    $currentCol = $col - $c;
                    if ($matrix[$row][$currentCol] !== null) {
                        continue;
                    }
                    $value = $bitIndex < count($bits) ? $bits[$bitIndex] : 0;
                    $matrix[$row][$currentCol] = $value;
                    $reserved[$row][$currentCol] = false;
                    $bitIndex++;
                }
            }
            $col -= 2;
            $directionUp = !$directionUp;
        }
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function applyMask(array &$matrix, array &$reserved): void
    {
        $size = count($matrix);
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($matrix[$row][$col] === null) {
                    $matrix[$row][$col] = 0;
                }
                if (!$reserved[$row][$col]) {
                    if ((($row + $col) % 2) === 0) {
                        $matrix[$row][$col] ^= 1;
                    }
                }
            }
        }
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function placeFormatInfo(array &$matrix, array &$reserved): void
    {
        $formatBits = str_split('111011111000100');
        $size = count($matrix);
        $targetsA = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8], [7, 8],
            [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];
        $targetsB = [
            [$size - 1, 8], [$size - 2, 8], [$size - 3, 8], [$size - 4, 8], [$size - 5, 8], [$size - 6, 8], [$size - 7, 8],
            [8, $size - 8], [8, $size - 7], [8, $size - 6], [8, $size - 5], [8, $size - 4], [8, $size - 3], [8, $size - 2], [8, $size - 1],
        ];
        foreach ($targetsA as $index => [$r, $c]) {
            $bit = (int) $formatBits[$index];
            $matrix[$r][$c] = $bit;
        }
        foreach ($targetsB as $index => [$r, $c]) {
            $bit = (int) $formatBits[$index];
            $matrix[$r][$c] = $bit;
        }
    }

    /**
     * @param array<int,int> $bits
     */
    private function appendBits(array &$bits, int $value, int $length): void
    {
        for ($i = $length - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    /**
     * @param array<int,int> $bits
     * @return array<int,int>
     */
    private function bitsToCodewords(array $bits): array
    {
        $codewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $value = 0;
            for ($j = 0; $j < 8; $j++) {
                $value = ($value << 1) | ($bits[$i + $j] ?? 0);
            }
            $codewords[] = $value;
        }
        return $codewords;
    }

    private function initGaloisTables(): void
    {
        $exp = [1];
        $log = array_fill(0, 256, 0);
        for ($i = 1; $i < 256; $i++) {
            $prev = $exp[$i - 1] * 2;
            if ($prev >= 256) {
                $prev ^= 0x11D;
            }
            $exp[$i] = $prev;
        }
        for ($i = 256; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 256];
        }
        for ($i = 0; $i < 255; $i++) {
            $log[$exp[$i]] = $i;
        }
        $this->expTable = $exp;
        $this->logTable = $log;
    }

    /**
     * @return array<int,int>
     */
    private function buildGeneratorPolynomial(int $degree): array
    {
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $poly = $this->polyMultiply($poly, [1, $this->gfPow(2, $i)]);
        }
        return $poly;
    }

    /**
     * @param array<int,int> $a
     * @param array<int,int> $b
     * @return array<int,int>
     */
    private function polyMultiply(array $a, array $b): array
    {
        $result = array_fill(0, count($a) + count($b) - 1, 0);
        foreach ($a as $i => $av) {
            foreach ($b as $j => $bv) {
                $result[$i + $j] ^= $this->gfMul($av, $bv);
            }
        }
        return $result;
    }

    /**
     * @param array<int,int> $message
     * @param array<int,int> $divisor
     * @return array<int,int>
     */
    private function polyMod(array $message, array $divisor): array
    {
        $msg = $message;
        while (count($msg) >= count($divisor)) {
            $coef = $msg[0];
            if ($coef !== 0) {
                foreach ($divisor as $i => $div) {
                    $msg[$i] ^= $this->gfMul($div, $coef);
                }
            }
            array_shift($msg);
        }
        return $msg;
    }

    private function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }
        $logSum = $this->logTable[$x] + $this->logTable[$y];
        return $this->expTable[$logSum];
    }

    private function gfPow(int $x, int $power): int
    {
        if ($power === 0) {
            return 1;
        }
        $logValue = ($this->logTable[$x] * $power) % 255;
        return $this->expTable[$logValue];
    }

    /**
     * @param array<int,array<int,int|null>> $matrix
     * @param array<int,array<int,bool>> $reserved
     */
    private function setModule(array &$matrix, array &$reserved, int $row, int $col, int $value, bool $fixed): void
    {
        $size = count($matrix);
        if ($row < 0 || $col < 0 || $row >= $size || $col >= $size) {
            return;
        }
        $matrix[$row][$col] = $value;
        $reserved[$row][$col] = $fixed;
    }
}
