-- PostgreSQL
-- Registers the EnsurePackaging action so it's reachable via the generic
-- POST /s3/servers/{uuid}/do/{action} plumbing (ServersService::doAction()
-- looks it up by `name` + `input`). Takes no params — it just re-runs
-- ServersService::ensurePackaging() for this one server, idempotently.

INSERT INTO common_available_actions (uuid, action, name, description, class, input, parameters)
VALUES (
    gen_random_uuid(),
    'ensure-packaging',
    'Ensure Packaging',
    'Ensures this server has its Marketplace product, PAYG catalog, and the configured default paid tiers. Safe to re-run any time.',
    'NextDeveloper\S3\Actions\Servers\EnsurePackaging',
    'NextDeveloper\S3\Servers',
    '{}'
);
