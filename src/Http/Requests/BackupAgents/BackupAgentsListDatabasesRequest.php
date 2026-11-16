<?php

namespace NextDeveloper\S3\Http\Requests\BackupAgents;

use NextDeveloper\Commons\Http\Requests\AbstractFormRequest;
use NextDeveloper\S3\Services\BackupJobsService;

class BackupAgentsListDatabasesRequest extends AbstractFormRequest
{
    /**
     * Input of POST /s3/backup-agents/{s3_backup_agents}/list-databases.
     * `connection` names an entry in the agent's own config file; when it is
     * left out the agent uses its "default" connection.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'engine'     => 'required|string|in:' . implode(',', BackupJobsService::DATABASE_ENGINES),
            'connection' => 'nullable|string|max:64',
        ];
    }
}
