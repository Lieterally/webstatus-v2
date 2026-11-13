<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodiPaths = [
            '/',
            '/berita',
            '/profile',
            '/profile/sejarah',
            '/profile/visimisi',
            '/akademik/kurikulum',
            '/akademik/silabus',
            '/kemahasiswaan/ormawa',
            '/penelitian/grup_penelitian',
            '/penelitian/kontak',
            '/login',
        ];

        // Additional paths for non-prodi sites, keyed by base_url
        $extraPaths = [
            'http://siakad.itk.ac.id' => ['/home.php'],
        ];

        $sites = \App\Models\Site::all();

        foreach ($sites as $site) {

            // Program Studi sites
            if (str_starts_with($site->name, 'Prodi ')) {

                foreach ($prodiPaths as $path) {
                    \App\Models\Page::updateOrCreate(
                        [
                            'site_id' => $site->id,
                            'path' => $path,
                        ],
                        []
                    );
                }

                continue;
            }

            // Non-prodi sites
            foreach (['/', ...($extraPaths[$site->base_url] ?? [])] as $path) {
                \App\Models\Page::updateOrCreate(
                    [
                        'site_id' => $site->id,
                        'path' => $path,
                    ],
                    []
                );
            }
        }
    }
}
