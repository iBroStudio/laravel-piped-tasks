<?php

namespace IBroStudio\PipedTasks\Concerns;

use Illuminate\Support\Facades\DB;

trait HasDatabaseTransactions
{
    protected bool $useTransaction = false;

    public function withTransaction(): static
    {
        $this->useTransaction = true;

        return $this;
    }

    protected function beginTransaction(): void
    {
        if (! $this->useTransaction) {
            return;
        }

        DB::beginTransaction();
    }

    protected function commitTransaction(): void
    {
        if (! $this->useTransaction) {
            return;
        }

        DB::commit();
    }

    protected function rollbackTransaction(): void
    {
        if (! $this->useTransaction) {
            return;
        }

        DB::rollBack();
    }
}
