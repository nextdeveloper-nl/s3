-- PostgreSQL
-- Registers the SubscribeToServerPackage action so it's reachable via the
-- generic POST /s3/accounts/{uuid}/do/{action} plumbing
-- (AccountsService::doAction() looks it up by `name` + `input`).

INSERT INTO common_available_actions (uuid, action, name, description, class, input, parameters)
VALUES (
    gen_random_uuid(),
    'subscribe-server-package',
    'Subscribe To Server Package',
    'Subscribes this S3 account to a package (fixed tier or Pay-As-You-Go) sold on a specific server.',
    'NextDeveloper\S3\Actions\Accounts\SubscribeToServerPackage',
    'NextDeveloper\S3\Accounts',
    '{"server_id": {"type": "string", "validation": "required|uuid"}, "product_catalog_id": {"type": "string", "validation": "required|uuid"}}'
);
