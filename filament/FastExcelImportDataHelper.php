<?php

namespace App\Utils;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Rap2hpoutre\FastExcel\FastExcel;

class FastExcelImportDataHelper
{
    public static function importFormByClassTemplate(string $importClass, ?array $data = null): array
    {
        if (!class_exists($importClass)) {
            throw new \InvalidArgumentException("Import class {$importClass} tidak ditemukan.");
        }

        $templateUrl = route('export-template', [
            'import' => $importClass,
            'data' => json_encode($data ?? []),
        ]);

        $rulesMessage = method_exists($importClass, 'getImportRulesDescription')
            ? $importClass::getImportRulesDescription()
            : '_Tidak ada rules khusus untuk import._';

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

            Textarea::make('rules_description')
                ->label('Petunjuk Validasi Data')
                ->default($rulesMessage)
                ->disabled()
                ->rows(15)
                ->columnSpanFull(),

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
            Notification::make()
                ->title("Validasi Gagal pada Import, periksa data Anda.". $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            // Jika ingin menampilkan error detail:
            // throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Terjadi kesalahan saat import: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
