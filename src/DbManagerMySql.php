<?php

require_once SRC_ROOT . "/DbManager.php";
require_once SRC_ROOT . "/DbProfilerSimple.php";

class DbManagerMySql implements DbManager
{
    /**
     * @var bool
     */
    protected $profilingEnabled;

    /**
     * @var mysqli
     */
    protected $connection;

    /**
     * @var mysqli_stmt
     */
    protected $query;

    /**
     * @var DbProfiler
     */
    protected $profiler;

    /**
     * DbManager constructor.
     * @param array|null $dbConf
     * @param DbProfiler|null $profiler
     * @param bool $profilingEnabled
     * @throws Exception
     */
    public function __construct(array $dbConf = null, DbProfiler $profiler = null, $profilingEnabled = true) {

        $this->setProfilingEnabled($profilingEnabled);

        if(!$dbConf) {
            $dbConf = parse_ini_file(CONF_DB_PATH);
        }
        if(!$profiler) {
            $profiler = new DbProfilerSimple();
        }
        $this->profiler = $profiler;
        $this->connect($dbConf["dbhost"], $dbConf["dbuser"], $dbConf["dbpass"], $dbConf["dbname"]);
    }

    /**
     * @param bool $isProfilingEnabled
     */
    public function setProfilingEnabled(bool $isProfilingEnabled)
    {
        $this->profilingEnabled = $isProfilingEnabled;
    }

    /**
     * @param $query
     * @param null $params
     * @return $this|mixed
     * @throws Exception
     */
    public function query($query, $params = null) {
        if ($this->query = $this->connection->prepare($query)) {

            $this->bindParams($this->query,$params);

            // Profile start
            if($this->profilingEnabled) {
                $this->getProfiler()->start($query, $params);
            }

            // Run MySql query
            $this->query->execute();

            // End query profiling
            if($this->profilingEnabled) {
                $this->getProfiler()->end();
            }

            if ($this->query->errno) {
                throw new Exception("Unable to process MySQL query (check your params): " . $this->query->error);
            }

        } else {
            if(!$this->query) {
                throw new Exception($this->connection->error);
            } else {
                throw new Exception("Unable to prepare statement (check your syntax): " . $this->query->error);
            }
        }
        return $this;
    }

    /**
     * @param $query
     * @param null $params
     */
    private function bindParams(&$query, $params = null) {
        if($params && !is_array($params)) {
            $params = [$params];
        }
        if($params && is_array($params)) {
            $types = "";
            $args_ref = [];
            foreach($params as &$param) {
                $types .= $this->gettype($param);
                $args_ref[] = &$param;
            }

            array_unshift($args_ref, $types);
            call_user_func_array([$query, 'bind_param'], $args_ref);
        }
    }

    /**
     * @return array
     */
    public function fetchAll() {
        $params = [];
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array([$this->query, 'bind_result'], $params);
        $result = [];
        while ($this->query->fetch()) {
            $r = [];
            foreach ($row as $key => $val) {
                $r[$key] = $val;
            }
            $result[] = $r;
        }
        $this->query->close();
        return $result;
    }

    /**
     * @return array
     */
    public function fetchArray() {
        $params = [];
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array([$this->query, 'bind_result'], $params);
        $result = [];
        while ($this->query->fetch()) {
            foreach ($row as $key => $val) {
                $result[$key] = $val;
            }
        }
        $this->query->close();
        return $result;
    }

    /**
     * Get last insert id
     * @return int
     */
    public function getId() {
        return $this->query->insert_id;
    }

    /**
     * @return string
     */
    public function getLatestError() {
        return $this->query->error;
    }

    public function beginTransaction() {
        $this->connection->begin_transaction();
    }

    public function commit() {
        $this->connection->commit();
    }

    public function rollback() {
        $this->connection->rollback();
    }

    /**
     * @return int
     */
    public function numRows() {
        $this->query->store_result();
        return $this->query->num_rows;
    }

    /**
     * @return bool
     */
    public function close() {
        return $this->connection->close();
    }

    /**
     * @return int
     */
    public function affectedRows() {
        return $this->query->affected_rows;
    }


    /**
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpass
     * @param string $dbname
     * @param string $charset
     * @throws Exception
     */
    private function connect($dbhost = "localhost", $dbuser = "root", $dbpass = "", $dbname = "", $charset = "utf8") {
        $this->connection = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        if($this->connection->connect_error) {
            throw new Exception("Db connection error: " . $this->connection->connect_error);
        }
        $this->connection->set_charset($charset);
    }

    /**
     * Detect argument type for prepared statement
     * @param $var
     * @return string
     */
    private function gettype($var) {
        if(is_string($var)) return 's';
        if(is_float($var)) return 'd';
        if(is_int($var)) return 'i';
        return 'b';
    }

    /**
     * @return DbProfiler
     */
    public function getProfiler(): DbProfiler
    {
        return $this->profiler;
    }
}