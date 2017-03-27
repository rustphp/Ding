<?php
namespace Ding\Logger;

/**
 * Class Logger
 *
 * @package Ding\Logger
 */
use SeasLog;

class Logger extends SeasLog implements ILoggerAware {
    public function __construct($class) {
        parent::__construct();
        $this->setLogger($class);
    }
}