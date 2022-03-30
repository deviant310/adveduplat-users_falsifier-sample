<?php

namespace Tests\Feature\Falsifier;

use App\Events\Falsifier\FalsifierBatch;
use App\Events\Falsifier\FalsifierComplete;
use App\Events\Falsifier\FalsifierFail;
use App\Events\Falsifier\FalsifierStart;
use App\Events\Falsifier\FalsifierStep;
use App\Services\Falsifier\FalsifierService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use ReflectionException;
use Tests\TestCase;
use Tests\WithHeaderToken;
use Throwable;
use function collect;

/**
 * Class FalsifierServiceTest
 * @package Tests\Feature\Falsifier
 * @see FalsifierService
 */
abstract class FalsifierServiceTest extends TestCase
{
    use RefreshDatabase, WithHeaderToken;

    private const AVAILABLE_EVENTS = [
        FalsifierStart::class,
        FalsifierBatch::class,
        FalsifierStep::class,
        FalsifierFail::class,
        FalsifierComplete::class,
    ];

    abstract protected function getModel(): Model;
    abstract protected function getServiceClass(): string;

    /**
     * @throws ReflectionException
     */
    public function testEventsFiring(): void
    {
        $model = $this->getModel();
        $serviceClass = $this->getServiceClass();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $model::factory(10)->create();

        $firedEvents = [];

        Event::listen(function (FalsifierStart $event) use (&$firedEvents) {
            $firedEvents[] = get_class($event);
        });
        Event::listen(function (FalsifierBatch $event) use (&$firedEvents) {
            $firedEvents[] = get_class($event);
        });
        Event::listen(function (FalsifierStep $event) use (&$firedEvents) {
            $firedEvents[] = get_class($event);
        });
        Event::listen(function (FalsifierFail $event) use (&$firedEvents) {
            $firedEvents[] = get_class($event);
        });
        Event::listen(function (FalsifierComplete $event) use (&$firedEvents) {
            $firedEvents[] = get_class($event);
        });

        App::make($serviceClass)->falsify();

        collect(self::AVAILABLE_EVENTS)
            ->filter(fn ($event) => $event !== FalsifierFail::class)
            ->each(fn ($event) => $this->assertContains($event, $firedEvents, "Event $event hasn't been fired!"));
    }

    /**
     * @throws Throwable
     */
    public function testMainCase(): void
    {
        /**
         * @var FalsifierService $falsifier
         */

        $model = $this->getModel();
        $serviceClass = $this->getServiceClass();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $model::factory(10)->create();

        $falsifier = App::make($serviceClass);

        $fakeItems = [];

        Event::listen(function (FalsifierStep $event) use (&$fakeItems) {
            $fakeItems[] = $event->falsifier->getLastItemFakeAttributes();
        });

        $falsifier->falsify();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $this->assertCount(
            $falsifier->getProcessedItemsCount(),
            $model->get()
        );

        collect($fakeItems)
            ->each(fn ($fakeItem) => $this->assertDatabaseHas($model->getTable(), $fakeItem));
    }

    /**
     * @throws Throwable
     */
    public function testUniqueDataGenerating(): void
    {
        /**
         * @var FalsifierService $falsifier
         */

        $model = $this->getModel();
        $serviceClass = $this->getServiceClass();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $model::factory(3000)->create();

        $isCompleted = false;

        Event::listen(function (FalsifierComplete $event) use (&$isCompleted) {
            $isCompleted = true;
        });

        $falsifier = App::make($serviceClass);
        $falsifier->falsify();

        $this->assertTrue($isCompleted);
    }

    /**
     * @throws Throwable
     */
    public function testSkippingData(): void
    {
        /**
         * @var FalsifierService $falsifier
         */

        $model = $this->getModel();
        $serviceClass = $this->getServiceClass();

        $count = 10;

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $model::factory($count)->create();

        $skipCount = $count / 2;

        $falsifier = App::make($serviceClass, [
            'skipCount' => $skipCount
        ]);

        $falsifier->falsify();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $this->assertCount(
            $falsifier->getProcessedItemsCount(),
            $model->where($model->getKeyName(), '>', $skipCount)->get()
        );
    }

    /**
     * @throws Throwable
     */
    public function testIgnoringData(): void
    {
        /**
         * @var FalsifierService $falsifier
         */

        $model = $this->getModel();
        $serviceClass = $this->getServiceClass();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $model::factory(5)->create();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $firstRecord = $model::first();

        $falsifier = App::make($serviceClass, [
            'ignoreIds' => [$firstRecord->id]
        ]);

        $falsifier->falsify();

        $this->assertDatabaseHas($model->getTable(), $firstRecord->getAttributes());
    }
}
