<?php

namespace NextDeveloper\S3\Http\Requests\Servers;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class ServersCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'hostname' => 'required|string',
        'name' => 'nullable|string',
        'is_public' => 'nullable|boolean',
        'tags' => '',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
