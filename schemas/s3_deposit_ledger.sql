-- PostgreSQL

CREATE TABLE s3_deposit_ledger (
    id                     bigint NOT NULL DEFAULT nextval('s3_deposit_ledger_id_seq'::regclass),
    s3_account_id          bigint NOT NULL,
    s3_worm_commitment_id  bigint NOT NULL,
    iam_account_id         bigint NOT NULL,
    type                   text NOT NULL,
    amount                 numeric(12,6) NOT NULL,
    days_remaining         integer,
    days_total             integer,
    reference              text,
    performed_by           text NOT NULL,
    notes                  text,
    performed_at           timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT s3_deposit_ledger_commitment_id_foreign FOREIGN KEY (s3_worm_commitment_id) REFERENCES s3_worm_commitments(id),
    CONSTRAINT s3_deposit_ledger_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id),
    CONSTRAINT s3_deposit_ledger_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_deposit_ledger_account_idx ON public.s3_deposit_ledger USING btree (s3_account_id);
CREATE INDEX s3_deposit_ledger_commitment_idx ON public.s3_deposit_ledger USING btree (s3_worm_commitment_id);
CREATE INDEX s3_deposit_ledger_performed_at_idx ON public.s3_deposit_ledger USING btree (performed_at DESC);
