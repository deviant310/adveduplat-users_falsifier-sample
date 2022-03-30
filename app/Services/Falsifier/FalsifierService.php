<?php

namespace App\Services\Falsifier;

use App\Events\Falsifier\FalsifierBatch;
use App\Events\Falsifier\FalsifierComplete;
use App\Events\Falsifier\FalsifierFail;
use App\Events\Falsifier\FalsifierStart;
use App\Events\Falsifier\FalsifierStep;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class FalsifierService
{
    /**
     * Database table name
     */
    private string $table;

    /**
     * Database table key name
     */
    private string $tableKeyName;

    /**
     * Database query builder for extracting records to falsify.
     */
    private Builder $query;

    /**
     * Items per batch.
     */
    private int $itemsPerBatch;

    /**
     * Total number of items to process.
     */
    private int $itemsTotal;

    /**
     * Total number of batches to process.
     */
    private int $batchesTotal;

    /**
     * Last batch number
     */
    private int $lastBatchNumber;

    /**
     * Processed items count.
     */
    private int $processedItemsCount = 0;

    /**
     * Items count to process.
     */
    private int $itemsCountToProcess = 0;

    /**
     * Last item fake attributes.
     */
    private array $lastItemFakeAttributes;

    /**
     * Fake data generator.
     */
    protected Generator $faker;

    /**
     * @param int|null $itemsPerBatch
     * @param int|null $skipCount
     * @param int[] $ignoreIds
     */
    public function __construct(?int $itemsPerBatch = 1000, ?int $skipCount = 0, ?array $ignoreIds = [])
    {
        $this->table = $this->getTableName();

        $this->tableKeyName = $this->getTableKeyName();

        $this->query = DB::table($this->table)
            ->where($this->tableKeyName, '>', $skipCount ?? 0)
            ->whereNotIn($this->tableKeyName, $ignoreIds);

        $this->itemsPerBatch = $itemsPerBatch;

        $this->itemsTotal = $this->query->clone()->count();

        $this->batchesTotal = ceil($this->itemsTotal / $this->itemsPerBatch);

        $this->faker = Faker::create();
    }

    /**
     * Retrieving table name to pass control to database manager
     *
     * @return string
     */
    abstract protected function getTableName(): string;

    /**
     * Retrieving table key name for records ordering
     *
     * @return string
     */
    abstract protected function getTableKeyName(): string;

    /**
     * Retrieving fake attributes for replacing existing data
     *
     * @return array
     */
    abstract protected function getFakeAttributes(): array;

    /**
     * @throws Throwable
     */
    public function falsify()
    {
        $this->dropItemsCountToProcess();
        $this->dropProcessedItemsCount();

        if($this->itemsTotal)
            FalsifierStart::dispatch($this);

        try {
            $this->query
                ->chunkById($this->itemsPerBatch, function ($collection, $number) {
                    $collectionCount = $collection->count();

                    $this->increaseItemsCountToProcess($collectionCount);
                    $this->setLastBatchNumber($number);

                    FalsifierBatch::dispatch($this);

                    foreach ($collection as $item) {
                        $attributes = $this->getFakeAttributes();
                        $this->setLastItemFakeAttributes($attributes);
                        $cleanAttributes = collect($attributes)
                            ->except($this->tableKeyName)
                            ->all();

                        $this->query
                            ->newQuery()
                            ->from($this->query->from)
                            ->where($this->tableKeyName, $item->{$this->tableKeyName})
                            ->update($cleanAttributes);

                        $this->increaseProcessedItemsCount();
                        FalsifierStep::dispatch($this);
                    }
                }, $this->tableKeyName);
        } catch (Throwable $error) {
            FalsifierFail::dispatch($this);

            throw $error;
        }

        if($this->itemsTotal)
            FalsifierComplete::dispatch($this);
    }

    private function increaseProcessedItemsCount()
    {
        $this->processedItemsCount += 1;
    }

    private function dropProcessedItemsCount()
    {
        $this->processedItemsCount = 0;
    }

    public function getProcessedItemsCount(): int
    {
        return $this->processedItemsCount;
    }

    public function getLastBatchNumber(): int
    {
        return $this->lastBatchNumber;
    }

    public function getItemsTotal(): int
    {
        return $this->itemsTotal;
    }

    public function getBatchesTotal(): int
    {
        return $this->batchesTotal;
    }

    public function getLastItemFakeAttributes(): array
    {
        return $this->lastItemFakeAttributes;
    }

    private function setLastItemFakeAttributes(array $attributes): void
    {
        $this->lastItemFakeAttributes = $attributes;
    }

    private function setLastBatchNumber($number): void
    {
        $this->lastBatchNumber = $number;
    }

    private function increaseItemsCountToProcess(int $count = 1)
    {
        $this->itemsCountToProcess += $count;
    }

    private function dropItemsCountToProcess()
    {
        $this->itemsCountToProcess = 0;
    }
}
