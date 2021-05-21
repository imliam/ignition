<?php

namespace Spatie\Ignition\ErrorPage;

use Closure;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Laravel\Telescope\Http\Controllers\HomeController;
use Laravel\Telescope\IncomingExceptionEntry;
use Laravel\Telescope\Telescope;
use Spatie\FlareClient\Report;
use Spatie\Ignition\Ignition;
use Spatie\Ignition\IgnitionConfig;
use Spatie\Ignition\Solutions\SolutionTransformer;
use Throwable;

class ErrorPageViewModel implements Arrayable
{
    protected ?Throwable $throwable;

    protected array $solutions = [];

    protected array $ignitionConfig;

    protected Report $report;

    protected string $defaultTab = 'trace';

    protected array $defaultTabProps = [];

    protected string $appEnv;

    protected bool $appDebug;

    public function __construct(
        ?Throwable $throwable,
        array $ignitionConfig,
        Report $report,
        array $solutions
    ) {
        $this->throwable = $throwable;

        $this->ignitionConfig = $ignitionConfig;

        $this->report = $report;

        $this->solutions = $solutions;
    }

    public function throwableString(): string
    {
        if (! $this->throwable) {
            return '';
        }

        $throwableString = sprintf(
            "%s: %s in file %s on line %d\n\n%s\n",
            get_class($this->throwable),
            $this->throwable->getMessage(),
            $this->throwable->getFile(),
            $this->throwable->getLine(),
            $this->report->getThrowable()->getTraceAsString()
        );

        return htmlspecialchars($throwableString);
    }

    public function telescopeUrl(): ?string
    {
        try {
            if (! class_exists(Telescope::class)) {
                return null;
            }

            if (! count(Telescope::$entriesQueue)) {
                return null;
            }

            $telescopeEntry = collect(Telescope::$entriesQueue)->first(function ($entry) {
                return $entry instanceof IncomingExceptionEntry;
            });

            if (is_null($telescopeEntry)) {
                return null;
            }

            $telescopeEntryId = (string) $telescopeEntry->uuid;

            return url(action([HomeController::class, 'index'])."/exceptions/{$telescopeEntryId}");
        } catch (Exception $exception) {
            return null;
        }
    }

    public function title(): string
    {
        $message = htmlspecialchars($this->report->getMessage());

        return "🧨 {$message}";
    }

    public function config(): array
    {
        return $this->ignitionConfig->toArray();
    }

    public function solutions(): array
    {
        $solutions = [];

        foreach ($this->solutions as $solution) {
            $solutions[] = (new SolutionTransformer($solution))->toArray();
        }

        return $solutions;
    }

    public function report(): array
    {
        return $this->report->toArray();
    }

    public function jsonEncode($data): string
    {
        $jsonOptions = JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

        return json_encode($data, $jsonOptions);
    }

    public function getAssetContents(string $asset): string
    {
        $assetPath = __DIR__."/../../resources/compiled/{$asset}";

        return file_get_contents($assetPath);
    }

    public function styles(): array
    {
        return array_keys(Ignition::styles());
    }

    public function scripts(): array
    {
        return array_keys(Ignition::scripts());
    }

    public function tabs(): string
    {
        return json_encode(Ignition::$tabs);
    }

    public function defaultTab(?string $defaultTab, ?array $defaultTabProps)
    {
        $this->defaultTab = $defaultTab ?? 'StackTab';

        if ($defaultTabProps) {
            $this->defaultTabProps = $defaultTabProps;
        }
    }

    protected function shareEndpoint(): string
    {
        return  'https://flareapp.io/share';
    }

    public function toArray(): array
    {
        return [
            'throwableString' => $this->throwableString(),
            'telescopeUrl' => $this->telescopeUrl(),
            'shareEndpoint' => $this->shareEndpoint(),
            'title' => $this->title(),
            'config' => $this->ignitionConfig,
            'solutions' => $this->solutions(),
            'report' => $this->report(),
            'housekeepingEndpoint' => '',
            'styles' => $this->styles(),
            'scripts' => $this->scripts(),
            'tabs' => $this->tabs(),
            'jsonEncode' => Closure::fromCallable([$this, 'jsonEncode']),
            'getAssetContents' => Closure::fromCallable([$this, 'getAssetContents']),
            'defaultTab' => $this->defaultTab,
            'defaultTabProps' => $this->defaultTabProps,
        ];
    }
}