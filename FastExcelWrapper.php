<?php

namespace App\Utils;

use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FastExcelWrapper
{
    public static function import($importClass, $filePath, $batchSize = 1000)
    {
        $fastExcel = new FastExcel();
        $rows = collect();
        
        // $filePath = storage_path('app/' . $filePath);

        $fastExcel->import($filePath, function ($line) use (&$rows, $batchSize, $importClass) {
            $rows->push($line);

            if ($rows->count() >= $batchSize) {
                self::processBatch($rows, $importClass);
                $rows = collect();
            }
        });

        if ($rows->count() > 0) {
            self::processBatch($rows, $importClass);
        }
    }

    protected static function processBatch(Collection $rows, $importClass)
    {
        $instance = new $importClass;

        // Validate batch
        if (method_exists($instance, 'rules')) {
            $rules = $instance->rules();
            $messages = method_exists($instance, 'messages') ? $instance->messages() : [];
            $attributes = method_exists($instance, 'attributes') ? $instance->attributes() : [];

            $validationData = $rows->map(function ($item, $key) {
                return [$key => $item];
            })->collapse();

            $validator = Validator::make($validationData->toArray(), self::transformRules($rules, $rows->count()), $messages, $attributes);

            if ($validator->fails()) {
                // throw new ValidationException($validator);
                $errors = $validator->errors();
                $detailedErrors = [];

                foreach ($errors->messages() as $key => $messagesArray) {
                    foreach ($messagesArray as $message) {
                        [$rowIndex, $column] = explode('.', $key);
                        $rowNumber = (int) $rowIndex + 1;
                        $value = $rows[$rowIndex][$column] ?? 'NULL';

                        $errorMessage =
                            $message . "\n" 
                            .'for value (' . $value . ') '
                            .'in column ' . $column
                            .', in row ' . $rowNumber . "\n";

                        $detailedErrors[] = $errorMessage;
                    }
                }

                throw ValidationException::withMessages($detailedErrors);
            }
        }

        // Optional hook for adding additional columns
        if (method_exists($instance, 'addColumns')) {
            $rows = $rows->map(function ($item) use ($instance) {
                return $instance->addColumns($item);
            });
        }

        // Handle insert DB
        if (method_exists($instance, 'safelyDBHandle')) {
            DB::beginTransaction();
            try {
                $instance->safelyDBHandle($rows);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
        // handle natively
        elseif (method_exists($instance, 'handle')) {
            $instance->handle($rows);
        }
    }

    protected static function transformRules(array $rules, int $rowCount): array
    {
        $transformed = [];

        for ($i = 0; $i < $rowCount; $i++) {
            foreach ($rules as $key => $rule) {
                $transformed[$i . '.' . $key] = $rule;
            }
        }

        return $transformed;
    }

    public static function export(Collection $collection, string $fileName, $disk = 'local')
    {
        $fastExcel = new FastExcel($collection);
        
        $tempPath = 'exports/' . $fileName;
        $fastExcel->export(storage_path('app/' . $tempPath));

        return Storage::disk($disk)->download($tempPath);
    }
}
