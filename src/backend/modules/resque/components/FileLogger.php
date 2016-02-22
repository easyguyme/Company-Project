<?php

namespace backend\modules\resque\components;

/**
 * FileLogger class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CFileLogRoute records log messages in files.
 *
 * The log files are stored under {@link setLogPath logPath} and the file name
 * is specified by {@link setLogFile logFile}. If the size of the log file is
 * greater than {@link setMaxFileSize maxFileSize} (in kilo-bytes), a rotation
 * is performed, which renames the current log file by suffixing the file name
 * with '.1'. All existing log files are moved backwards one place, i.e., '.2'
 * to '.3', '.1' to '.2'. The property {@link setMaxLogFiles maxLogFiles}
 * specifies how many files to be kept.
 *
 * @property string $logPath Directory storing log files. Defaults to application runtime path.
 * @property string $logFile Log file name. Defaults to 'application.log'.
 * @property integer $maxFileSize Maximum log file size in kilo-bytes (KB). Defaults to 1024 (1MB).
 * @property integer $maxLogFiles Number of files used for rotation. Defaults to 5.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.logging
 * @since 1.0
 */
class FileLogger
{
    /**
     *
     * @var integer maximum log file size
     */
    private $_maxFileSize = 1024; // in KB
    /**
     *
     * @var integer number of log files used for rotation
     */
    private $_maxLogFiles = 5;
    /**
     *
     * @var string directory storing log files
     */
    private $_logPath;
    /**
     *
     * @var string log file name
     */
    private $_logFile = 'resque.log';

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init ();
        if ($this->getLogPath () === null)
            $this->setLogPath ( \Yii::$app->runtimePath . '/logs' );
    }

    /**
     *
     * @return string directory storing log files. Defaults to application runtime path.
     */
    public function getLogPath()
    {
        return $this->_logPath;
    }

    /**
     *
     * @param string $value
     *            directory for storing log files.
     * @throws CException if the path is invalid
     */
    public function setLogPath($value)
    {
        $this->_logPath = realpath ( $value );
        if (array_key_exists('log', \Yii::$app->bootstrap) && ($this->_logPath === false || ! is_dir ( $this->_logPath ) || ! is_writable ( $this->_logPath ))) {
            throw new \Exception ("logPath $value does not point to a valid directory. Make sure the directory exists and is writable by the Web server process.");
        }
    }

    /**
     *
     * @return string log file name. Defaults to 'application.log'.
     */
    public function getLogFile()
    {
        return $this->_logFile;
    }

    /**
     *
     * @param string $value
     *            log file name
     */
    public function setLogFile($value)
    {
        $this->_logFile = $value;
    }

    /**
     *
     * @return integer maximum log file size in kilo-bytes (KB). Defaults to 1024 (1MB).
     */
    public function getMaxFileSize()
    {
        return $this->_maxFileSize;
    }

    /**
     *
     * @param integer $value
     *            maximum log file size in kilo-bytes (KB).
     */
    public function setMaxFileSize($value)
    {
        if (($this->_maxFileSize = ( int ) $value) < 1)
            $this->_maxFileSize = 1;
    }

    /**
     *
     * @return integer number of files used for rotation. Defaults to 5.
     */
    public function getMaxLogFiles()
    {
        return $this->_maxLogFiles;
    }

    /**
     *
     * @param integer $value
     *            number of files used for rotation.
     */
    public function setMaxLogFiles($value)
    {
        if (($this->_maxLogFiles = ( int ) $value) < 1)
            $this->_maxLogFiles = 1;
    }

    /**
     * Saves log messages in files.
     *
     * @param array $logs
     *            list of log messages
     */
    public function processLog($log)
    {
        $logFile = $this->getLogPath () . DIRECTORY_SEPARATOR . $this->getLogFile ();
        if (@filesize ( $logFile ) > $this->getMaxFileSize () * 1024)
            $this->rotateFiles ();
        $fp = @fopen ( $logFile, 'a' );
        @flock ( $fp, LOCK_EX );
        @fwrite ( $fp, $this->formatLogMessage ( $log [0], $log [1] ) );
        @flock ( $fp, LOCK_UN );
        @fclose ( $fp );
    }

    /**
     * Rotates log files.
     */
    protected function rotateFiles()
    {
        $file = $this->getLogPath () . DIRECTORY_SEPARATOR . $this->getLogFile ();
        $max = $this->getMaxLogFiles ();
        for($i = $max; $i > 0; -- $i) {
            $rotateFile = $file . '.' . $i;
            if (is_file ( $rotateFile )) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $max)
                    @unlink ( $rotateFile );
                else
                    @rename ( $rotateFile, $file . '.' . ($i + 1) );
            }
        }
        if (is_file ( $file ))
            @rename ( $file, $file . '.1' ); // suppress errors because it's possible multiple processes enter into this section
    }

    /**
     * Formats a log message given different fields.
     *
     * @param string $message
     *            message content
     * @param integer $level
     *            message level
     * @param string $category
     *            message category
     * @param integer $time
     *            timestamp
     * @return string formatted message
     */
    protected function formatLogMessage($message, $time)
    {
        return @date ( 'Y/m/d H:i:s', $time ) . " $message\n";
    }
}