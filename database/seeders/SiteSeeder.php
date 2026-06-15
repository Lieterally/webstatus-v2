<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = [
            // Website Akademik
            ['name' => 'Prodi Teknik Sipil', 'category_id' => 1, 'base_url' => 'https://ce.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Kimia', 'category_id' => 1, 'base_url' => 'https://che.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Elektro', 'category_id' => 1, 'base_url' => 'https://ee.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Lingkungan', 'category_id' => 1, 'base_url' => 'https://enviro.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Industri', 'category_id' => 1, 'base_url' => 'https://ie.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Informatika', 'category_id' => 1, 'base_url' => 'https://if.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Sistem Informasi', 'category_id' => 1, 'base_url' => 'https://is.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Matematika', 'category_id' => 1, 'base_url' => 'https://math.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Mesin', 'category_id' => 1, 'base_url' => 'https://me.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Material Metalurgi', 'category_id' => 1, 'base_url' => 'https://mme.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Perkapalan', 'category_id' => 1, 'base_url' => 'https://na.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Kelautan', 'category_id' => 1, 'base_url' => 'https://oe.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Fisika', 'category_id' => 1, 'base_url' => 'https://phy.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Perencanaan Wilayah dan Kota', 'category_id' => 1, 'base_url' => 'https://urp.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Arsitektur', 'category_id' => 1, 'base_url' => 'https://ars.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Statistika', 'category_id' => 1, 'base_url' => 'https://stat.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Ilmu Aktuaria', 'category_id' => 1, 'base_url' => 'https://actsci.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Teknik Pangan', 'category_id' => 1, 'base_url' => 'https://foodtech.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Rekayasa Keselamatan', 'category_id' => 1, 'base_url' => 'https://safetyeng.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Bisnis Digital', 'category_id' => 1, 'base_url' => 'https://bisnisdigital.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Prodi Desain Komunikasi Visual', 'category_id' => 1, 'base_url' => 'https://dkv.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],

            ['name' => 'Repository', 'category_id' => 1, 'base_url' => 'https://repository.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Perpustakaan', 'category_id' => 1, 'base_url' => 'https://perpustakaan.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Learning Management Systems', 'category_id' => 1, 'base_url' => 'https://kuliah.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Dokumen Mutu', 'category_id' => 1, 'base_url' => 'https://dokumen-mutu.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Penerimaan Mahasiswa Baru', 'category_id' => 1, 'base_url' => 'https://pmb.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],

            // Website Non Akademik
            ['name' => 'SIM Manajemen', 'category_id' => 2, 'base_url' => 'https://simmanajemen.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SIPEKA', 'category_id' => 2, 'base_url' => 'https://sipeka.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SPEAK', 'category_id' => 2, 'base_url' => 'https://speak.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SUMMIT', 'category_id' => 2, 'base_url' => 'https://summit.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SIMPAS', 'category_id' => 2, 'base_url' => 'https://simpas.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'JAMU', 'category_id' => 2, 'base_url' => 'https://jamu.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Short Link ITK', 'category_id' => 2, 'base_url' => 'https://s.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SIRAMA', 'category_id' => 2, 'base_url' => 'https://sirama.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SIM Banding', 'category_id' => 2, 'base_url' => 'https://simbanding.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Gerbang', 'category_id' => 2, 'base_url' => 'https://gerbang.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Host to Host BNI', 'category_id' => 2, 'base_url' => 'https://h2hbni.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Host to Host BRI', 'category_id' => 2, 'base_url' => 'https://h2hbri.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Host to Host Mandiri', 'category_id' => 2, 'base_url' => 'https://h2hmandiri.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Tracer Study', 'category_id' => 2, 'base_url' => 'https://tracer.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Feeder Gerbang', 'category_id' => 2, 'base_url' => 'https://feeder-gerbang.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'CCTV', 'category_id' => 2, 'base_url' => 'http://nvr.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SIAKAD', 'category_id' => 2, 'base_url' => 'http://siakad.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SIKAP', 'category_id' => 2, 'base_url' => 'http://sikap.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Sepakat', 'category_id' => 2, 'base_url' => 'http://sepakat.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SIMKUR', 'category_id' => 2, 'base_url' => 'http://kurikulum.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Lab Terpadu', 'category_id' => 2, 'base_url' => 'http://labterpadu.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],

            // Others
            ['name' => 'PPID', 'category_id' => 3, 'base_url' => 'https://ppid.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'DPM ITK', 'category_id' => 3, 'base_url' => 'https://dpm.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Open House ITK', 'category_id' => 3, 'base_url' => 'https://openhouse.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Kerjasama ITK', 'category_id' => 3, 'base_url' => 'https://kerjasama.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Pilrek ITK', 'category_id' => 3, 'base_url' => 'https://pilrek.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Unit Layanan Terpadu', 'category_id' => 3, 'base_url' => 'https://ult.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Web Profil ITK', 'category_id' => 3, 'base_url' => 'https://itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SPI', 'category_id' => 3, 'base_url' => 'https://spi.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'UPA TIK', 'category_id' => 3, 'base_url' => 'https://ict.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'UPA Bahasa', 'category_id' => 3, 'base_url' => 'https://lch.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'LPPM', 'category_id' => 3, 'base_url' => 'https://lppm.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Dev SCA', 'category_id' => 3, 'base_url' => 'https://dev-sca.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Journal', 'category_id' => 3, 'base_url' => 'https://journal.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'IAET', 'category_id' => 3, 'base_url' => 'https://iaet.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'SNBP', 'category_id' => 3, 'base_url' => 'https://snbp.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
            ['name' => 'Inkubator Bisnis', 'category_id' => 3, 'base_url' => 'https://ibt.itk.ac.id', 'description' => null, 'responsible_person_id' => 1],
        ];

        foreach ($sites as $site) {
            Site::updateOrCreate(
                ['base_url' => $site['base_url']],
                $site
            );
        }
    }
}
