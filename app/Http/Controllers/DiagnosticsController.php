<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * A read-only report on the environment the application is actually running in.
 *
 * WHY THIS EXISTS. Render's free plan has no shell and no one-off jobs, so
 * there is no way to ask the running container anything: not which PHP
 * extensions loaded, not whether a directory is writable, not whether the
 * storage bucket is reachable. When something works locally and 500s in
 * production - which is the only interesting class of bug on a deployment like
 * this - the difference is invisible.
 *
 * The photo upload endpoint returning 500 on Render while working perfectly on
 * every developer machine is exactly that shape, and it is what this was built
 * to answer.
 *
 * WHAT IT MUST NEVER DO: print a secret. Every credential here is reported as
 * "configured" or "missing" and never by value or length - a page that leaks
 * the Supabase key is a worse problem than the one it was built to diagnose.
 * The same goes for the full phpinfo(), which is deliberately not used.
 *
 * OWNER ONLY, re-checked here rather than trusting the route group, because
 * this sits outside the Policy layer like the design gallery does.
 */
final class DiagnosticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isOwner() ?? false, 403);

        return view('diagnostics', [
            'php' => $this->php(),
            'extensions' => $this->extensions(),
            'uploads' => $this->uploads(),
            'paths' => $this->paths(),
            'disks' => $this->disks(),
            'services' => $this->services(),
        ]);
    }

    /** @return array<string, string> */
    private function php(): array
    {
        return [
            'PHP version' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Environment' => (string) app()->environment(),
            'Debug mode' => config('app.debug') ? 'ON' : 'off',
            'Memory limit' => (string) ini_get('memory_limit'),
        ];
    }

    /**
     * The extensions this application actually depends on.
     *
     * `fileinfo` is the one to look at first for an upload failure: Livewire
     * calls getMimeType() on every temporary upload, and with no MIME guesser
     * available Symfony throws rather than returning null - which surfaces as
     * a bare 500 from an endpoint that never reaches application code.
     *
     * @return array<string, bool>
     */
    private function extensions(): array
    {
        $needed = ['fileinfo', 'gd', 'exif', 'pdo_pgsql', 'zip', 'intl', 'bcmath', 'openssl', 'mbstring'];

        return array_combine($needed, array_map('extension_loaded', $needed));
    }

    /** @return array<string, string> */
    private function uploads(): array
    {
        return [
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size' => (string) ini_get('post_max_size'),
            'max_file_uploads' => (string) ini_get('max_file_uploads'),
            'file_uploads enabled' => ini_get('file_uploads') ? 'yes' : 'NO',
            'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir().' (default)',
            'tmp dir writable' => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? 'yes' : 'NO',
            'Livewire temp disk' => (string) (config('livewire.temporary_file_upload.disk') ?: config('filesystems.default').' (default)'),
            'app photo disk' => (string) config('gfms.photo_disk'),
        ];
    }

    /**
     * Directories the framework writes to at runtime.
     *
     * A container filesystem that looks fine at build time can still be
     * unwritable to the php-fpm user, and every symptom of that is a 500 with
     * nothing useful in it.
     *
     * @return array<string, array{exists: bool, writable: bool}>
     */
    private function paths(): array
    {
        $paths = [
            'storage/app/private' => storage_path('app/private'),
            'storage/app/private/livewire-tmp' => storage_path('app/private/livewire-tmp'),
            'storage/app/public' => storage_path('app/public'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),

            /*
             * NOT a Laravel path, and the most important row on this page.
             *
             * nginx spools any POST body larger than client_body_buffer_size
             * here before handing the request to PHP, so every photo upload
             * passes through it. Alpine's nginx package owns it as `nginx`
             * while the workers run as www-data, which broke every upload on
             * this deployment with a 500 that never reached application code.
             *
             * php-fpm runs as the SAME www-data user, so what PHP can write
             * here is exactly what nginx can write here.
             */
            '/var/lib/nginx/tmp/client_body (uploads)' => '/var/lib/nginx/tmp/client_body',
        ];

        return array_map(
            static fn (string $path): array => [
                'exists' => is_dir($path),
                'writable' => is_dir($path) && is_writable($path),
            ],
            $paths
        );
    }

    /**
     * Whether each configured disk can actually be reached.
     *
     * The check is a cheap existence probe for a path that will not be there.
     * It proves the credentials, endpoint and network path work without
     * writing anything or listing anyone's files.
     *
     * @return array<string, string>
     */
    private function disks(): array
    {
        $out = [];

        foreach (array_keys(config('filesystems.disks', [])) as $name) {
            try {
                Storage::disk($name)->exists('.diagnostics-probe-'.bin2hex(random_bytes(4)));
                $out[$name] = 'reachable';
            } catch (Throwable $e) {
                // The message can name a host or a bucket, which is fine, but
                // never a key - AWS SDK errors do not echo credentials.
                $out[$name] = 'FAILED: '.mb_substr($e->getMessage(), 0, 120);
            }
        }

        return $out;
    }

    /**
     * Configuration that is easy to get wrong and silent when it is.
     *
     * Credentials are reported as configured/missing ONLY. Never the value,
     * and never the length.
     *
     * @return array<string, string>
     */
    private function services(): array
    {
        $configured = static fn (mixed $value): string => filled($value) ? 'configured' : 'MISSING';

        return [
            'Database driver' => (string) config('database.default'),
            'Session driver' => (string) config('session.driver'),
            'Cache store' => (string) config('cache.default'),
            'Queue' => (string) config('queue.default'),
            // `log` here means password reset writes the link to stderr and
            // the user never receives anything.
            'Mail mailer' => (string) config('mail.default'),
            'Mail host' => $configured(config('mail.mailers.smtp.host')),
            'Mail from address' => $configured(config('mail.from.address')),
            'Supabase endpoint' => $configured(config('filesystems.disks.supabase.endpoint')),
            'Supabase key' => $configured(config('filesystems.disks.supabase.key')),
            'Supabase secret' => $configured(config('filesystems.disks.supabase.secret')),
            'Supabase bucket' => (string) config('filesystems.disks.supabase.bucket'),
        ];
    }
}
