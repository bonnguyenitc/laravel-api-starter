<?php

namespace App\Http\Requests;

use App\Library\HttpStatusCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ErrorRequest extends FormRequest
{
    function failedValidation(Validator $validator)
    {
        $response = new Response(['success' => false, 'data' => $validator->errors()], HttpStatusCode
        ::HTTP_UNPROCESSABLE_ENTITY);
        throw new ValidationException($validator, $response);
    }
}
