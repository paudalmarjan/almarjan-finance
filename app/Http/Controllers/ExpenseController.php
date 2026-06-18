<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\ExpenseCategory;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        
        $query = Expense::with(['expenseCategory', 'user'])->orderBy('date', 'desc');

        // Filter by Category
        if ($request->filled('expense_category_id')) {
            $query->where('expense_category_id', $request->expense_category_id);
        }

        // Filter by Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Filter by Search (Notes)
        if ($request->filled('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('notes', $like, "%{$request->search}%");
        }

        $expenses = $query->paginate(20);

        return view('expenses.index', compact('expenses', 'categories'));
    }

    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'attachment' => 'required|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240', // Max 10MB
        ], [
            'expense_category_id.required' => 'Pilih kategori pengeluaran terlebih dahulu.',
            'amount.min' => 'Nominal pengeluaran tidak boleh kurang dari Rp 0.',
            'attachment.required' => 'Bukti transaksi (foto/PDF/Word) wajib diunggah.',
            'attachment.mimes' => 'Format file bukti transaksi harus berupa JPG, PNG, PDF, atau Word (doc/docx).',
            'attachment.max' => 'Ukuran bukti transaksi maksimal adalah 10MB.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $file->getClientOriginalName());
            
            // Tentukan disk yang digunakan: jika default local, arahkan ke public. Jika s3, gunakan s3.
            $defaultDisk = config('filesystems.default');
            $disk = ($defaultDisk === 'local') ? 'public' : $defaultDisk;
            
            $extension = strtolower($file->getClientOriginalExtension());
            $tempPath = $file->getRealPath();
            $fileContents = null;

            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                // Buat file temporary untuk menyimpan hasil kompresi
                $optimizedPath = tempnam(sys_get_temp_dir(), 'expense_attach_');
                
                // Lakukan kompresi dengan lebar maksimal 1200px dan kualitas 75%
                if ($this->compressImage($tempPath, $optimizedPath, 1200, 75)) {
                    $fileContents = file_get_contents($optimizedPath);
                    // Karena GD mengubah semua format ke JPEG, setel ekstensi file target menjadi .jpg
                    $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
                }
                
                if (file_exists($optimizedPath)) {
                    unlink($optimizedPath);
                }
            }

            // Jika kompresi gagal/tidak berjalan (misal file PDF atau Word), baca file aslinya
            if ($fileContents === null) {
                $fileContents = file_get_contents($tempPath);
            }

            $attachmentPath = 'uploads/expenses/' . $fileName;
            Storage::disk($disk)->put($attachmentPath, $fileContents);
        }

        Expense::create([
            'academic_year_id' => session('selected_academic_year_id'),
            'expense_category_id' => $request->expense_category_id,
            'user_id' => auth()->id(),
            'date' => $request->date,
            'amount' => $request->amount,
            'notes' => $request->notes,
            'attachment_path' => $attachmentPath,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Transaksi pengeluaran berhasil dicatat.');
    }

    public function destroy(Expense $expense)
    {
        // Delete attachment from storage if exists
        if ($expense->attachment_path) {
            $defaultDisk = config('filesystems.default');
            $disk = ($defaultDisk === 'local') ? 'public' : $defaultDisk;
            
            if (Storage::disk($disk)->exists($expense->attachment_path)) {
                Storage::disk($disk)->delete($expense->attachment_path);
            }
        }

        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }

    /**
     * Kompresi dan resize gambar menggunakan pustaka PHP GD bawaan.
     */
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

        // Resize jika lebar gambar melebihi batas maksimal dengan mempertahankan aspek rasio
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) (($height / $width) * $maxWidth);
            $scaledImage = imagescale($image, $newWidth, $newHeight);
            imagedestroy($image);
            $image = $scaledImage;
        }

        // Simpan sebagai JPEG dengan kualitas kompresi yang ditentukan
        $result = imagejpeg($image, $targetPath, $quality);
        imagedestroy($image);

        return $result;
    }
}
