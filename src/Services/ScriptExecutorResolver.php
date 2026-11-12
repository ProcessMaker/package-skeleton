<?php

declare(strict_types=1);

namespace ProcessMaker\Package\PackageSkeleton\Services;

use ProcessMaker\Models\ScriptExecutor;

class ScriptExecutorResolver
{
    public function resolveForNewScript(): ?ScriptExecutor
    {
        $nayra = ScriptExecutor::query()
            ->where('language', 'php-nayra')
            ->orderBy('id')
            ->first();
        if ($nayra !== null) {
            return $nayra;
        }

        $php = ScriptExecutor::query()
            ->where('language', 'php')
            ->orderBy('id')
            ->first();
        if ($php !== null) {
            return $php;
        }

        return ScriptExecutor::query()->orderBy('id')->first();
    }
}
