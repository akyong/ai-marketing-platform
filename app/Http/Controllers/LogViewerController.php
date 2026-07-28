<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class LogViewerController extends Controller
{
    /**
     * Display System Logs (laravel.log)
     */
    public function index(Request $request): Response
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];
        $fileSize = 0;

        if (File::exists($logPath)) {
            $fileSize = File::size($logPath);
            $content = File::get($logPath);

            // Split log entries by Laravel log timestamp pattern: [2026-07-28 10:00:00]
            $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+([\w\.]+)\.([A-Z]+):\s+(.*)$/m';

            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

            // Take the last 150 log entries in reverse order (newest first)
            $matches = array_reverse(array_slice($matches, -150));

            foreach ($matches as $index => $match) {
                $logs[] = [
                    'id' => $index + 1,
                    'timestamp' => $match[1] ?? '',
                    'environment' => $match[2] ?? '',
                    'level' => strtoupper($match[3] ?? 'INFO'),
                    'message' => mb_substr($match[4] ?? '', 0, 1000),
                    'full' => $match[0] ?? '',
                ];
            }
        }

        return Inertia::render('Logs/Index', [
            'logs' => $logs,
            'fileSizeFormatted' => $this->formatBytes($fileSize),
            'logExists' => File::exists($logPath),
        ]);
    }

    /**
     * Clear / Truncate laravel.log file
     */
    public function clear(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        return redirect()->back()->with('success', 'File laravel.log berhasil dibersihkan!');
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
