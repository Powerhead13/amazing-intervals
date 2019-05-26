<?php

require_once SRC_ROOT . "/DbProfiler.php";

class DbProfilerSimple implements DbProfiler
{
    /**
     * @var array
     */
    private $queries = [];

    /**
     * @var float
     */
    private $start;

    /**
     * @var string
     */
    private $query;

    /**
     * @var array;
     */
    private $params;

    /**
     * @var float
     */
    private $overallTime = 0;

    /**
     * @var int
     */
    private $insertsCnt = 0;

    /**
     * @var int
     */
    private $updatesCnt = 0;

    /**
     * @var int
     */
    private $deletesCnt = 0;

    /**
     * @var int
     */
    private $selectsCnt = 0;

    /**
     * Start query profiling
     * @param string $query
     * @param array $params
     */
    public function start(string $query, array $params = null) {
        $this->query  = $query;
        $this->params = $params;
        $this->start  = microtime(true);

        // Count query types
        $q = strtolower(trim($query));
        if(strpos($q, "insert") === 0) {
            $this->insertsCnt++;
        } elseif(strpos($q, "update") === 0) {
            $this->updatesCnt++;
        } elseif(strpos($q, "delete") === 0) {
            $this->deletesCnt++;
        } elseif(strpos($q, "select") === 0) {
            $this->selectsCnt++;
        }
    }

    /**
     * Finish query profiling
     */
    public function end() {
        $time = round((microtime(true) - $this->start) * 1000, 3);

        $this->queries[] = [
            "sql"     => $this->query,
            "params"  => $this->params,
            "time"    => $time,
            "explain" => null,
        ];

        $this->overallTime += $time;
    }

    /**
     * @return mixed|void
     */
    public function reset()
    {
        $this->queries = [];
        $this->overallTime = 0;
        $this->insertsCnt = 0;
        $this->updatesCnt = 0;
        $this->deletesCnt = 0;
        $this->selectsCnt = 0;
    }

    /**
     * Get all queries count
     * @return int
     */
    public function getQueryCount(): int
    {
        return sizeof($this->getQueries());
    }

    /**
     * Get all queries count except selects
     * @return int
     */
    public function getModifyingQueryCount(): int
    {
        return $this->insertsCnt + $this->updatesCnt + $this->deletesCnt;
    }

    /**
     * @return array
     */
    public function getCounters() : array
    {
        return [
            "total"   => $this->insertsCnt + $this->updatesCnt + $this->deletesCnt + $this->selectsCnt,
            "inserts" => $this->insertsCnt,
            "updates" => $this->updatesCnt,
            "deletes" => $this->deletesCnt,
            "selects" => $this->selectsCnt,
        ];
    }

    /**
     * @return float
     */
    public function getOverallTime():float
    {
        return round($this->overallTime);
    }

    /**
     * @param DbManager $dbManager
     * @return mixed|void
     * @throws Exception
     */
    public function explain(DbManager $dbManager = null)
    {
        if(!$dbManager) {
            $dbManager = new DbManagerMySql(parse_ini_file(CONF_DB_PATH), $this, false);
        }
        $dbManager->setProfilingEnabled(false);
        if(!empty($this->queries)) {
            foreach($this->queries as $k => &$query) {
                $explain = $dbManager->query("EXPLAIN {$query['sql']}", $query['params'])->fetchAll();

                $keys = array_keys($explain[0]);
                $values = [];
                foreach($explain as $row) {
                    $values[] = array_values($row);
                }
                $query["explain"] = [
                   "keys" => $keys,
                   "values" => $values,
                ];
            }
        }
    }

    /**
     * Get profiled results
     * @param bool $includeSelects
     * @return array
     */
    public function getQueries($includeSelects = false): array
    {
        if($includeSelects) {
            return $this->queries;
        }

        $result = [];
        if(!empty($this->queries)) {
            foreach ($this->queries as $query) {
                if(strpos(strtolower($query["sql"]), "select") === 0) {
                    continue;
                }
                $result[] = $query;
            }
        }
        return $result;
    }

    /**
     * @param bool $includeSelects
     * @return string
     */
    public function getResultsAsString($includeSelects = false):string {
        $res = [];
        $queries  = $this->getQueries($includeSelects);
        $counters = $this->getCounters();

        if(!empty($queries)) {
            $i = 0;
            foreach($queries as $query) {

                $i++;

                $p = "";
                if(!empty($query["params"])) {
                    $p = "Params: " . join(", ", $query["params"]);
                }

                $res[] = "\n#$i " .  $query["sql"] . " $p\nTime: " . $query["time"] . "ms";
            }
        }
        $s = "";
        if(!$includeSelects) {
            $s = "(SELECT queries hexcluded)";
        }
        $result = "Queries: $s"
            . join("\n", $res)
            . "\n\nOverall DB time: " . $this->getOverallTime() . "ms"
            . " Inserts: " . $counters["inserts"] . "  Updates: " . $counters["updates"] . "  Deletes: " .$counters["deletes"] . "  Selects: " . $counters["selects"] . "\n"
        ;
        return $result;
    }

}