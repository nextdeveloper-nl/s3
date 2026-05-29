<?php

namespace NextDeveloper\S3\Http\Requests\ServerTelemetries;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;

class ServerTelemetriesUpdateRequest extends AbstractFormRequest
{

    /**
     * @return array
     */
    public function rules()
    {
        return [
            's3_server_id' => 'nullable|exists:s3_servers,uuid|uuid',
        'reported_at' => 'date',
        'master_reachable' => 'nullable|boolean',
        'volume_count' => 'nullable|integer',
        'volumes_degraded' => 'nullable|integer',
        'capacity_bytes_total' => 'nullable|integer',
        'capacity_bytes_used' => 'nullable|integer',
        'capacity_pct' => 'nullable',
        ];
    }
    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE
}