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


---

## 5️⃣ Testing Locally

### 📦 `composer install`

### 📦 Testing in your Laravel project:
1. Run:
    ```bash
    composer require path/to/laravel-fast-excel-wrapper
    ```
    or use:
    ```json
    "repositories": [
        {
            "type": "path",
            "url": "../path/to/laravel-fast-excel-wrapper"
        }
    ],
    ```
    in your Laravel project `composer.json`, then:
    ```bash
    composer require yourname/laravel-fast-excel-wrapper
    ```

2. Use exactly as pada `FastExcelWrapper::import(...)` dan `export(...)`.

---

## 6️⃣ Upload ke GitHub

1️⃣ Buat repo baru `laravel-fast-excel-wrapper` di GitHub.  
2️⃣ `git init`, `git add .`, `git commit -m "Initial commit"`  
3️⃣ `git remote add origin git@github.com:yourname/laravel-fast-excel-wrapper.git`  
4️⃣ `git push -u origin main`.

---

