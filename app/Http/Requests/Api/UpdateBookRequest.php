<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'string',
                'regex:/^[0-9]{13}$/',
                Rule::unique('books', 'isbn')->ignore($book),
            ],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('genres', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者名を入力してください。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'isbn.required' => 'ISBNを入力してください。',
            'isbn.string' => 'ISBNは文字列で入力してください。',
            'isbn.regex' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'このISBNはすでに登録されています。',

            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '有効な出版日を入力してください。',

            'description.string' => '説明は文字列で入力してください。',
            'image_url.url' => '画像URLは正しいURL形式で入力してください。',

            'user_id.required' => '登録者IDを指定してください。',
            'user_id.integer' => '登録者IDは整数で指定してください。',
            'user_id.exists' => '指定された登録者は存在しません。',

            'genres.required' => 'ジャンルを1つ以上指定してください。',
            'genres.array' => 'ジャンルは配列で指定してください。',
            'genres.min' => 'ジャンルを1つ以上指定してください。',

            'genres.*.required' => '有効なジャンルを指定してください。',
            'genres.*.integer' => 'ジャンルIDは整数で指定してください。',
            'genres.*.distinct' => '同じジャンルを重複して指定できません。',
            'genres.*.exists' => '存在しないジャンルが指定されています。',
        ];
    }
}
