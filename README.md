# 🦉 Laravel FastExcel Wrapper

A lightweight **FastExcel import/export wrapper** for Laravel, supporting **batch validation, optional attribute/messages, and clean import pipeline**.

---

## 🚀 Features

✅ Import Excel/CSV with batch validation  
✅ Uses [rap2hpoutre/fast-excel](https://github.com/rap2hpoutre/fast-excel) as the engine  
✅ Optional `rules()`, `messages()`, `attributes()` for validation  
✅ Optional `addColumns()` for dynamic column injection before insert  
✅ Optional `handle()` to customize batch insert  
✅ Simple export pipeline with FastExcel  
✅ Framework-agnostic, but optimized for Laravel

---

## 📦 Installation
1. make sure you already install the [rap2hpoutre/fast-excel](https://github.com/rap2hpoutre/fast-excel)
2. clone this repo
```bash
# composer require yourname/laravel-fast-excel-wrapper
git clone https://github.com/kikiraihan/laravel-fast-excel-wrapper fast-excel-wrapper
```
3. just use the wrapper class, copy to your project

🛠️ Usage
1️⃣ Create Import Class
```php
use Illuminate\Support\Collection;
use App\Models\YourModel;

class YourModelImport
{
    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }

    public function handle(Collection $rows)
    {
        foreach ($rows as $row) {
            YourModel::create($row);
        }
    }

    public function addColumns($row)
    {
        $row['created_at'] = now();
        $row['updated_at'] = now();
        return $row;
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama wajib diisi.',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'Nama',
        ];
    }
}
```
2️⃣ Import in Controller / Livewire / Filament Action
```php
use FastExcelWrapper\FastExcelWrapper;

FastExcelWrapper::import(new YourModelImport, $pathToFile, $batchSize = 1000);
```
3️⃣ Export
```php
use FastExcelWrapper\FastExcelWrapper;

return FastExcelWrapper::export($collection, 'yourfile.xlsx');
```

🪄 Why?
Uses FastExcel’s speed while providing a clean, testable pipeline for imports.

Avoids heavy memory usage during large imports.

Respects Laravel’s validation approach with Validator for batch validation.

No Excel “row by row” boilerplate in your controllers.

## 📥 CSV Import with Livewire (Filament)

You can quickly add a CSV import form to your Filament Livewire resource using `FastExcelImportDataHelper`:

```php
use Filament\Forms\Components\Actions\Action;
use App\Utils\FastExcelImportDataHelper;
use App\Livewire\Dealertrx\BranchDealerTransactionResource\BranchDealerTransactionImport;

// Inside your formActions or tableActions:

Action::make('csvupload')
    ->label('Import')
    ->icon('eos-csv-file')
    ->form(
        FastExcelImportDataHelper::importFormByClassTemplate(BranchDealerTransactionImport::class)
    )
    ->action(fn ($data) => FastExcelImportDataHelper::handleImport($data, new BranchDealerTransactionImport)),
```

### What This Does

✅ Adds a **CSV/Excel upload modal** with:

* Downloadable import template.
* Validation rules guide inside the modal.
* CSV splitter and VLOOKUP online tool shortcuts.
* File upload input with size limit.

✅ On submission, it:

* Uses `FastExcelWrapper` for **fast chunked validation and import**.
* Rolls back if any row fails validation.
* Sends success/error notifications in Filament automatically.

---

### Requirements

* Ensure your import class (e.g., `BranchDealerTransactionImport`) implements:

  * `rules()`
  * `handle(Collection $rows)`
  * `addColumns($row)`
  * `examples()` for template generation.
  * `getImportRules()` and `getImportRulesDescription()` for dynamic validation rule hints.

---

### Example Import Class

```php
namespace App\Livewire\Dealertrx\BranchDealerTransactionResource;

use Illuminate\Support\Collection;

class BranchDealerTransactionImport
{
    public function rules(): array
    {
        return [
            'branch_dealer_id' => ['required', 'string'],
            'dealer_id' => ['required', 'string'],
            'type_merk_id' => ['required', 'string'],
            'qty_fakpol' => ['required', 'integer'],
            'qty_spk' => ['required', 'integer'],
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer'],
        ];
    }

    public function handle(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Insert into your table
        }
    }

    public function addColumns($row): array
    {
        $row['created_at'] = now();
        $row['updated_at'] = now();
        return $row;
    }

    public function examples(): array
    {
        return [
            [
                'branch_dealer_id' => 'BRCH001',
                'dealer_id' => 'DLR001',
                'type_merk_id' => 'TMK001',
                'qty_fakpol' => 10,
                'qty_spk' => 12,
                'year' => 2025,
                'month' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
    }

    public static function getImportRules(): array
    {
        return (new self())->rules();
    }

    public static function getImportRulesDescription(): string
    {
        return "branch_dealer_id: required, string\n"
            . "dealer_id: required, string\n"
            . "...";
    }
}
```

---

### Notes

✅ Uses **`Rap2hpoutre\FastExcel`** under the hood for speed.
✅ **Validation messages** will appear in Filament notifications if a row fails.
✅ Adjust `handle()` in your import class to match your table structure.
✅ The import will **fail fast and rollback if any row is invalid**, ensuring data integrity.


---
🛡️ License
MIT
