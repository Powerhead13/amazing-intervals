<?php

interface DbProfiler
{
    /**
     * Start query profiling
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public function start(string $query, array $params);

    /**
     * Finish query profiling
     * @return mixed
     */
    public function end();

    /**
     * Reset counters and stats
     * @return mixed
     */
    public function reset();

    /**
     * Iverall queries count
     * @return int
     */
    public function getQueryCount():int;

    /**
     * @return int
     */
    public function getModifyingQueryCount():int;

    /**
     * Counters by query type
     * @return array
     */
    public function getCounters() : array;

    /**
     * Sum af all DB queries runtime
     * @return float
     */
    public function getOverallTime():float;

    /**
     * Explain all profiled queries.
     * @param DbManager|null $dbManager
     * @return mixed
     */
    public function explain(DbManager $dbManager = null);

    /**
     * Get profiled results
     * @param bool $includeSelects
     * @return array
     */
    public function getQueries($includeSelects = false): array;

    /**
     * Formatted results for debug
     * @param bool $includeSelects
     * @return string
     */
    public function getResultsAsString($includeSelects = false):string;
}