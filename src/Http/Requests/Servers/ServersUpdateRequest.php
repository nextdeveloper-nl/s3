<?php

namespace NextDeveloper\S3\Http\Requests\Servers;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class ServersUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'hostname' => 'nullable|string',
        'name' => 'nullable|string',
        'tags' => '',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}