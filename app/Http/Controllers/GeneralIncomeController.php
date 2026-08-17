<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\GeneralIncome;
use App\Models\IncomeCategory;

class GeneralIncomeController extends Controller
{
    public function index(Request $request)
    {
        $selectedYearId = session('selected_academic_year_id');
        $categories = IncomeCategory::orderBy('name')->get();
        
        $query = GeneralIncome::with(['incomeCategory', 'user'])
            ->where('academic_year_id', $selectedYearId)
            ->orderBy('date', 'desc');

        // Filter by Category
        if ($request->filled('income_category_id')) {
            $query->where('income_category_id', $request->income_category_id);
        }

        // Filter by Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Filter by Search (Source / Notes)
        if ($request->filled('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($like, $request) {
                $q->where('source', $like, "%{$request->search}%")
                  ->orWhere('notes', $like, "%{$request->search}%");
            });
        }

        $incomes = $query->paginate(20);

        return view('incomes.index', compact('incomes', 'categories'));
    }

    public function create()
    {
        $categories = IncomeCategory::orderBy('name')->get();
        return view('incomes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'income_category_id' => 'required|exists:income_categories,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'source' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240', // Max 10MB
        ], [
            'income_category_id.required' => 'Pilih kategori pemasukan terlebih dahulu.',
            'amount.min' => 'Nominal pemasukan tidak boleh kurang dari Rp 0.',
            'source.required' => 'Sumber pemasukan (misal: Dinas Pendidikan / H. Ahmad) wajib diisi.',
            'attachment.mimes' => 'Format file bukti transaksi harus berupa JPG, PNG, PDF, atau Word (doc/docx).',
            'attachment.max' => 'Ukuran bukti transaksi maksimal adalah 10MB.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $file->getClientOriginalName());
            
            $defaultDisk = config('filesystems.default');
            $disk = ($defaultDisk === 'local') ? 'public' : $defaultDisk;
            
            $extension = strtolower($file->getClientOriginalExtension());
            $tempPath = $file->getRealPath();
            $fileContents = null;

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $optimizedPath = tempnam(sys_get_temp_dir(), 'income_attach_');
                
                if ($this->compressImage($tempPath, $optimizedPath, 1200, 75)) {
                    $fileContents = file_get_contents($optimizedPath);
                    $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
                }
                
                if (file_exists($optimizedPath)) {
                    unlink($optimizedPath);
                }
            }

            if ($fileContents === null) {
                $fileContents = file_get_contents($tempPath);
            }

            $attachmentPath = 'uploads/incomes/' . $fileName;
            Storage::disk($disk)->put($attachmentPath, $fileContents);
        }

        GeneralIncome::create([
            'academic_year_id' => session('selected_academic_year_id'),
            'income_category_id' => $request->income_category_id,
            'user_id' => auth()->id(),
            'date' => $request->date,
            'amount' => $request->amount,
            'source' => $request->source,
            'notes' => $request->notes,
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()->route('incomes.index')->with('success', 'Transaksi pemasukan lain-lain berhasil dicatat.');
    }

    public function destroy(GeneralIncome $income)
    {
        if ($income->attachment_path) {
            $defaultDisk = config('filesystems.default');
            $disk = ($defaultDisk === 'local') ? 'public' : $defaultDisk;
            
            if (Storage::disk($disk)->exists($income->attachment_path)) {
                Storage::disk($disk)->delete($income->attachment_path);
            }
        }

        $income->delete();
        return redirect()->route('incomes.index')->with('success', 'Catatan pemasukan berhasil dihapus.');
    }

    private function compressImage($sourcePath, $targetPath, $maxWidth, $quality)
    {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return false;
        }

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourcePath);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) (($height / $width) * $maxWidth);
            $scaledImage = imagescale($image, $newWidth, $newHeight);
            imagedestroy($image);
            $image = $scaledImage;
        }

        $result = imagejpeg($image, $targetPath, $quality);
        imagedestroy($image);

        return $result;
    }
}
