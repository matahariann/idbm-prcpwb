<?php

namespace Database\Seeders;

use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuration = [
            [
                'VVARIABLE' => 'pengaturan_privasi_anda',
                'VVALUE' => '<strong>Privacy &amp; Terms – Aplikasi Web 3 Way Matching</strong> <p> Aplikasi ini merupakan <strong>sistem internal perusahaan</strong> yang digunakan untuk proses <strong>3 Way Matching (Purchase Order, Goods Receipt, dan Invoice)</strong>. </p> <p>Dengan menggunakan aplikasi ini, Anda menyetujui bahwa:</p> <ul style="padding-left:18px;"> <li>Data yang diakses dan diproses hanya untuk kepentingan operasional perusahaan</li> <li>Seluruh aktivitas pengguna dicatat dalam sistem (audit log)</li> <li>Akses data dibatasi berdasarkan hak akses dan peran pengguna</li> <li>Pengguna bertanggung jawab menjaga kerahasiaan akun dan password</li> <li>Penyalahgunaan sistem dapat dikenakan sanksi sesuai kebijakan perusahaan</li> </ul> <p> Data dikelola secara aman dan tidak dibagikan ke pihak ketiga tanpa izin resmi. </p> <p> <strong>Dengan login, Anda dianggap telah membaca dan menyetujui ketentuan ini.</strong> </p>',
            ],
            [
                'VVARIABLE' => 'TOKEN_EXPIRY_HOURS',
                'VVALUE' => '5',
            ],
            [
                'VVARIABLE' => 'CACHE_EXPIRY_HOURS',
                'VVALUE' => '24',
            ],
            [
                'VVARIABLE' => 'ppn',
                'VVALUE' => '12',
            ],
            [
                'VVARIABLE' => 'rumus_dpp',
                'VVALUE' => '11/12',
            ],
            [
                'VVARIABLE' => 'npwp_idbm_match',
                'VVALUE' => '0010022184092000',
            ],
            [
                'VVARIABLE' => 'limit_eskalated',
                'VVALUE' => '3',
            ],
            [
                'VVARIABLE' => 'tnc_verify_po_non_po',
                'VVALUE' => '<p>
                        Proses penerimaan barang di
                        <strong>Warehouse Direct Material PT. Kelola Bisnis Indonesia</strong>
                        dilaksanakan setiap hari sesuai
                        <strong>Hari Kerja Reguler (Senin–Jumat)</strong>.
                    </p>

                    <p>
                        Lokasi unloading dan waktu delivery telah diatur dalam
                        <strong>Mapping Jam Kedatangan Supplier</strong>.
                        Delivery yang tidak sesuai ketentuan
                        (Sabtu–Minggu, hari libur nasional, shift 3, tanpa Daily Request,
                        atau part khusus/urgent) wajib dikoordinasikan dengan pihak internal
                        IDBM dan dilengkapi dokumen pendukung
                        (memo atau form abnormal).
                    </p>

                    <hr>

                    <h6 class="fw-bold">
                        Ketentuan Delivery ke Warehouse Direct Material<br>
                        PT. Hitachi Astemo Bekasi Manufacturing
                    </h6>

                    <ol class="mt-3">
                        <li>
                            Barang yang dikirim harus sesuai dengan
                            <strong>SDIS</strong> yang telah ditandatangani oleh kedua belah pihak
                            (Supplier–IDBM), termasuk kewajiban pemasangan
                            <strong>label Part Number dengan QR Code</strong>
                            pada setiap kemasan.
                        </li>

                        <li>
                            Driver wajib memenuhi ketentuan berikut:
                            <ul>
                                <li>Memiliki SIM aktif sesuai jenis kendaraan</li>
                                <li>Mengenakan seragam supplier/ekspedisi (terdapat inisial nama)</li>
                                <li>Menggunakan sepatu keselamatan dan topi/helm</li>
                                <li>Membawa ganjal ban kendaraan</li>
                                <li>Memiliki SIO forklift aktif (jika bongkar mandiri)</li>
                            </ul>
                        </li>

                        <li>
                            Dokumen delivery yang wajib dibawa:
                            <strong>Surat Jalan Supplier</strong> dan
                            <strong>Daily Request (DR)</strong> dari Portal PO Web.
                        </li>

                        <li>
                            Supplier wajib menginput seluruh nomor
                            <strong>Daily Request</strong> ke Portal
                            <strong>Queuing System</strong> pada menu
                            <em>Delivery Supplier</em>
                            sebagai syarat registrasi di pos security IDBM.
                        </li>

                        <li>
                            Supplier harus tiba di pos security sebelum jam kedatangan,
                            melakukan <strong>Registrasi IN</strong> dengan scan Daily Request,
                            dan menunggu panggilan dari sistem.
                            Progress antrian dapat dipantau melalui monitor <strong>Andon</strong>.
                        </li>

                        <li>
                            Setelah dipanggil sistem, driver melakukan
                            <strong>Entry IDBM</strong> dengan scan Daily Request
                            dan menuju loading dock yang telah ditentukan.
                        </li>

                        <li>
                            Proses unloading dilakukan secara manual atau menggunakan forklift
                            sesuai dengan jenis komoditi.
                        </li>

                        <li>
                            Setelah proses unloading selesai:
                            <ul>
                                <li>Supplier wajib membawa kembali sarana kosong</li>
                                <li>PIC Receiving membuat dan mencetak surat jalan sarana</li>
                            </ul>
                        </li>

                        <li>
                            Surat jalan yang telah ditandatangani dan distempel wajib dibawa
                            kembali oleh supplier untuk keperluan penagihan.
                            <ul>
                                <li>Surat jalan asli digunakan jika tidak terdapat revisi</li>
                                <li>
                                    Copy surat jalan digunakan jika terdapat revisi dan supplier
                                    wajib melakukan perbaikan dokumen
                                </li>
                            </ul>
                        </li>

                        <li>
                            Driver melakukan <strong>Registrasi OUT</strong> di pos security
                            dengan scan barcode pada surat jalan sarana kosong.
                        </li>
                    </ol>'
            ],
            [
                'VVARIABLE' => 'verify_non_po_list_unit',
                'VVALUE' => 'Pcs,Box,Kg'
            ],
            [
                'VVARIABLE' => 'base_url_api',
                'VVALUE' => 'http://webdev.idbm.co.id/ifs10dev/api'
            ],
            [
                'VVARIABLE' => 'endpoint_create_manual_si',
                'VVALUE' => '/ifs10/createmanualsi',
            ],
            [
                'VVARIABLE' => 'endpoint_objek_pajak',
                'VVALUE' => '/ifs10/objekpajak',
            ],
            [
                'VVARIABLE' => 'verify_api',
                'VVALUE' => 'true',
            ],
            [
                'VVARIABLE' => 'endpoint_supplier',
                'VVALUE' => '/ifs10/suppliers'
            ],
            [
                'VVARIABLE' => 'legal_cookies',
                'VVALUE' => '<strong>Cookie Policy</strong> <p> Aplikasi web ini menggunakan cookie untuk memastikan fungsi autentikasi, keamanan, dan kelancaran penggunaan sistem. </p> <p> Cookie digunakan untuk menyimpan sesi login, preferensi pengguna, serta mendukung pencatatan aktivitas (audit log). </p> <p> Aplikasi ini <strong>tidak menggunakan cookie untuk tujuan pemasaran atau pelacakan pihak ketiga</strong>. </p> <p> Dengan melanjutkan penggunaan aplikasi ini, Anda menyetujui penggunaan cookie sesuai dengan kebijakan ini. </p>'
            ],
            [
                'VVARIABLE' => 'list_pph_pasal',
                'VVALUE' => 'PPH23,PPh22,PPh4-02'
            ],
            [
                'VVARIABLE' => 'ocr_astemo_value',
                'VVALUE' => 'ASTEMO BEKASI'
            ],
            [
                'VVARIABLE' => 'max_len_view_news',
                'VVALUE' => '150'
            ],
            [
                'VVARIABLE' => 'contact_phone_number',
                'VVALUE' => '08912345678'
            ],
            [
                'VVARIABLE' => 'contact_email',
                'VVALUE' => 'sample@mail.com'
            ],
            [
                'VVARIABLE' => 'toleransi_ppn',
                'VVALUE' => '20'
            ],
            [
                'VVARIABLE' => 'n_day',
                'VVALUE' => '2'
            ],
            [
                'VVARIABLE' => 'toleransi_dpp',
                'VVALUE' => '20'
            ],
            [
                'VVARIABLE' => 'minimum_validasi_materai',
                'VVALUE' => '5000000'
            ],
            [
                'VVARIABLE' => 'ocr_read_document_page',
                'VVALUE' => '1,2,last'
            ]
        ];

        Config::truncate();
        Config::insert($configuration);
    }
}
