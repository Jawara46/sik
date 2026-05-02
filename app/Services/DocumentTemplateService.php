<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Carbon;

class DocumentTemplateService
{
    public const TYPE_SKL = 'skl';
    public const TYPE_TRANSCRIPT = 'transcript';
    public const TYPE_UKK_STATEMENT = 'ukk_statement';

    /**
     * @return array<int, array{type:string,name:string}>
     */
    public function supportedTypes(): array
    {
        return [
            ['type' => self::TYPE_SKL, 'name' => 'Template SKL'],
            ['type' => self::TYPE_TRANSCRIPT, 'name' => 'Template Transkrip'],
            ['type' => self::TYPE_UKK_STATEMENT, 'name' => 'Template Surat Keterangan UKK'],
        ];
    }

    public function forSchool(School $school, string $documentType): DocumentTemplate
    {
        return DocumentTemplate::query()->firstOrCreate(
            [
                'school_id' => $school->id,
                'document_type' => $documentType,
            ],
            $this->defaultAttributes($documentType)
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    public function update(School $school, string $documentType, array $input): DocumentTemplate
    {
        $template = $this->forSchool($school, $documentType);

        $template->fill([
            'name' => (string) ($input['name'] ?? $template->name),
            'title_html' => $this->sanitizeHtml((string) ($input['title_html'] ?? '')),
            'intro_html' => $this->sanitizeHtml((string) ($input['intro_html'] ?? '')),
            'body_html' => $this->sanitizeHtml((string) ($input['body_html'] ?? '')),
            'closing_html' => $this->sanitizeHtml((string) ($input['closing_html'] ?? '')),
        ]);

        $template->save();

        return $template->refresh();
    }

    /**
     * @param array<string, string> $variables
     * @return array{title_html:string,intro_html:string,body_html:string,closing_html:string}
     */
    public function renderSections(DocumentTemplate $template, array $variables): array
    {
        return [
            'title_html' => $this->replacePlaceholders((string) $template->title_html, $variables),
            'intro_html' => $this->replacePlaceholders((string) $template->intro_html, $variables),
            'body_html' => $this->replacePlaceholders((string) $template->body_html, $variables),
            'closing_html' => $this->replacePlaceholders((string) $template->closing_html, $variables),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function previewVariables(School $school, string $documentType, ?Student $student = null): array
    {
        $student ??= $school->students()->with('major')->orderBy('name')->first();
        $studentName = $student?->name ?? 'AISYAH PUTRI';
        $majorName = $student?->major?->name ?? ($school->tipe_sekolah === 'SMK' ? 'AKUNTANSI KEUANGAN LEMBAGA' : 'UMUM');
        $issuedDate = $school->tanggal_surat instanceof Carbon
            ? $school->tanggal_surat->locale('id')->translatedFormat('d F Y')
            : now()->locale('id')->translatedFormat('d F Y');

        return [
            '{{nama_sekolah}}' => e((string) ($school->nama_sekolah ?: $school->name ?: 'Nama Sekolah')),
            '{{npsn}}' => e((string) ($school->npsn ?: '00000000')),
            '{{alamat_sekolah}}' => e((string) ($school->alamat_sekolah ?: 'Alamat sekolah belum diisi')),
            '{{telepon_sekolah}}' => e((string) ($school->telepon_sekolah ?: '081234567890')),
            '{{email_sekolah}}' => e((string) ($school->email_sekolah ?: 'sekolah@example.com')),
            '{{web_sekolah}}' => e((string) ($school->web_sekolah ?: 'https://sekolah.sch.id')),
            '{{tahun_pelajaran}}' => e((string) ($school->tahun_pelajaran ?: now()->format('Y') . '/' . (now()->year + 1))),
            '{{nama_siswa}}' => e($studentName),
            '{{nisn}}' => e((string) ($student?->nisn ?: '0012345678')),
            '{{tempat_lahir}}' => e((string) ($student?->tempat_lahir ?: 'Jakarta')),
            '{{tanggal_lahir}}' => e((string) ($student?->tanggal_lahir?->locale('id')->translatedFormat('d F Y') ?: '17 Agustus 2008')),
            '{{nama_orang_tua}}' => e((string) ($student?->nama_orang_tua ?: 'Bapak/Ibu Wali')),
            '{{jurusan}}' => e($majorName),
            '{{status_kelulusan}}' => e((string) ($student?->status ?: 'Lulus')),
            '{{tempat_surat}}' => e((string) ($school->tempat_surat ?: 'Kabupaten')),
            '{{tanggal_surat}}' => e($issuedDate),
            '{{nama_kepsek}}' => e((string) ($school->nama_kepsek ?: 'Nama Kepala Sekolah')),
            '{{nip_kepsek}}' => e((string) ($school->nip_kepsek ?: '198001012005011001')),
            '{{jenis_dokumen}}' => e(match ($documentType) {
                self::TYPE_SKL => 'SKL',
                self::TYPE_TRANSCRIPT => 'Transkrip',
                default => 'Surat Keterangan UKK',
            }),
        ];
    }

    /**
     * @return array<int, array{
     *   category:string,
     *   title:string,
     *   items:array<int, array{token:string,label:string,description:string}>
     * }>
     */
    public function placeholders(): array
    {
        return [
            [
                'category' => 'school',
                'title' => 'Data Sekolah',
                'items' => [
                    ['token' => '{{nama_sekolah}}', 'label' => 'Nama Sekolah', 'description' => 'Nama resmi sekolah'],
                    ['token' => '{{npsn}}', 'label' => 'NPSN', 'description' => 'Nomor pokok sekolah nasional'],
                    ['token' => '{{alamat_sekolah}}', 'label' => 'Alamat Sekolah', 'description' => 'Alamat lengkap sekolah'],
                    ['token' => '{{telepon_sekolah}}', 'label' => 'No. Telp Sekolah', 'description' => 'Nomor telepon sekolah'],
                    ['token' => '{{email_sekolah}}', 'label' => 'Email Sekolah', 'description' => 'Alamat email resmi sekolah'],
                    ['token' => '{{web_sekolah}}', 'label' => 'Website Sekolah', 'description' => 'Alamat website sekolah'],
                ],
            ],
            [
                'category' => 'student',
                'title' => 'Data Siswa',
                'items' => [
                    ['token' => '{{nama_siswa}}', 'label' => 'Nama Siswa', 'description' => 'Nama lengkap siswa'],
                    ['token' => '{{nisn}}', 'label' => 'NISN', 'description' => 'Nomor induk siswa nasional'],
                    ['token' => '{{tempat_lahir}}', 'label' => 'Tempat Lahir', 'description' => 'Tempat lahir siswa'],
                    ['token' => '{{tanggal_lahir}}', 'label' => 'Tanggal Lahir', 'description' => 'Tanggal lahir siswa'],
                    ['token' => '{{nama_orang_tua}}', 'label' => 'Nama Orang Tua', 'description' => 'Nama ayah/ibu/wali'],
                    ['token' => '{{jurusan}}', 'label' => 'Jurusan', 'description' => 'Jurusan atau program keahlian siswa'],
                ],
            ],
            [
                'category' => 'academic',
                'title' => 'Data Akademik',
                'items' => [
                    ['token' => '{{tahun_pelajaran}}', 'label' => 'Tahun Pelajaran', 'description' => 'Tahun pelajaran aktif'],
                    ['token' => '{{status_kelulusan}}', 'label' => 'Status Kelulusan', 'description' => 'Status lulus / tidak lulus / pending'],
                    ['token' => '{{jenis_dokumen}}', 'label' => 'Jenis Dokumen', 'description' => 'Nama dokumen yang sedang dicetak'],
                ],
            ],
            [
                'category' => 'legal',
                'title' => 'Penandatanganan',
                'items' => [
                    ['token' => '{{tempat_surat}}', 'label' => 'Tempat Surat', 'description' => 'Lokasi penerbitan surat'],
                    ['token' => '{{tanggal_surat}}', 'label' => 'Tanggal Surat', 'description' => 'Tanggal penerbitan surat'],
                    ['token' => '{{nama_kepsek}}', 'label' => 'Nama Kepala Sekolah', 'description' => 'Nama kepala sekolah'],
                    ['token' => '{{nip_kepsek}}', 'label' => 'NIP Kepala Sekolah', 'description' => 'NIP kepala sekolah'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function defaultAttributes(string $documentType): array
    {
        return match ($documentType) {
            self::TYPE_TRANSCRIPT => [
                'name' => 'Template Transkrip',
                'title_html' => '<p class="ql-align-center"><strong>TRANSKRIP NILAI</strong></p>',
                'intro_html' => '<p>Dokumen ini memuat rekap hasil belajar peserta didik atas nama <strong>{{nama_siswa}}</strong> pada {{nama_sekolah}} tahun pelajaran {{tahun_pelajaran}}.</p>',
                'body_html' => '<p>Tabel berikut menampilkan nilai semester 1 sampai 6 beserta rata-rata akhir untuk setiap mata pelajaran.</p>',
                'closing_html' => '<p>Transkrip ini diterbitkan untuk dipergunakan sebagaimana mestinya.</p>',
            ],
            self::TYPE_UKK_STATEMENT => [
                'name' => 'Template Surat Keterangan UKK',
                'title_html' => '<p class="ql-align-center"><strong>SURAT KETERANGAN</strong><br><strong>TELAH MENGIKUTI UJI KOMPETENSI KEAHLIAN</strong></p>',
                'intro_html' => '<p>Kepala {{nama_sekolah}} menerangkan bahwa:</p>',
                'body_html' => '<p>Yang bersangkutan telah mengikuti Uji Kompetensi Keahlian pada paket keahlian <strong>{{jurusan}}</strong> dengan rincian unit kompetensi sebagai berikut:</p>',
                'closing_html' => '<p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>',
            ],
            default => [
                'name' => 'Template SKL',
                'title_html' => '<p class="ql-align-center"><strong>SURAT KETERANGAN LULUS</strong></p>',
                'intro_html' => '<p>Kepala {{nama_sekolah}} menerangkan bahwa:</p>',
                'body_html' => '<p>Berdasarkan hasil rapat dewan guru dan ketentuan akademik yang berlaku, peserta didik tersebut di atas dinyatakan <strong>{{status_kelulusan}}</strong> dari {{nama_sekolah}} pada tahun pelajaran {{tahun_pelajaran}}.</p>',
                'closing_html' => '<p>Surat keterangan ini dipergunakan sebagaimana mestinya sambil menunggu dokumen resmi lainnya sesuai ketentuan yang berlaku.</p>',
            ],
        };
    }

    private function sanitizeHtml(string $html): string
    {
        $clean = trim($html);
        if ($clean === '') {
            return '';
        }

        $clean = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $clean) ?? $clean;
        $clean = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $clean) ?? $clean;
        $clean = preg_replace('/on[a-z]+\s*=\s*(["\']).*?\1/i', '', $clean) ?? $clean;
        $clean = preg_replace('/javascript\s*:/i', '', $clean) ?? $clean;

        return strip_tags($clean, '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><h1><h2><h3><h4><span>');
    }

    /**
     * @param array<string, string> $variables
     */
    private function replacePlaceholders(string $html, array $variables): string
    {
        return strtr($html, $variables);
    }
}
