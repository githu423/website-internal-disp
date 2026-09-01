<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PeranOtomatikTest extends TestCase
{
    private const HEADER = 'No.,NAMA LENGKAP,N I P,TEMPAT LAHIR,TANGGAL LAHIR,PANGKAT,TMT CPNS,PANGKAT,ASN,TMT SEKARANG,JABATAN SEKARANG,TMT JABATAN,PENDIDIKAN,JENIS KELAMIN,UNIT KERJA';

    private function baris(int $no, string $nama, string $nip, string $jabatan, string $pend, string $jk, string $unit): string
    {
        $f = array_fill(0, 15, '');
        $f[0] = (string) $no; $f[1] = $nama; $f[2] = $nip; $f[10] = $jabatan;
        $f[12] = $pend; $f[13] = $jk; $f[14] = $unit;
        return implode(',', $f);
    }

    private function csv(string $budiJabatan, string $budiUnit): string
    {
        return self::HEADER . "\n"
            . $this->baris(1, 'Budi Santoso', 'SIP111', $budiJabatan, 'S-1', 'L', $budiUnit) . "\n"
            . $this->baris(2, 'Sari Amalia', 'SIP222', 'PENGADMINISTRASI UMUM', 'S-1', 'P', 'SEKRETARIAT') . "\n";
    }

    private function tmp(string $isi): string
    {
        $f = tempnam(sys_get_temp_dir(), 'peg') . '.csv';
        file_put_contents($f, $isi);
        return $f;
    }

    private function impor(string $isi): array
    {
        return ImportService::applyPegawai(ImportService::rowsFrom($this->tmp($isi)));
    }

    public function test_peran_bidang_terisi_otomatis_dari_import_pegawai(): void
    {
        $this->buatKerangka();
        $hasil = $this->impor($this->csv('KEPALA BIDANG PEMASARAN', 'BIDANG PEMASARAN'));

        $this->assertSame(2, $hasil['dibuat']);
        $budi = User::where('nip', 'SIP111')->firstOrFail();
        $sari = User::where('nip', 'SIP222')->firstOrFail();
        $this->assertSame('bidang', $budi->role, 'Kabid harus otomatis menjadi peran bidang');
        $this->assertSame(3, (int) $budi->id_bidang, 'PEMASARAN → bidang ke-3');
        $this->assertSame('pegawai', $sari->role);
        $this->assertSame(1, (int) $sari->id_bidang, 'SEKRETARIAT → bidang ke-1');
        $this->assertSame('BIDANG PEMASARAN', Pegawai::where('nip', 'SIP111')->value('unit_kerja'));
    }

    public function test_kepala_uptd_ikut_naik_peran(): void
    {
        $this->assertSame('bidang', ImportService::peranDariJabatan('Kepala UPTD Pengelolaan Objek Wisata Wilayah Kawali'));
        $this->assertSame('bidang', ImportService::peranDariJabatan('KEPALA UPT PASAR WISATA'));
        $this->assertSame('pegawai', ImportService::peranDariJabatan('OPERATOR LAYANAN OPERASIONAL'));
    }

    public function test_perintah_artisan_sinkron_bekerja(): void
    {
        $this->buatKerangka();
        Pegawai::create(['nip' => 'SIP555', 'nama' => 'Ujang UPTD', 'jabatan' => 'KEPALA UPTD PENGELOLAAN OBJEK WISATA']);
        User::create(['nip' => 'SIP555', 'name' => 'Ujang UPTD', 'password' => 'rahasia123', 'role' => 'pegawai']);

        $this->artisan('dispar:sinkron-akun')->assertExitCode(0);
        $u = User::where('nip', 'SIP555')->firstOrFail();
        $this->assertSame('bidang', $u->role);
    }

    public function test_impor_ulang_sinkron_peran_dan_bidang_tanpa_duplikat(): void
    {
        $this->buatKerangka();
        $this->impor($this->csv('KEPALA BIDANG PEMASARAN', 'BIDANG PEMASARAN'));

        // file yang sama diunggah lagi → tidak ada duplikat & tidak ada perubahan
        $hasil = $this->impor($this->csv('KEPALA BIDANG PEMASARAN', 'BIDANG PEMASARAN'));
        $this->assertSame(0, $hasil['dibuat']);
        $this->assertSame(0, $hasil['akun_baru']);
        $this->assertSame(0, $hasil['akun_disinkron']);
        $this->assertSame(2, User::whereIn('nip', ['SIP111', 'SIP222'])->count());
        $this->assertSame(2, Pegawai::count());

        // jabatan pindah → akun lama ikut bergeser otomatis (tetap satu baris)
        $hasil = $this->impor($this->csv('KASI DESTINASI WISATA', 'BIDANG PENGEMBANGAN DAYA TARIK & DESTINASI PARIWISATA'));
        $this->assertSame(1, $hasil['akun_disinkron']);
        $budi = User::where('nip', 'SIP111')->firstOrFail();
        $this->assertSame(2, (int) $budi->id_bidang);
        $this->assertSame('bidang', $budi->role);
        $this->assertSame(2, User::whereIn('nip', ['SIP111', 'SIP222'])->count());
    }

    public function test_tombol_sinkron_memperbaiki_akun_lama(): void
    {
        $this->buatKerangka();
        // akun lama salah peran (manual) padahal pegawainya Kabid
        $p = Pegawai::create(['nip' => 'SIP333', 'nama' => 'Ujang Kabid', 'jabatan' => 'KEPALA BIDANG PEMASARAN', 'id_bidang' => 3]);
        $u = User::create(['nip' => 'SIP333', 'name' => 'Ujang Kabid', 'password' => 'rahasia123', 'role' => 'pegawai']);

        $this->actingAs($this->userAdmin())->post(route('admin.akun.sinkron'))->assertRedirect();
        $u->refresh();
        $this->assertSame('bidang', $u->role);
        $this->assertSame(3, (int) $u->id_bidang);
        unset($p);
    }

    public function test_buat_akun_baru_tanpa_pilihan_peran_otomatis_terisi(): void
    {
        $this->buatKerangka();
        Pegawai::create(['nip' => 'SIP444', 'nama' => 'Nining Kasesbang', 'jabatan' => 'KEPALA SUB BAGIAN PENUNJANG', 'unit_kerja' => 'SEKRETARIAT']);
        $this->actingAs($this->userAdmin())->post(route('admin.akun.store'), [
            'name' => 'Nining Kasesbang', 'nip' => 'SIP444', 'password' => 'rahasia123',
        ])->assertRedirect();

        $u = User::where('nip', 'SIP444')->firstOrFail();
        $this->assertSame('bidang', $u->role, 'Kasubbag → otomatis peran bidang');
        $this->assertSame(1, (int) $u->id_bidang);
    }

    public function test_pegawai_biasa_tidak_bisa_unggah_dokumen_bidang_bisa(): void
    {
        $this->buatKerangka();
        $file = fn () => UploadedFile::fake()->createWithContent('uji.txt', 'isi uji');
        $payload = fn () => ['judul' => 'Dok Uji', 'visibility' => 'publik', 'file' => $file()];

        $peg = $this->userPegawai(1);
        $this->actingAs($peg)->post(route('dokumen.store'), $payload())->assertForbidden();
        $this->assertSame(0, Dokumen::count());
        $this->actingAs($peg)->get(route('dokumen.index'))->assertOk()->assertDontSee('Unggah Dokumen');

        $kabid = $this->userPegawai(2, 'KABID1');
        $kabid->update(['role' => 'bidang']);
        $this->actingAs($kabid)->post(route('dokumen.store'), $payload())->assertSessionHas('sukses');
        $this->assertSame(1, Dokumen::count());
        $this->actingAs($kabid)->get(route('dokumen.index'))->assertOk()->assertSee('Unggah Dokumen', false);
    }
}
