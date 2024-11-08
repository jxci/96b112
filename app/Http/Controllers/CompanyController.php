<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Http\Requests\StoreCompanyRequest;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::paginate(10);
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = new Company();
        $company->name = $request->name;
        $company->email = $request->email;

        if ($request->hasFile('logo')) {
            $company->logo = $request->file('logo')->store('logos', 'public');
        }

        $company->website = $request->website;
        $company->save();

        return redirect()->route('companies.index')->with('success', 'Created');
    }

    public function show(Company $company)
    {
        return view('companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->name = $request->name;
        $company->email = $request->email;

        if ($request->hasFile('logo')) {
            // Удаление старого логотипа, если он существует
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $company->logo = $request->file('logo')->store('logos', 'public');
        }

        $company->website = $request->website;
        $company->save();

        return redirect()->route('companies.index')->with('success', 'Updated');
    }

    public function destroy(Company $company)
    {
        // Удаление логотипа, если он существует
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Deleted');
    }
}
