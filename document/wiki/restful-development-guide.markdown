# Restful webservice develop guide

## Set up modules
* The structure of the module should be like this:

```
├── modules
│   └── moduleName
│       ├── controllers
│       └── Module.php

(You can add other folders, such as "models", "views", if necessary. )
```

* Edit Module.php follow the example below:

```
<?php
namespace backend\modules\xxx;

class Module extends \yii\base\Module
{
    public function init()
    {
        parent::init();

        // $this->params['foo'] = 'bar';
        // ...  other initialization code ...
    }
    
    //... other functions if necessary ...
}
```


## controllers and actions
* Add the controller in the folder "controllers" 

```
<?php
namespace backend\controllers;

use backend\components\rest\RestController;
// ... other 'use' statements ...

class UserController extends RestController
{
    //... code ...
}
```

* The modelClass should be specified to identify the source

```
public $modelClass = 'backend\models\User';
```

### index(method: GET)
* The url follows the pattern: 

```
GET http://{server-domain}/{module}/{controller}
```

* The response contains the list of the source

```
GET api.augmarketing.com/xxx/users

{
    "items": [
        {
            "id": "54758ea321a3cc6c048b4575",
            "email": "devinjin@augmentum.con.cn",
            "name": "aaaaa"
        },
        {
            "id": "5475ab9e21a3cc6b048b4572",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        {
            "id": "5475ab9f21a3cc6c048b4576",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        {
            "id": "5475aba021a3cc6b048b4573",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        {
            "id": "5475aba021a3cc6c048b4577",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        {
            "id": "5475aba121a3cc6b048b4574",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        {
            "id": "5475aba121a3cc6c048b4578",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        ... more items ...
    ],
    "_links": {
        "self": {
            "href": "http://api.augmarketing.com/xxx/users?page=1"
        },
        "next": {
            "href": "http://api.augmarketing.com/xxx/users?page=2"
        },
        "last": {
            "href": "http://api.augmarketing.com/xxx/users?page=2"
        }
    },
    "_meta": {
        "totalCount": 24,
        "pageCount": 2,
        "currentPage": 0,
        "perPage": 20
    }
}
```

* add parameters page and per-page for pagination
url: api.augmarketing.com/xxx/users?page=2&per-page=3

```
{
    "items": [
        {
            "id": "5475aba021a3cc6b048b4573",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        {
            "id": "5475aba021a3cc6c048b4577",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        },
        {
            "id": "5475aba121a3cc6b048b4574",
            "email": "devinjin@augmentum.con.cn",
            "name": "12"
        }
    ],
    "_links": {
        "self": {
            "href": "http://api.augmarketing.com/xxx/users?page=2&per-page=3"
        },
        "first": {
            "href": "http://api.augmarketing.com/xxx/users?page=1&per-page=3"
        },
        "prev": {
            "href": "http://api.augmarketing.com/xxx/users?page=1&per-page=3"
        },
        "next": {
            "href": "http://api.augmarketing.com/xxx/users?page=3&per-page=3"
        },
        "last": {
            "href": "http://api.augmarketing.com/xxx/users?page=8&per-page=3"
        }
    },
    "_meta": {
        "totalCount": 24,
        "pageCount": 8,
        "currentPage": 1,
        "perPage": 3
    }
}
```

* add parameter where for filter
request url: api.augmarketing.com/xxx/users?where={"name":"aaaaa"}

```
{
    "items": [
        {
            "id": "54758ea321a3cc6c048b4575",
            "email": "devinjin@augmentum.con.cn",
            "name": "aaaaa"
        }
    ],
    "_links": {
        "self": {
            "href": "http://api.augmarketing.com/xxx/users?where=%7B%22name%22%3A%22aaaaa%22%7D&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 0,
        "perPage": 20
    }
}
```

### view(method: GET)
* url

```
GET http://{server-domain}/{module}/{controller}/{id}
```

* Response example

```
{
    "id": "54758ea321a3cc6c048b4575",
    "email": "devinjin@augmentum.con.cn",
    "name": "aaaaa"
}
```

### create(method: POST)
* url

```
POST http://{server-domain}/{module}/{controller}
```

* Parameters:
The json contains the attributes.

```
{"name":"devin","email":"devinjin@augmentum.con.cn"}
```

### update(method: PUT)
* url

```
PUT http://{server-domain}/{module}/{controller}/{id}
```

* Parameters:
The json contains the attributes.

```
{"name":"devin","email":"devinjin@augmentum.con.cn"}
```

### delete(method: DELETE)
* url

```
DELETE http://{server-domain}/{module}/{controller}/{id list}
```

The ids is seperated with ","


### Customize the actions
* Customize the exsisted actions
use function actions, unset the exsisted ones and add function action... of your own

```
    public function actions()
    {
        $actions = parent::actions();

        // disable the "delete" and "create" actions
        unset($actions['delete'], $actions['create']);
        return $actions;
    }
    
    public function actionDelete()
    {
        //... code ...
    }
```

* Add customized actions
Add function action...
The name should **NOT** be ended with letter "s"

```
    public function actionFunc()
    {
        //... code ...
    }
```
The request url will be:

```
http://{server-domain}/{module}/{controller}/{action}
```

* Handling the request
  * Get the parameters:
    If the parameters are in the query string, get it with method:

```
$value = $this->getQuery('somekey'); //returns the value
$queryArray = $this->getQuery(); //returns an array
```
    
If the parameters are in the request payload, get it in this way:

```
$postArr = $this->getParams(); //return an array
```

