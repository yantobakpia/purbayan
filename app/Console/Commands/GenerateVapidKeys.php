<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:keys {--force : Timpa kunci VAPID yang sudah ada di .env}';

    protected $description = 'Buat pasangan kunci VAPID untuk Web Push dan simpan ke .env';

    public function handle(): int
    {
        if (! class_exists(VAPID::class)) {
            $this->error('Library minishlink/web-push belum terpasang. Jalankan: composer require minishlink/web-push');

            return self::FAILURE;
        }

        $keys = VAPID::createVapidKeys();

        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();

        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->warn('File .env tidak ditemukan. Salin dua baris di atas ke file .env Anda.');

            return self::SUCCESS;
        }

        $env = file_get_contents($envPath);

        if (preg_match('/^VAPID_PRIVATE_KEY=.+$/m', $env) && ! $this->option('force')) {
            $this->warn('Kunci VAPID sudah ada di .env. Pakai --force untuk menimpanya.');
            $this->warn('Perhatian: mengganti kunci membuat semua langganan push lama tidak berlaku.');

            return self::SUCCESS;
        }

        $env = $this->setEnvValue($env, 'VAPID_PUBLIC_KEY', $keys['publicKey']);
        $env = $this->setEnvValue($env, 'VAPID_PRIVATE_KEY', $keys['privateKey']);

        file_put_contents($envPath, $env);

        $this->info('Kunci VAPID tersimpan di .env.');
        $this->line('Jangan lupa jalankan: php artisan config:clear');

        return self::SUCCESS;
    }

    protected function setEnvValue(string $env, string $key, string $value): string
    {
        if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $env)) {
            return preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $key.'='.$value, $env);
        }

        return rtrim($env, "\n")."\n".$key.'='.$value."\n";
    }
}
