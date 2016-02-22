<?php
namespace backend\modules\resque\components;

/**
 * This file part of RResque
 *
 * Autoloader for Resque library
 *
 * For license and full copyright information please see main package file
 * @package       yii-resque
 */
class RResqueAutoloader
{
    /**
     * Registers Raven_Autoloader as an SPL autoloader.
     */
    public static function register()
    {
        spl_autoload_unregister(array('Yii','autoload'));
        spl_autoload_register(array(new self,'autoload'));
        spl_autoload_register(array('Yii','autoload'), true, true);
    }

    /**
     * Handles autoloading of classes.
     *
     * @param  string  $class  A class name.
     *
     * @return boolean Returns true if the class has been loaded
     */
    public static function autoload($class)
    {
        if (is_file($file = dirname(__FILE__).'/lib/'.str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
            require_once $file;
        } else if (is_file($file = dirname(__FILE__).'/lib/ResqueScheduler/'.str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
            require_once $file;
        } else if (is_file($file = dirname(__FILE__).'/'.str_replace(array('_', "\0"), array('/', ''), $class).'.php')) {
            require_once $file;
        } else if (is_file($file = dirname(__FILE__).'/lib/'.str_replace(array('\\', "\0"), array('/', ''), $class).'.php')) {
            require_once $file;
        } else if (is_file($file = \Yii::$app->basePath . '/../' . str_replace(array('\\', "\0"), array('/', ''), $class) . '.php')) {
            require_once $file;
        }

        require_once(dirname(__FILE__) . '/lib/Resque/Job.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Event.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Redis.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Stat.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Worker.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Exception.php');
        require_once(dirname(__FILE__) . '/lib/ResqueScheduler/Worker.php');
        require_once(dirname(__FILE__) . '/lib/Resque/Job/Status.php');
    }
}
