<?php


namespace Seatplus\Eveapi\Http\Request;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOpenRecruitmentRequest extends FormRequest
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

        return [
            'corporation_id' => ['required', 'exists:corporation_info,corporation_info'],
            'type' => ['required', Rule::in(['character','user'])],
        ];
    }

}
