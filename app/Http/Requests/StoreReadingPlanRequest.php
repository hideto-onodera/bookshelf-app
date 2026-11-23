<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReadingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && Gate::allows('create', ReadingPlan::class);
    }

    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                Rule::exists('books', 'id'),
            ],
            'target_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $alreadyExists = ReadingPlan::query()
                ->where('user_id', $this->user()->id)
                ->where('book_id', $this->integer('book_id'))
                ->where('status', ReadingPlanStatus::InProgress->value)
                ->exists();

            if ($alreadyExists) {
                $validator->errors()->add(
                    'book_id',
                    'この書籍には進行中の読書計画が既に存在します。'
                );
            }
        });
    }
}
