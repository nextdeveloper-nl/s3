<?php

namespace NextDeveloper\S3\Http\Requests\BackupAgents;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BackupAgentsCreateRequest extends AbstractFormRequest
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * store() maps to BackupAgentsService::create(), which is overridden to mean
     * "issue a registration token for a not-yet-installed agent" — hostname/os/
     * arch/agent_api_key are all reported later by the agent itself when it calls
     * the token-only /v1/backup-agents/register endpoint, not supplied here.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tags' => 'nullable',
        ];
    }
}
