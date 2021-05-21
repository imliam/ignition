<?php

namespace Spatie\Ignition\Middleware;

use Spatie\FlareClient\Report;
use Spatie\Ignition\QueryRecorder\QueryRecorder;

class AddQueries
{
    protected QueryRecorder $queryRecorder;

    public function __construct(QueryRecorder $queryRecorder)
    {
        $this->queryRecorder = $queryRecorder;
    }

    public function handle(Report $report, $next)
    {
        $report->group('queries', $this->queryRecorder->getQueries());

        return $next($report);
    }
}