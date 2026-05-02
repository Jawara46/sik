<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GraduationDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;

class SklPdfService
{
    private const TEMPLATE_VERSION = '2026-03-23-skl-pdf-v2';

    public function render(GraduationDocument $document): string
    {
        if ($document->document_type !== GraduationDocumentType::SKL) {
            throw new \InvalidArgumentException('SklPdfService hanya menerima document_type skl.');
        }

        $document->loadMissing(['school', 'student']);

        $payload = is_array($document->snapshot_payload) ? $document->snapshot_payload : [];
        $cacheRelativePath = $this->buildCacheRelativePath($document, $payload);
        $cacheAbsolutePath = Storage::disk('local')->path($cacheRelativePath);

        if (Storage::disk('local')->exists($cacheRelativePath)) {
            return $cacheAbsolutePath;
        }

        $this->cleanupStaleCacheFiles($document, $cacheRelativePath);

        $qrCode = (new QRCode(new QROptions([
            'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
            'scale' => 4,
            'imageBase64' => true,
        ])))->render(url('/verifikasi-dokumen/' . $document->verification_token));

        $school = (array) data_get($payload, 'school', []);
        $student = (array) data_get($payload, 'student', []);
        $documentMeta = (array) data_get($payload, 'document', []);

        $data = [
            'document' => $document,
            'school' => $school,
            'student' => $student,
            'documentMeta' => $documentMeta,
            'qrCode' => $qrCode,
            'letterheadPath' => $this->optimizePdfAsset($this->resolveStorageAssetPath($school['kop_surat'] ?? null), 'skl-letterhead', 1800, 420),
            'schoolLogoPath' => $this->optimizePdfAsset(
                $this->resolveStorageAssetPath($school['logo'] ?? null) ?? $this->resolvePublicAssetPath('assets/img/logo.png'),
                'skl-school-logo',
                360,
                360
            ),
            'tutWuriPath' => $this->resolvePublicAssetPath('assets/img/Logo_tutwuri.svg'),
            'ttdKepsekPath' => $this->optimizePdfAsset($this->resolveStorageAssetPath($school['ttd_kepsek'] ?? null), 'skl-ttd-kepsek', 520, 180),
            'stempelPath' => $this->optimizePdfAsset($this->resolveStorageAssetPath($school['stempel_sekolah'] ?? null), 'skl-stempel', 360, 360),
            'studentPhotoPath' => $this->optimizePdfAsset($this->resolveStorageAssetPath($student['photo_path'] ?? null), 'skl-student-photo', 420, 560),
        ];

        $pdfBinary = Pdf::loadView('admin.graduation.pdf.skl', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isPhpEnabled', false)
            ->setOption('chroot', [public_path(), storage_path('app/public'), storage_path('app/private')])
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 96)
            ->setOption('defaultFont', 'Times New Roman')
            ->output();

        Storage::disk('local')->put($cacheRelativePath, $pdfBinary);

        $document->forceFill([
            'pdf_path' => $cacheRelativePath,
            'generated_at' => now(),
        ])->save();

        return $cacheAbsolutePath;
    }

    private function buildCacheRelativePath(GraduationDocument $document, array $payload): string
    {
        $payloadHash = hash('sha1', json_encode($payload, JSON_THROW_ON_ERROR));
        $fingerprint = sha1(implode('|', [
            self::TEMPLATE_VERSION,
            $document->id,
            $document->updated_at?->timestamp ?? 0,
            $payloadHash,
            (string) ($document->pdf_hash ?? ''),
        ]));

        return 'graduation-documents/skl/document-' . $document->id . '/' . $fingerprint . '.pdf';
    }

    private function cleanupStaleCacheFiles(GraduationDocument $document, string $keepRelativePath): void
    {
        $directory = dirname($keepRelativePath);
        foreach (Storage::disk('local')->files($directory) as $file) {
            if ($file !== $keepRelativePath) {
                Storage::disk('local')->delete($file);
            }
        }
    }

    private function optimizePdfAsset(?string $assetUri, string $prefix, int $maxWidth, int $maxHeight): ?string
    {
        if (!is_string($assetUri) || $assetUri === '' || !extension_loaded('gd')) {
            return $assetUri;
        }

        $sourcePath = str_starts_with($assetUri, 'file://')
            ? substr($assetUri, 7)
            : $assetUri;

        if (!is_string($sourcePath) || !is_file($sourcePath)) {
            return $assetUri;
        }

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return $assetUri;
        }

        [$sourceWidth, $sourceHeight, $sourceType] = $imageInfo;
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return $assetUri;
        }

        $scale = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = max((int) round($sourceWidth * $scale), 1);
        $targetHeight = max((int) round($sourceHeight * $scale), 1);

        $extension = $sourceType === IMAGETYPE_JPEG ? 'jpg' : 'png';
        $hash = sha1(implode('|', [
            $sourcePath,
            filemtime($sourcePath) ?: 0,
            $sourceWidth,
            $sourceHeight,
            $targetWidth,
            $targetHeight,
            $sourceType,
            self::TEMPLATE_VERSION,
        ]));

        $relativePath = 'graduation-documents/assets/' . $prefix . '-' . $hash . '.' . $extension;
        $absolutePath = Storage::disk('local')->path($relativePath);

        if (is_file($absolutePath)) {
            return 'file://' . $absolutePath;
        }

        $sourceImage = match ($sourceType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($sourceImage === false) {
            return $assetUri;
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($targetImage === false) {
            imagedestroy($sourceImage);
            return $assetUri;
        }

        if ($extension === 'png') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        } else {
            $white = imagecolorallocate($targetImage, 255, 255, 255);
            imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $white);
        }

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        Storage::disk('local')->makeDirectory('graduation-documents/assets');

        $saved = $extension === 'jpg'
            ? imagejpeg($targetImage, $absolutePath, 82)
            : imagepng($targetImage, $absolutePath, 6);

        imagedestroy($targetImage);
        imagedestroy($sourceImage);

        if ($saved !== true || !is_file($absolutePath)) {
            return $assetUri;
        }

        return 'file://' . $absolutePath;
    }

    private function resolvePublicAssetPath(string $relativePath): ?string
    {
        $absolutePath = public_path(ltrim($relativePath, '/'));

        return is_file($absolutePath) ? 'file://' . $absolutePath : null;
    }

    private function resolveStorageAssetPath(?string $relativePath): ?string
    {
        if (!is_string($relativePath) || trim($relativePath) === '') {
            return null;
        }

        $absolutePath = storage_path('app/public/' . ltrim($relativePath, '/'));

        return is_file($absolutePath) ? 'file://' . $absolutePath : null;
    }
}
