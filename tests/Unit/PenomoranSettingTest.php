<?php

namespace Tests\Unit;

use App\Models\NomorPpat;
use App\Models\PenomoranSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PenomoranSettingTest extends TestCase
{
    public function test_it_formats_sequence_with_numeric_month(): void
    {
        $formatted = PenomoranSetting::formatNomor(
            'nnn/CN/mm/yyyy',
            7,
            Carbon::parse('2026-04-12'),
            'number'
        );

        $this->assertSame('007/CN/04/2026', $formatted);
    }

    public function test_it_formats_sequence_with_roman_month_and_suffix(): void
    {
        $formatted = PenomoranSetting::formatNomor(
            'nnn/CN/mm/yy',
            7,
            Carbon::parse('2026-04-12'),
            'roman',
            'A'
        );

        $this->assertSame('007A/CN/IV/26', $formatted);
    }

    public function test_a_job_partner_number_is_owned_by_the_partner_and_used_by_the_configured_notary(): void
    {
        Cache::put('setting_perusahaan_nama_notaris', 'Notaris Contoh');
        $number = new NomorPpat([
            'rekanan' => 1,
            'job_divisi_form_order_id' => 123,
        ]);
        $number->setRelation('notarisRekanan', (object) ['nama' => 'Kantor Rekanan']);

        $this->assertSame('Kantor Rekanan', $number->pemilik_nomor);
        $this->assertSame('Notaris Contoh', $number->pemakai_nomor);
    }

    public function test_a_report_number_is_owned_by_the_configured_notary_and_used_by_the_partner(): void
    {
        Cache::put('setting_perusahaan_nama_notaris', 'Notaris Contoh');
        $number = new NomorPpat([
            'rekanan' => 0,
            'job_divisi_form_order_id' => 0,
        ]);
        $number->setRelation('notarisRekanan', (object) ['nama' => 'Kantor Rekanan']);

        $this->assertSame('Notaris Contoh', $number->pemilik_nomor);
        $this->assertSame('Notaris Rekanan: Kantor Rekanan', $number->pemakai_nomor);
    }
}
