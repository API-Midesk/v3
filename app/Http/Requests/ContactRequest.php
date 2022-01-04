<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Auth;

class ContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $groupid   = auth::user()->groupid;
        switch ($groupid) {   
            default:
                switch($this->method())
                {
                    case 'POST':
                        return [
                            'fullname' => 'required|max:50|min:3',
                            'phone' => 'required_without:email',
                            'email' => 'required_without:phone'
                        ];
                        break;
                    case 'PUT':
                        return [
                            'fullname' => 'required|max:50|min:3',
                            'phone' => 'required_without:email',
                            'email' => 'required_without:phone' 
                        ];
                        break;
                    default: break;
                }
                break;
        }
        return [];
    }
}
