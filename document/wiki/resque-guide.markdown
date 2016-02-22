# Yii component API documentaion
## Create job directly

You can put this line where ever you want to add jobs to queue

```php
    Yii::$app->resque->createJob('queue_name', 'Job_ClassJob', $args = array());
```

Put your jobs inside Job folder and name the class with ```Job_``` as prefix, e.g you want to create worker with name SendEmail then you can create file inside Job folder and name it SendEmail.php, class inside this file must be ```Job_SendEmail```

## Create Delayed Job

You can run job at specific time

```php
    $time = 1332067214;
    Yii::$app->resque->enqueueJobAt($time, 'queue_name', 'Job_ClassJob', $args = array());
```

or run job after n second

```php
    $in = 3600;
    $args = array('id' => $user->id);
    Yii::$app->resque->enqueueJobIn($in, 'email', 'Job_ClassJob', $args);
```

## Get the total count of delayed jobs

You can get the count of jobs waiting to be executed

```php
    Yii::$app->resque->getDelayedJobsCount();
```

## Get updated time of delayed jobs

This will return all job in queue (EXCLUDE all active job)

```php
    Yii::$app->resque->getDelayedJobs();
```

## Get the status for a job

This will return the status of specified job by passing its token.

* Resque_Job_Status::STATUS_WAITING = 1;
* Resque_Job_Status::STATUS_RUNNING = 2;
* Resque_Job_Status::STATUS_FAILED = 3;
* Resque_Job_Status::STATUS_COMPLETE = 4;

```php
    Yii::$app->resque->status('ba1dfb1e2f20a938cbbe5accfd4a845d');
```


# Php Command
## Some examples
* Start workers and make it listen on the default queue

```sh
    QUEUE=default php {project_path}/src/backend/modules/resque/components/bin/resque
```


## Start Worker Options

This is available options for starting worker using `yiic` command :

* Set queue name

```sh
 QUEUE=default
```
This option default to `*` means all queue.

* Set interval time

```sh
INTERVAL=[time in second]
```
Set your interval time for checking new job.

* Run in verbose mode

```sh
VERBOSE=[1 or 0]
```
Set to `1` if you want to see more information in log file.

* Number of worker

```sh
COUNT=[integer]
```

* Log

```sh
LOGGING=1
```







# Priorities and Queue Lists

Similarly, priority and queue list functionality works exactly
the same as the Ruby workers. Multiple queues should be separated with
a comma, and the order that they're supplied in is the order that they're
checked in.

* A php command example:

```sh
    QUEUE=default,mail php {project_path}/src/backend/modules/resque/components/bin/resque
```

The `default` queue will always be checked for new jobs on each
iteration before the `mail` queue is checked.

* As a worker is also supported to be created dynamically, below code is also an option:

```php
    Yii::$app->resque->createJob('default,mail', 'Job_ClassJob', $args = array());
```

* This is also can be defined in the configuration file of supervisord, so that these workers will be under the control of supervisord.

The configuration maybe looks like

```sh
[program:email]
command = php {project_path}/src/backend/modules/resque/components/bin/resque
numprocs=1
stderr_logfile_maxbytes=10MB
stdout_logfile=/var/log/supervisor/%(program_name)s-stdout.log
stderr_logfile=/var/log/supervisor/%(program_name)s-stderr.log
redirect_stderr=true
autostart=true
autorestart=true
environment=QUEUE='default,email',APP_INCLUDE='{project_path}/src/backend/modules/resque/components/lib/Resque/RequireFile.php'

```





# Getting Started

The easiest way to work with yiiResque is when it's installed as a
yii module inside your yii project.

## Configuration

* Copy the resque module to the modules folder of Yii

* Add configuration to your main.php in config folder

```php
    ...
    'modules'=>array(
        ...
        'resque'
    ),
    ...
    'components'=>array(
        ...
        'resque'=>array(
            'class' => 'backend\modules\resque\components\RResque',
            'server' => 'localhost',    // Redis server address
            'port' => '6379',           // Redis server port
            'database' => 0             // Redis database number
        ),
        ...
    ),
    ...
```


## Jobs

### Queueing Jobs

Jobs are queued as follows:

```php
Yii::$app->resque->createJob('queue_name', 'Job_ClassJob', $args = array());
```

### Defining Jobs

Each job should be in its own class, and include a `perform` method.

```php
class Job_MyJob
{
    public function perform()
    {
        // Work work work
        echo $this->args['name'];
    }
}
```

When the job is run, the class will be instantiated and any arguments
will be set as an array on the instantiated object, and are accessible
via `$this->args`.

Any exception thrown by a job will result in the job failing - be
careful here and make sure you handle the exceptions that shouldn't
result in a job failing.

Jobs can also have `setUp` and `tearDown` methods. If a `setUp` method
is defined, it will be called before the `perform` method is run.
The `tearDown` method, if defined, will be called after the job finishes.


```php
class My_Job
{
    public function setUp()
    {
        // ... Set up environment for this job
    }

    public function perform()
    {
        // .. Run job
    }

    public function tearDown()
    {
        // ... Remove environment for this job
    }
}
```
### Tracking Job Statuses

yiiResque has the ability to perform basic status tracking of a queued
job. The status information will allow you to check if a job is in the
queue, is currently being run, has finished, or has failed.

Store the job token when creating it, and call the API to fetch its status

```php
$token = 'ba1dfb1e2f20a938cbbe5accfd4a845d';
Yii::$app->resque->status($token);
```

Job statuses are defined as constants in the `Resque_Job_Status` class.
Valid statuses include:

* `Resque_Job_Status::STATUS_WAITING` - Job is still queued
* `Resque_Job_Status::STATUS_RUNNING` - Job is currently running
* `Resque_Job_Status::STATUS_FAILED` - Job has failed
* `Resque_Job_Status::STATUS_COMPLETE` - Job is complete
* `false` - Failed to fetch the status - is the token valid?

Statuses are available for up to 24 hours after a job has completed
or failed, and are then automatically expired.

## Workers

Workers work in the exact same way as the php-resque workers. For complete
documentation on workers, see the original documentation.

To start a worker, there is two optional ways:

* configure supervisord

```sh
[program:scheduler]
command = php {project_path}/src/backend/modules/resque/components/bin/resque-scheduler
numprocs=1
stderr_logfile_maxbytes=10MB
stdout_logfile=/var/log/supervisor/%(program_name)s-stdout.log
stderr_logfile=/var/log/supervisor/%(program_name)s-stderr.log
redirect_stderr=true
autostart=true
autorestart=true
environment=QUEUE=*,LOGGING='1',APP_INCLUDE='{project_path}/src/backend/modules/resque/components/lib/Resque/RequireFile.php'

[program:demoecho]
command = php {project_path}/src/backend/modules/resque/components/bin/resque
numprocs=1
stderr_logfile_maxbytes=10MB
stdout_logfile=/var/log/supervisor/%(program_name)s-stdout.log
stderr_logfile=/var/log/supervisor/%(program_name)s-stderr.log
redirect_stderr=true
autostart=true
autorestart=true
environment=QUEUE='demoecho',LOGGING='1',APP_INCLUDE='{project_path}/src/backend/modules/resque/components/lib/Resque/RequireFile.php'
```

* run php command

```sh
    QUEUE=default php {project_path}/src/backend/modules/resque/components/bin/resque
```
