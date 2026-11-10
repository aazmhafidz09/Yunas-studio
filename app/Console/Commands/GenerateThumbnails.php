<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class GenerateThumbnails extends Command
{
    protected $signature = 'photos:generate-thumbnails';
    protected $description = 'Scan NAS folders and generate thumbnails for all categories';

    public function handle()
    {
        $disk = Storage::disk('public'); 
        $rootFolder = 'photos';
        
        // Daftar folder yang ingin dibuatkan thumbnail-nya
        $targetFolders = ['RAW', 'Result', 'Pilih Edit', 'Pilih Cetak'];

        $this->info('Starting scanning NAS storage...');

        // Ambil semua folder transaksi
        $directories = $disk->directories($rootFolder);

        foreach ($directories as $dir) {
            
            // Loop untuk setiap kategori (RAW, Result, dll)
            foreach ($targetFolders as $subFolder) {
                
                $sourcePath = $dir . '/' . $subFolder;
                $thumbPath = $dir . '/Thumbnails/' . $subFolder; // Struktur Nested

                // 1. Cek apakah folder sumber (misal: Result) ada isinya?
                if (!$disk->exists($sourcePath)) continue;

                // 2. Buat folder Thumbnails spesifik jika belum ada
                if (!$disk->exists($thumbPath)) {
                    $disk->makeDirectory($thumbPath);
                }

                $files = $disk->files($sourcePath);

                foreach ($files as $file) {
                    $filename = basename($file);
                    
                    // Filter hanya gambar
                    if (!in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])) {
                        continue;
                    }

                    $thumbnailFilePath = $thumbPath . '/' . $filename;

                    // Skip jika thumbnail sudah ada
                    if ($disk->exists($thumbnailFilePath)) {
                        continue; 
                    }

                    // $this->comment("Processing: $subFolder/$filename"); // Uncomment untuk debug

                    try {
                        // Ambil path fisik file asli
                        $realPath = $disk->path($file);
                        
                        // Resize
                        $img = Image::make($realPath)
                            ->resize(400, null, function ($constraint) {
                                $constraint->aspectRatio();
                                $constraint->upsize();
                            })
                            ->encode(null, 60);

                        // Simpan ke folder thumbnail yang sesuai
                        $disk->put($thumbnailFilePath, (string) $img);
                        
                    } catch (\Exception $e) {
                        // Silent error agar tidak spamming jika file corrupt/symlink rusak
                        // $this->error("Error: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info('All Done!');
    }
}