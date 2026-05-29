<?php

namespace NextDeveloper\S3\Http\Requests\ServerCapacityStats;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class ServerCapacityStatsCreateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_server_id' => 'nullable|exists:s3_servers,uuid|uuid',
        'hostname' => 'nullable|string',
        'name' => 'nullable|string',
        'health' => 'nullable|string',
        'agent_status' => 'nullable|string',
        'agent_last_seen_at' => 'nullable|date',
        'latest_reported_at' => 'nullable|date',
        'master_reachable' => 'nullable|boolean',
        'volume_count' => 'nullable|integer',
        'volumes_degraded' => 'nullable|integer',
        'capacity_bytes_total' => 'nullable|integer',
        'capacity_bytes_used' => 'nullable|integer',
        'capacity_pct' => 'nullable',
        'capacity_gb_total' => 'nullable',
        'capacity_gb_used' => 'nullable',
        'hosted_bucket_count' => 'nullable|integer',
        'hosted_account_count' => 'nullable|integer',
        'minutes_since_last_report' => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}