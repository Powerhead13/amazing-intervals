<?php

interface DbManager
{
    /**
     * DbManager constructor.
     * @param array $dbConf
     * @param DbProfiler|null $profiler
     * @param bool $profilingEnabled
     */
    public function __construct(array $dbConf, DbProfiler $profiler, $profilingEnabled);

    /**
     * @param bool $isProfilingEnabled
     * @return mixed
     */
    public function setProfilingEnabled(bool $isProfilingEnabled);


    /**
     * @param $query
     * @param null $params
     * @return DbManager
     */
    public function query($query, $params = null);

    /**
     * @return array
     */
    public function fetchArray();
    public function fetchAll();

    public function getId();
    public function getLatestError();

    public function beginTransaction();
    public function commit();
    public function rollback();

    /**
     * @return DbProfiler
     */
    public function getProfiler(): DbProfiler;


    public function close();
}