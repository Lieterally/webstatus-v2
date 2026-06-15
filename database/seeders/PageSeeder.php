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
            \App\Models\Page::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'path' => '/',
                ],
                []
            );
        }
    }
}
