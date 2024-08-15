<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;

class ImageUpdateRequest extends FormRequest
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
        $contentType = $this->headers->get('content-type');
        if ($contentType === "application/json") {
            $rules = [
                'name' => ['required'],
                'image' => 'required|string',
                'is_static' => ['integer'],
                'duration' => ['integer'],
            ];
        } else {
           $rules = [
                'name' => ['required'],
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:6144',
                'is_static' => ['integer'],
                'duration' => ['integer'],
            ];
        }

        return $rules;
    }

    public function failedValidation(Validator $validator)

    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));
    }
}
