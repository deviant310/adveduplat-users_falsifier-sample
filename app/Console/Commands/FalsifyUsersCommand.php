<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ProtectionInProductionMode;
use App\Events\Falsifier\FalsifierBatch;
use App\Events\Falsifier\FalsifierComplete;
use App\Events\Falsifier\FalsifierFail;
use App\Events\Falsifier\FalsifierStart;
use App\Events\Falsifier\FalsifierStep;
use App\Services\Falsifier\UserFalsifierService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Throwable;

class FalsifyUsersCommand extends Command
{
    use ProtectionInProductionMode;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'falsify:users
                            {--skip-count= : The number of users to skip (e.g. to continue where you left off)}
                            {--ignore-id=* : Users ids whose processing should be ignored}
                            {--force : Force execution in non local env mode}';

    /**
     * The console command description.
     */
    protected $description = 'Falsify users table';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Throwable
     */
    public function handle(): int
    {
        /**
         * Protection in production mode
         */
        try {
            $this->protect();
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        /**
         * Init progress bar
         */
        $this->initProgressBar();

        /**
         * Running command handler
         */
        $ignorableIds = $this->option('ignore-id');
        $skipCount = $this->option('skip-count');

        App::make(UserFalsifierService::class, [
            'skipCount' => $skipCount,
            'ignoreIds' => $ignorableIds,
        ])->falsify();

        return Command::SUCCESS;
    }

    private function initProgressBar()
    {
        $progressBar = $this->output->createProgressBar();
        $progressBar->setRedrawFrequency(1);
        $progressBar->setBarWidth(100);
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% %current_batch%/%max_batches%');

        Event::listen(function (FalsifierStart $event) use ($progressBar) {
            $this->line('Performing users falsifying task');
            $progressBar->setMessage($event->falsifier->getBatchesTotal(), 'max_batches');
            $progressBar->setMessage(0, 'current_batch');
            $progressBar->start($event->falsifier->getItemsTotal());
        });
        Event::listen(function (FalsifierBatch $event) use ($progressBar) {
            $progressBar->setMessage($event->falsifier->getLastBatchNumber(), 'current_batch');
        });
        Event::listen(function (FalsifierStep $event) use ($progressBar) {
            $progressBar->advance();
        });
        Event::listen(function (FalsifierFail $event) {
            $this->newLine();
            $this->error('Users falsifying task failed!');
        });
        Event::listen(function (FalsifierComplete $event) use ($progressBar) {
            $progressBar->finish();
            $this->newLine();
            $this->info('Users falsifying task has been successfully completed!');
        });
    }
}
