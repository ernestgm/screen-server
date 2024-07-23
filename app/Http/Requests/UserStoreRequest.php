<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'lastname' => ['required', 'string'],
            'password' => ['required', 'string'],
            'c_password' => ['required', 'same:password'],
            'email' => ['required', 'string', 'unique:users'],
            'role_id' => ['required', 'integer'],
            'enabled' => ['integer'],
        ];
    }

    public function failedValidation(Validator $validator)

    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));
    }



    public function messages(): array
    {
        return [
            'c_password.same:password' => 'The password fields must be match.',
        ];
    }
}
