<?php

namespace NextDeveloper\S3\Http\Requests\BackupAgents;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class BackupAgentsUpdateRequest extends AbstractFormRequest
{

    /**
     * status/agent_api_key are intentionally not accepted here — they only change
     * through BackupAgentsService::revoke() (or the agent's own register() call),
     * which also has side effects (clearing NATS credentials, notifying the agent)
     * that a plain field update would silently skip.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tags' => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}
