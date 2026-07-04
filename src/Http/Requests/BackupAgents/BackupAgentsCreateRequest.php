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
     * the token-only /backup-agents/register endpoint, not supplied here.
     *
     * s3_bucket_id is required — the agent is never given a bucket we
     * provisioned on its behalf; the customer must already own one (created
     * via the normal Buckets API) before registering a machine against it.
     *
     * @return array
     */
    public function rules()
    {
        return [
            's3_bucket_id' => 'required|exists:s3_buckets,uuid|uuid',
            'tags'         => 'nullable',
        ];
    }
}
