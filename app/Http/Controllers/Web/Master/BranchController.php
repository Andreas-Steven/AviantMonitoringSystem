<?php

namespace App\Http\Controllers\Web\Master;

use App\Domains\Master\Models\Branch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreBranchRequest;
use App\Http\Requests\Master\UpdateBranchRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $query = Branch::query()->orderBy('branch_name');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('branch_code', 'ilike', "%{$keyword}%")
                  ->orWhere('branch_name', 'ilike', "%{$keyword}%");
            });
        }

        $branches = $query->paginate(15)->withQueryString();

        return view('master.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $branchTypes = DB::table('branch_types')
            ->where('active', true)
            ->orderBy('branch_type_name')
            ->get();

        return view('master.branches.create', compact('branchTypes'));
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        Branch::create($request->validated());

        return redirect()
            ->route('master.branches.index')
            ->with('success', 'Branch berhasil ditambahkan.');
    }

    public function edit(int $branch): View
    {
        $branch = Branch::findOrFail($branch);

        $branchTypes = DB::table('branch_types')
            ->where('active', true)
            ->orderBy('branch_type_name')
            ->get();

        return view('master.branches.edit', compact('branch', 'branchTypes'));
    }

    public function update(UpdateBranchRequest $request, int $branch): RedirectResponse
    {
        $branchModel = Branch::findOrFail($branch);
        $branchModel->update($request->validated());

        return redirect()
            ->route('master.branches.index')
            ->with('success', 'Branch berhasil diperbarui.');
    }
}
