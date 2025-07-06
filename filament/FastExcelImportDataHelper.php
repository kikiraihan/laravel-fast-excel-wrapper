<?php

namespace App\Utils;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Rap2hpoutre\FastExcel\FastExcel;

class FastExcelImportDataHelper
{
    // method tambahan untuk form detail
    public static function getImportRulesDescription($rules): string
    {
        // $rules = self::getImportRules();
        $messages = [];

        foreach ($rules as $field => $ruleSet) {
            $message = "**{$field}**: ";

            $ruleSet = is_array($ruleSet) ? $ruleSet : [$ruleSet];
            $translated = [];

            foreach ($ruleSet as $rule) {
                // Ubah string menjadi array kalau perlu
                if (is_string($rule)) {
                    $parts = explode(':', $rule);
                    $ruleName = strtolower($parts[0]);
                    $param = $parts[1] ?? null;

                    switch ($ruleName) {
                        case 'required':
                            $translated[] = 'wajib diisi';
                            break;
                        case 'nullable':
                            $translated[] = 'boleh kosong';
                            break;
                        case 'string':
                            $translated[] = 'harus berupa teks';
                            break;
                        case 'integer':
                            $translated[] = 'harus berupa angka bulat';
                            break;
                        case 'numeric':
                            $translated[] = 'harus berupa angka';
                            break;
                        case 'date':
                            $translated[] = 'harus berupa tanggal';
                            break;
                        case 'date_format':
                            $translated[] = "format tanggal harus '{$param}'";
                            break;
                        case 'max':
                            $translated[] = "maksimal {$param} karakter";
                            break;
                        case 'min':
                            $translated[] = "minimal {$param}";
                            break;
                        case 'digits':
                            $translated[] = "{$param} digit";
                            break;
                        case 'in':
                            $inOptions = explode(',', $param);
                            $shown = implode(', ', array_slice($inOptions, 0, 5));
                            if (count($inOptions) > 5) {
                                $shown .= ', dll.';
                            }
                            $translated[] = "harus salah satu dari: {$shown}";
                            break;
                        case 'boolean':
                            $translated[] = 'harus bernilai 1 (ya) atau 0 (tidak)';
                            break;
                        case 'regex':
                            $translated[] = 'harus sesuai pola tertentu';
                            break;
                        default:
                            // $translated[] = "{$ruleName}" . ($param ? ": {$param}" : '');
                            $translated[] = "{$ruleName}" . ($param ? ": {$param}" : '');
                            break;
                    }
                } elseif (is_object($rule)) {
                    $classname = get_class($rule);
                    switch ($classname) {
                        case 'Illuminate\Validation\Rules\In':
                            $translated[] = 'Input terbatas pada nilai tertentu';
                            break;
                        default:
                            # code...
                            $translated[] = 'other rules';
                            break;
                    }
                }
            }

            $messages[] = "{$message}" . implode(', ', $translated);
        }

        return implode("<br/>", $messages);
    }

    public static function importFormByClassTemplate(string $importClass, ?array $data = null): array
    {
        if (!class_exists($importClass)) {
            throw new \InvalidArgumentException("Import class {$importClass} tidak ditemukan.");
        }

        $templateUrl = route('export-template', [
            'import' => $importClass,
            'data' => json_encode($data ?? []),
        ]);

        $rules = method_exists($importClass, 'getImportRules')
            ? $importClass::getImportRules()
            : [];

        $rulesMessage = $rules ? self::getImportRulesDescription($rules):'_Tidak ada rules khusus untuk import._';

        return [
            Actions::make([
                Action::make('Template')
                    ->url($templateUrl)
                    ->color('success')
                    ->icon('eos-download-o')
                    ->button(),

                Action::make('csv splitter')
                    ->url('https://extendsclass.com/csv-splitter.html')
                    ->color('success')
                    ->icon('eos-link-o')
                    ->label('Pecah CSV Online')
                    ->button()
                    ->outlined(),

                Action::make('vlookupOnline')
                    ->url('http://www.vlookuponline.com/vlookup-online-tool-without-excel')
                    ->color('success')
                    ->icon('eos-link-o')
                    ->label('VLOOKUP Online')
                    ->button()
                    ->outlined(),
            ]),

            MarkdownEditor::make('rules_description')
                ->label('Petunjuk Validasi Data')
                ->default($rulesMessage)
                ->disabled()
                ->columnSpanFull(),
            // Textarea::make('rules_description')
            //     ->label('Petunjuk Validasi Data')
            //     ->default($rulesMessage)
            //     ->disabled()
            //     // ->rows(15)
            //     ->columnSpanFull(),

            FileUpload::make('uploadcsv')
                ->label('Excel atau CSV File')
                ->storeFiles(false)
                ->maxSize(50024)
                ->required(),
        ];
    }

    /**
     * Menangani proses import data dengan validasi dan rollback jika 1 baris error.
     */
    public static function handleImport(array $data, object $importClass): void
    {
        try {
            // Simpan file sementara
            // $path = $file->store('imports');
            // $path = $data['file']; // sudah path relatif ke storage/app
            // $filePath = storage_path('app/public/' . $path);
            $filePath = $data['uploadcsv']->getRealPath();

            // Jalankan import dengan batch validation cepat
            FastExcelWrapper::import(new $importClass, $filePath, 1000);

            Notification::make()
                ->title("Berhasil import data")
                ->success()
                ->send();
        } catch (ValidationException $e) {

            // Tampil semua pesan error
            $errors = collect($e->errors())->flatten();
            $display = $errors->take(5)->implode("<br/><br/>");
            $more = $errors->count() > 5 ? "<br/><br/>... dan " . ($errors->count() - 5) . " error lainnya." : '';

            Notification::make()
                ->title("Validasi Gagal pada Import")
                ->body($display . $more)
                ->danger()
                ->persistent()
                ->send();

            // menampilkan ringkasan sperti "(and 1 more)"
            // Notification::make()
            //     ->title("Validasi Gagal pada Import: ")
            //     ->body($e->getMessage())
            //     ->danger()
            //     ->persistent()
            //     ->send();
            // Jika ingin menampilkan error detail:
            // throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan saat import: ')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
