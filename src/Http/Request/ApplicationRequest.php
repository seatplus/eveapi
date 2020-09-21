<?php


namespace Seatplus\Eveapi\Http\Request;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationRequest extends FormRequest
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
            'corporation_id' => ['required', 'exists:enlistments,corporation_id'],
            'character_id' => ['exists:character_infos,character_id'],
        ];
    }

}
