<?php

namespace App\Http\Requests\PJ;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMonthlyRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'base_revision' => ['required', 'string', 'regex:/^sha256:[a-f0-9]{64}$/'],
            'entries' => ['required', 'array'],
            'entries.*' => ['required', 'array'],
            'entries.*.date' => ['required', 'string', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'entries.*.operation' => ['required', 'string', 'in:unchanged,upsert'],
            'entries.*.shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routeYear = (int) $this->route('year');
            $routeMonth = (int) $this->route('month');
            $routeEmployee = (int) optional($this->route('user'))->getKey();

            if ((int) $this->input('employee_id') !== $routeEmployee) {
                $validator->errors()->add('employee_id', 'Pegawai tidak sesuai dengan target route.');
            }

            if ((int) $this->input('year') !== $routeYear) {
                $validator->errors()->add('year', 'Tahun tidak sesuai dengan target route.');
            }

            if ((int) $this->input('month') !== $routeMonth) {
                $validator->errors()->add('month', 'Bulan tidak sesuai dengan target route.');
            }

            if ($routeYear < 2000 || $routeYear > 2100 || $routeMonth < 1 || $routeMonth > 12) {
                return;
            }

            $periodStart = Carbon::create($routeYear, $routeMonth, 1)->startOfDay();
            $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();
            $entries = $this->input('entries', []);
            $dates = [];

            foreach ($entries as $index => $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                foreach (['start_time', 'end_time', 'is_overnight', 'work_hours', 'duration'] as $forbidden) {
                    if (array_key_exists($forbidden, $entry)) {
                        $validator->errors()->add("entries.$index.$forbidden", 'Field waktu harus diambil dari master shift.');
                    }
                }

                $operation = $entry['operation'] ?? null;
                $shiftId = $entry['shift_id'] ?? null;
                if ($operation === 'upsert' && ($shiftId === null || $shiftId === '')) {
                    $validator->errors()->add("entries.$index.shift_id", 'Shift wajib dipilih untuk operation upsert.');
                }

                $date = $entry['date'] ?? null;
                if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    continue;
                }

                try {
                    $parsed = Carbon::createFromFormat('!Y-m-d', $date);
                } catch (\Throwable) {
                    $parsed = null;
                }
                if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                    $validator->errors()->add("entries.$index.date", 'Tanggal harus valid dalam format Y-m-d.');
                    continue;
                }

                if ($parsed->lt($periodStart) || $parsed->gt($periodEnd)) {
                    $validator->errors()->add("entries.$index.date", 'Tanggal berada di luar periode yang dipilih.');
                }

                if (isset($dates[$date])) {
                    $validator->errors()->add("entries.$index.date", 'Tanggal tidak boleh duplikat.');
                }
                $dates[$date] = true;
            }

            $expected = [];
            for ($cursor = $periodStart->copy(); $cursor->lte($periodEnd); $cursor->addDay()) {
                $expected[$cursor->toDateString()] = true;
            }

            foreach (array_diff_key($expected, $dates) as $missing => $_) {
                $validator->errors()->add('entries', "Tanggal $missing wajib dikirim.");
            }

            foreach (array_diff_key($dates, $expected) as $extra => $_) {
                $validator->errors()->add('entries', "Tanggal $extra tidak termasuk periode.");
            }
        });
    }

    public function messages(): array
    {
        return [
            'entries.*.operation.in' => 'Operation hanya boleh unchanged atau upsert.',
            'entries.*.shift_id.exists' => 'Shift yang dipilih tidak ditemukan.',
            'base_revision.regex' => 'Format revision tidak valid.',
        ];
    }
}
