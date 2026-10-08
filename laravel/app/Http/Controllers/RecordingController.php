<?php

namespace App\Http\Controllers;

use App\Models\Recording;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecordingController extends Controller
{
    /**
     * Stream الفيديو في المتصفح
     */
    public function stream($id): StreamedResponse
    {
        $recording = Recording::findOrFail($id);

        abort_unless($recording->status === 'ready', 404, 'التسجيل غير جاهز');
        abort_unless(Storage::exists($recording->storage_path), 404, 'ملف التسجيل غير موجود');

        return Storage::response(
            $recording->storage_path,
            $recording->filename,
            ['Content-Type' => 'video/' . $recording->format]
        );
    }

    /**
     * تحميل الفيديو
     */
    public function download($id)
    {
        $recording = Recording::findOrFail($id);

        abort_unless($recording->status === 'ready', 404, 'التسجيل غير جاهز');
        abort_unless(Storage::exists($recording->storage_path), 404, 'ملف التسجيل غير موجود');

        return Storage::download(
            $recording->storage_path,
            $recording->filename
        );
    }
}