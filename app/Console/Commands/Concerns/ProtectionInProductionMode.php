<?php

namespace App\Console\Commands\Concerns;

use Exception;

trait ProtectionInProductionMode
{
    /**
     * @throws Exception
     */
    private function protect()
    {
        if (app()->environment('production')) {
            throw new Exception('Cannot run this command in production mode');
        }

        $appEnv = app()->environment();
        $isForceRunning = $this->option('force');

        if (!$isForceRunning) {
            if (!app()->environment('local')) {
                if (!$this->confirm("Do you wish to continue in {$appEnv} mode?")) {
                    throw new Exception("Command canceled");
                }
            }
        }
    }
}
