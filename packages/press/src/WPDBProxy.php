<?php

declare(strict_types=1);

namespace Moox\Press;

class WPDBProxy extends \wpdb
{
    private string $logFile;

    public function __construct($dbuser, $dbpassword, $dbname, $dbhost)
    {
        parent::__construct($dbuser, $dbpassword, $dbname, $dbhost);

        $this->logFile = $this->getStoragePath().'/logs/wpdb-proxy.log';
        $this->ensureLogDirectoryExists();
    }

    private function getStoragePath(): string
    {
        if (defined('ABSPATH')) {
            return dirname(ABSPATH, 2).'/storage';
        }

        return dirname(__DIR__, 3).'/storage';
    }

    private function ensureLogDirectoryExists(): void
    {
        $logDir = dirname($this->logFile);
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    private function log(string $method, $query, $args, $result, float $executionTime): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $wpFunctions = array_filter($backtrace, function ($frame) {
            return isset($frame['function']) &&
                   ! str_starts_with($frame['function'] ?? '', 'Moox\\');
        });
        $wpFunctions = array_slice(array_values($wpFunctions), 0, 3);

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'method' => $method,
            'query' => $query,
            'args' => $args,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'rows_affected' => $this->rows_affected ?? 0,
            'num_rows' => $this->num_rows ?? 0,
            'last_error' => $this->last_error,
            'backtrace' => array_map(function ($frame) {
                return sprintf(
                    '%s%s%s() in %s:%d',
                    $frame['class'] ?? '',
                    $frame['type'] ?? '',
                    $frame['function'] ?? '',
                    basename($frame['file'] ?? 'unknown'),
                    $frame['line'] ?? 0
                );
            }, $wpFunctions),
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES).PHP_EOL;
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    public function query($query)
    {
        $startTime = microtime(true);
        $result = parent::query($query);
        $executionTime = microtime(true) - $startTime;

        $this->log('query', $query, null, $result, $executionTime);

        return $result;
    }

    public function get_results($query = null, $output = OBJECT)
    {
        $startTime = microtime(true);
        $result = parent::get_results($query, $output);
        $executionTime = microtime(true) - $startTime;

        $this->log('get_results', $query, ['output' => $output], $result, $executionTime);

        return $result;
    }

    public function get_row($query = null, $output = OBJECT, $y = 0)
    {
        $startTime = microtime(true);
        $result = parent::get_row($query, $output, $y);
        $executionTime = microtime(true) - $startTime;

        $this->log('get_row', $query, ['output' => $output, 'offset' => $y], $result, $executionTime);

        return $result;
    }

    public function get_col($query = null, $x = 0)
    {
        $startTime = microtime(true);
        $result = parent::get_col($query, $x);
        $executionTime = microtime(true) - $startTime;

        $this->log('get_col', $query, ['column' => $x], $result, $executionTime);

        return $result;
    }

    public function get_var($query = null, $x = 0, $y = 0)
    {
        $startTime = microtime(true);
        $result = parent::get_var($query, $x, $y);
        $executionTime = microtime(true) - $startTime;

        $this->log('get_var', $query, ['column' => $x, 'row' => $y], $result, $executionTime);

        return $result;
    }

    public function insert($table, $data, $format = null)
    {
        $startTime = microtime(true);
        $result = parent::insert($table, $data, $format);
        $executionTime = microtime(true) - $startTime;

        $this->log('insert', $this->last_query, [
            'table' => $table,
            'data' => $data,
            'format' => $format,
        ], $result, $executionTime);

        return $result;
    }

    public function update($table, $data, $where, $format = null, $where_format = null)
    {
        $startTime = microtime(true);
        $result = parent::update($table, $data, $where, $format, $where_format);
        $executionTime = microtime(true) - $startTime;

        $this->log('update', $this->last_query, [
            'table' => $table,
            'data' => $data,
            'where' => $where,
            'format' => $format,
            'where_format' => $where_format,
        ], $result, $executionTime);

        return $result;
    }

    public function delete($table, $where, $where_format = null)
    {
        $startTime = microtime(true);
        $result = parent::delete($table, $where, $where_format);
        $executionTime = microtime(true) - $startTime;

        $this->log('delete', $this->last_query, [
            'table' => $table,
            'where' => $where,
            'where_format' => $where_format,
        ], $result, $executionTime);

        return $result;
    }

    public function replace($table, $data, $format = null)
    {
        $startTime = microtime(true);
        $result = parent::replace($table, $data, $format);
        $executionTime = microtime(true) - $startTime;

        $this->log('replace', $this->last_query, [
            'table' => $table,
            'data' => $data,
            'format' => $format,
        ], $result, $executionTime);

        return $result;
    }
}
