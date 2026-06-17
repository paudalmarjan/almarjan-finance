<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            $query->where('notes', 'like', "%{$request->search}%");
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
            'attachment' => 'required|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:5120', // Max 5MB
        ], [
            'expense_category_id.required' => 'Pilih kategori pengeluaran terlebih dahulu.',
            'amount.min' => 'Nominal pengeluaran tidak boleh kurang dari Rp 0.',
            'attachment.required' => 'Bukti transaksi (foto/PDF/Word) wajib diunggah.',
            'attachment.mimes' => 'Format file bukti transaksi harus berupa JPG, PNG, PDF, atau Word (doc/docx).',
            'attachment.max' => 'Ukuran bukti transaksi maksimal adalah 5MB.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            
            // Store directly in public disk for easy web accessibility and LPJ Zipping
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '', $file->getClientOriginalName());
            $file->move(public_path('uploads/expenses'), $fileName);
            $attachmentPath = 'uploads/expenses/' . $fileName;
        }

        Expense::create([
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
        // Delete attachment from public folder if exists
        if ($expense->attachment_path && file_exists(public_path($expense->attachment_path))) {
            unlink(public_path($expense->attachment_path));
        }

        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }
}
