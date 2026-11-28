<?php

namespace App\Http\Requests\Komunitas;

use App\Models\Dokumentasi;
use App\Support\BatasUnggah;
use Illuminate\Foundation\Http\FormRequest;

class StoreDokumentasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Dokumentasi::class) === true;
    }

    public function rules(): array
    {
        $fotoKb = BatasUnggah::fotoKb();
        $videoKb = BatasUnggah::videoKb();

        // ukuran batas mengikuti jenis berkas: video lebih besar dari foto
        $ext = strtolower((string) $this->file('berkas')?->getClientOriginalExtension());
        $maks = in_array($ext, ['mp4', 'webm'], true) ? $videoKb : $fotoKb;

        return [
            'laporan_banjir_id' => ['required', 'integer'],
            'distribusi_bantuan_id' => ['nullable', 'integer'],
            'keterangan' => ['nullable', 'string', 'max:'.(int) config('alir.dokumentasi.keterangan_maks', 1000)],
            'berkas' => ['required', 'file', 'mimes:jpg,jpeg,png,mp4,webm', 'mimetypes:image/jpeg,image/png,video/mp4,video/webm', 'max:'.$maks],
        ];
    }

    public function messages(): array
    {
        $fotoMb = BatasUnggah::mb(BatasUnggah::fotoKb());
        $videoMb = BatasUnggah::mb(BatasUnggah::videoKb());

        return [
            'laporan_banjir_id.required' => 'Pilih laporan yang terkait dengan dokumentasi ini.',
            'laporan_banjir_id.integer' => 'Laporan yang dipilih tidak valid.',
            'distribusi_bantuan_id.integer' => 'Item logistik yang dipilih tidak valid.',
            'keterangan.max' => 'Catatan kegiatan maksimal :max karakter.',
            'berkas.required' => 'Foto atau video wajib diunggah.',
            'berkas.file' => 'Berkas tidak valid atau gagal diunggah.',
            'berkas.uploaded' => "Berkas gagal diunggah. Pastikan ukuran foto maksimal {$fotoMb} MB atau video maksimal {$videoMb} MB.",
            'berkas.mimes' => 'Format berkas harus JPG, PNG (foto) atau MP4, WebM (video).',
            'berkas.mimetypes' => 'Isi berkas tidak sesuai dengan format foto atau video yang diizinkan.',
            'berkas.max' => 'Ukuran berkas terlalu besar. Foto maksimal '.$fotoMb.' MB, video maksimal '.$videoMb.' MB.',
        ];
    }
}
