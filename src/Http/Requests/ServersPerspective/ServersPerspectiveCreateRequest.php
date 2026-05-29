<?php

namespace NextDeveloper\S3\Http\Requests\ServersPerspective;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class ServersPerspectiveCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'hostname' => 'nullable|string',
        'name' => 'nullable|string',
        'agent_version' => 'nullable|string',
        'seaweedfs_version' => 'nullable|string',
        'agent_status' => 'nullable|string',
        'agent_last_seen_at' => 'nullable|date',
        'agent_connected_at' => 'nullable|date',
        'health' => 'nullable|string',
        'health_summary' => 'nullable|string',
        'components' => 'nullable',
        'tags' => 'nullable',
        'master_reachable' => 'nullable|boolean',
        'master_is_leader' => 'nullable|boolean',
        'master_peers' => 'nullable|integer',
        'volume_reachable' => 'nullable|boolean',
        'volume_total' => 'nullable|integer',
        'volumes_writable' => 'nullable|integer',
        'volumes_degraded' => 'nullable|integer',
        'volumes_readonly' => 'nullable|integer',
        'capacity_bytes_total' => 'nullable|integer',
        'capacity_bytes_used' => 'nullable|integer',
        'capacity_pct' => 'nullable',
        'capacity_gb_total' => 'nullable',
        'capacity_gb_used' => 'nullable',
        'filer_reachable' => 'nullable|boolean',
        's3_reachable' => 'nullable|boolean',
        's3_bucket_count' => 'nullable|integer',
        'hosted_bucket_count' => 'nullable|integer',
        'hosted_account_count' => 'nullable|integer',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}