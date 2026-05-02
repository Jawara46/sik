<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\RomanHelper;
use App\Models\School;
use App\Models\SmkAssessor;
use App\Models\SmkRecord;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    private const TEMPLATE_VERSION = '2026-03-23-competency-documents-v3';
    private const DOCUMENT_CERTIFICATE = 'certificate';
    private const DOCUMENT_STATEMENT = 'statement';

    public function __construct(
        private readonly DocumentTemplateService $documentTemplateService,
    ) {
    }


    public function generateDynamicNumber(Student $student, string $pattern, string $mode = 'dynamic'): string
    {
        $student->loadMissing('major', 'school');
        $schoolId = $student->school_id;
        $majorCode = strtoupper((string) ($student->major?->code ?? 'UMUM'));

        $year = now()->format('Y');
        $month = (int) now()->format('n');
        $romanMonth = RomanHelper::convertMonthToRoman($month);

        $resolved = str_replace(
            ['{JURUSAN}', '{BULAN}', '{TAHUN}'],
            [$majorCode, $romanMonth, $year],
            $pattern
        );

        if ($mode === 'static') {
            return $resolved;
        }

        $parts = explode('{NO}', $resolved);
        $prefix = $parts[0];
        $suffix = $parts[1] ?? '';

        $latestRecord = SmkRecord::query()
            ->whereHas('student', function (Builder $q) use ($schoolId): void {
                $q->where('school_id', $schoolId);
            })
            ->whereYear('updated_at', $year)
            ->whereNotNull('certificate_number')
            ->orderByDesc('id')
            ->first();

        $increment = 1;
        if ($latestRecord !== null && is_string($latestRecord->certificate_number)) {
            $certNum = $latestRecord->certificate_number;
            $numberPart = $certNum;

            if ($prefix !== '' && str_starts_with($numberPart, $prefix)) {
                $numberPart = substr($numberPart, strlen($prefix));
            }

            if ($suffix !== '' && str_ends_with($numberPart, $suffix)) {
                $numberPart = substr($numberPart, 0, -strlen($suffix));
            }

            $numberPart = preg_replace('/[^0-9]/', '', (string) $numberPart);

            if ($numberPart !== null && $numberPart !== '' && is_numeric($numberPart)) {
                $increment = (int) $numberPart + 1;
            }
        }

        $formattedIncrement = str_pad((string) $increment, 3, '0', STR_PAD_LEFT);

        return $prefix . $formattedIncrement . $suffix;
    }

    public function renderCertificatePdf(SmkRecord $record, School $school): string
    {
        return $this->renderDocumentPdf($record, $school, self::DOCUMENT_CERTIFICATE);
    }

    public function renderStatementPdf(SmkRecord $record, School $school): string
    {
        return $this->renderDocumentPdf($record, $school, self::DOCUMENT_STATEMENT);
    }

    private function renderDocumentPdf(SmkRecord $record, School $school, string $documentType): string
    {
        $record = $this->prepareRecord($record, $school);
        $assessor = $this->loadAssessor($record, $school);
        $templateSections = $documentType === self::DOCUMENT_STATEMENT
            ? $this->resolveStatementTemplateSections($school, $record->student)
            : null;
        $templateHash = $templateSections !== null
            ? sha1(implode('|', $templateSections))
            : null;
        $cacheRelativePath = $this->buildCacheRelativePath($record, $school, $assessor, $documentType, $templateHash);
        $cacheAbsolutePath = Storage::disk('local')->path($cacheRelativePath);

        if (Storage::disk('local')->exists($cacheRelativePath)) {
            return $cacheAbsolutePath;
        }

        $this->cleanupStaleCacheFiles($record, $cacheRelativePath);

        $verifyUrl = url('/') . '/verify-ukk/' . $record->certificate_number;
        $qrCodePngBase64 = (new QRCode(new QROptions([
            'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
            'scale' => 4,
            'imageBase64' => true,
        ])))->render($verifyUrl);

        $documentDate = $school->tanggal_surat ?? $record->exam_date ?? now();
        $documentPlace = (string) ($school->tempat_surat ?: ($school->kota ?? 'Kabupaten'));

        $data = [
            'record' => $record,
            'student' => $record->student,
            'major' => $record->student->major,
            'school' => $school,
            'assessor' => $assessor,
            'qrCode' => $qrCodePngBase64,
            'documentDate' => $documentDate,
            'documentPlace' => $documentPlace,
            'bgSertiPath' => $this->optimizePdfAsset($this->resolvePublicAssetPath('assets/img/bg-serti-print.jpg'), 'bg-serti', 1240, 1754),
            'tutWuriPath' => $this->resolvePublicAssetPath('assets/img/Logo_tutwuri.svg'),
            'schoolLogoPath' => $this->optimizePdfAsset(
                $this->resolveStorageAssetPath($school->logo) ?? $this->resolvePublicAssetPath('assets/img/logo.png'),
                'school-logo',
                360,
                360
            ),
            'letterheadPath' => $this->optimizePdfAsset($this->resolveStorageAssetPath($school->kop_surat), 'letterhead', 1800, 420),
            'ttdKepsekPath' => $this->optimizePdfAsset($this->resolveStorageAssetPath($school->ttd_kepsek), 'ttd-kepsek', 520, 180),
            'stempelPath' => $school->use_digital_stamp
                ? $this->optimizePdfAsset($this->resolveStorageAssetPath($school->stempel_sekolah), 'stempel', 360, 360)
                : null,
            'ttdPengujiPath' => $this->optimizePdfAsset($this->resolveStorageAssetPath($assessor?->ttd_penguji ?? null), 'ttd-penguji', 420, 160),
            'templateSections' => $templateSections,
        ];

        $view = $documentType === self::DOCUMENT_STATEMENT
            ? 'admin.smk.pdf.statement'
            : 'admin.smk.pdf.certificate';

        $pdfBinary = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isPhpEnabled', false)
            ->setOption('chroot', [public_path(), storage_path('app/public'), storage_path('app/private')])
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 96)
            ->setOption('defaultFont', 'Helvetica')
            ->output();

        Storage::disk('local')->put($cacheRelativePath, $pdfBinary);

        return $cacheAbsolutePath;
    }

    private function prepareRecord(SmkRecord $record, School $school): SmkRecord
    {
        $record->loadMissing(['student.major', 'student.school', 'units.smkUnit']);

        $needsSave = false;
        if ($record->certificate_number && str_contains($record->certificate_number, '{NO}')) {
            $record->certificate_number = null;
            $needsSave = true;
        }

        if (!$record->certificate_number) {
            $pattern = $school->certificate_number_pattern ?? '420/UKK.{JURUSAN}/{BULAN}/{TAHUN}/{NO}';
            $mode = $school->certificate_number_mode ?? 'dynamic';

            $record->certificate_number = $this->generateDynamicNumber($record->student, $pattern, $mode);
            $needsSave = true;
        }

        if (!$record->exam_date) {
            $record->exam_date = now();
            $needsSave = true;
        }

        if ($needsSave) {
            $record->save();
            $record->refresh()->loadMissing(['student.major', 'student.school', 'units.smkUnit']);
        }

        return $record;
    }

    private function loadAssessor(SmkRecord $record, School $school): ?SmkAssessor
    {
        return SmkAssessor::query()
            ->where('school_id', $school->id)
            ->where('major_id', $record->student->major_id)
            ->first();
    }

    private function buildCacheRelativePath(SmkRecord $record, School $school, ?SmkAssessor $assessor, string $documentType, ?string $templateHash = null): string
    {
        $recordUpdatedAt = $record->updated_at?->timestamp ?? 0;
        $schoolUpdatedAt = $school->updated_at?->timestamp ?? 0;
        $studentUpdatedAt = $record->student?->updated_at?->timestamp ?? 0;
        $majorUpdatedAt = $record->student?->major?->updated_at?->timestamp ?? 0;
        $assessorUpdatedAt = $assessor?->updated_at?->timestamp ?? 0;
        $unitsUpdatedAt = (int) $record->units->max(static fn ($unit) => $unit->updated_at?->timestamp ?? 0);
        $smkUnitsUpdatedAt = (int) $record->units->max(static fn ($unit) => $unit->smkUnit?->updated_at?->timestamp ?? 0);

        $fingerprint = sha1(implode('|', [
            self::TEMPLATE_VERSION,
            $documentType,
            $record->id,
            $record->certificate_number,
            $record->exam_date?->format('Y-m-d'),
            optional($school->tanggal_surat)->format('Y-m-d'),
            (string) ($school->tempat_surat ?? ''),
            $recordUpdatedAt,
            $schoolUpdatedAt,
            $studentUpdatedAt,
            $majorUpdatedAt,
            $assessorUpdatedAt,
            $unitsUpdatedAt,
            $smkUnitsUpdatedAt,
            (int) $school->use_digital_stamp,
            (string) $school->ttd_kepsek,
            (string) $school->stempel_sekolah,
            (string) $school->logo,
            (string) $school->kop_surat,
            (string) ($assessor?->ttd_penguji ?? ''),
            (string) $templateHash,
        ]));

        return 'certificates/cache/' . $documentType . '/record-' . $record->id . '/' . $fingerprint . '.pdf';
    }

    /**
     * @return array{title_html:string,intro_html:string,body_html:string,closing_html:string}
     */
    private function resolveStatementTemplateSections(School $school, Student $student): array
    {
        $template = $this->documentTemplateService->forSchool($school, DocumentTemplateService::TYPE_UKK_STATEMENT);

        return $this->documentTemplateService->renderSections(
            $template,
            $this->documentTemplateService->previewVariables($school, DocumentTemplateService::TYPE_UKK_STATEMENT, $student)
        );
    }

    private function cleanupStaleCacheFiles(SmkRecord $record, string $keepRelativePath): void
    {
        $directory = dirname($keepRelativePath);
        $files = Storage::disk('local')->files($directory);

        foreach ($files as $file) {
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

        $relativePath = 'certificates/assets/' . $prefix . '-' . $hash . '.' . $extension;
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

        Storage::disk('local')->makeDirectory('certificates/assets');

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
