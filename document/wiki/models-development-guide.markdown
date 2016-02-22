all the model class should extends the BaseModel in namespace \backend\components

```
<?php
namespace backend\models;

use backend\components\BaseModel;

class User extends BaseModel
{
    //...code...
}
```

* If you want use `isDeleted` and `updatedAt` fields, please use `backend\components\BaseModel`， otherwise, youcan use `backend\components\PlainModel`.
* `BaseModel` has the `isDeleted`, `updatedAt` and `createdAt` fields.
* `PlainModel` only has the `createdAt` field.

# collectionName()
specifies the collection name in mongodb

```
public static function collectionName()
{
    return 'user';
}
```

# attributes() and safeAttributes()
Put the attributes of the model in it. If it is safe for update and create, put it in safeAttributes()
```
public function attributes()
{
    return array_merge(parent::attributes(), ['_id', 'name', 'email', 'count', 'status']);
}

public function safeAttributes()
{
    return array_merge(parent::safeAttributes(), ['name', 'email', 'count']);
}
```

# Scenarios
* A model may be used in different scenarios. In different scenarios, a model may use different business rules and logic.
* For example, the User model requires the username, password, email and other personal information during sign up while only username and password are required during sign in. "SignIn" and "SignUp" are so-called Scenarios.
* Two methods to specify the scenarios

```
// scenario is set as a property
$model = new User;
$model->scenario = 'login';

// scenario is set through configuration
$model = new User(['scenario' => 'login']);
```

The scenarios() method returns an array whose keys are the scenario names and values the corresponding active attributes. An active attribute can be massively assigned and is subject to validation.

```
public function scenarios()
{
    return [
        'login' => ['username', 'password'],
        'register' => ['username', 'email', 'password'],
    ];
}
```

# Rules
To declare validation rules associated with a model, override the rules() method by returning the rules that the model attributes should satisfy.

```
 public function rules()
{
    return array_merge(parent::rules(), [
        [['name', 'email'], 'required'],
        ['email', 'email'],
        ['count', 'default', 'value' => 10]
    ]);
}
```

```
public function rules()
{
    return array_merge(parent::rules(), [
        // username, email and password are all required in "register" scenario
        [['username', 'email', 'password'], 'required', 'on' => 'register'],

        // username and password are required in "login" scenario
        [['username', 'password'], 'required', 'on' => 'login'],
    ]);
}
```

# fields
* A field is simply a named element in the array that is obtained by calling the yii\base\Model::toArray() method of a model.
* By overriding fields() and/or extraFields(), you may specify what data, called fields, in the resource can be put into its array representation. The difference between these two methods is that the former specifies the default set of fields which should be included in the array representation, while the latter specifies additional fields which may be included in the array if an end user requests for them via the expand query parameter. 
* The id is in the field by default

```
// explicitly list every field, best used when you want to make sure the changes
// in your DB table or model attributes do not cause your field changes (to keep API backward compatibility).
public function fields()
{
    return array_merge(parent::fields(), [
        // field name is the same as the attribute name
        'id',

        // field name is "email", the corresponding attribute name is "email_address"
        'email' => 'email_address',

        // field name is "name", its value is defined by a PHP callback
        'name' => function () {
            return $this->first_name . ' ' . $this->last_name;
        },
    ]);
}
```

#CRUD

## queries
follow the example:

```php
User::findOne(['_id' => '54757dcae9c2fb69208b4567']);

$id = new \MongoId('54757dcae9c2fb69208b4567');
User::findOne(['_id' => $id]);

User::findAll([]);

User::findAll(['name' => 'potter', 'email' => 'harrysun@126.com']);
```

If you want use Query, you can follow the example:

```php
use backend\components\Query;

$query = new Query;
// compose the query
$query->select(['_id', 'email'])
    ->from('user')
    ->limit(10);
// execute the query
$rows = $query->all();
```

## update
follow the example:

```
User::updateAll(['email' => 'devinjin@augmentum.com.cn'], ['name' => 'devin']);

User::updateAll(['$inc' => ['codeLines' => 100]], ['in', '_id', $finishedIdList]);

Task::updateAll(['$set' => ['status' => 'finished'], '$inc' => ['remains' => '-1']]);
```

## delete

follow the example:

```php
$user = User::findOne(['_id' => '54757dcae9c2fb69208b4567']);
$user->delete();

User::deleteAll(['name' => 'potter']);

$id = new \MongoId('54757dcae9c2fb69208b4567');
User::deleteAll(['_id' => $id]);
```

## save

follow the example

```
$user = new User;
$user->name = "devin";
$user->email = "devinjin@augmentum.com.cn";
$user->save();
```

# common fields
## createdAt:
MongoDate, the time the record is created.

## updatedAt:
MongoDate, the time of the last updated.

## isDeleted:
For logical deletion.

For most cases the operations on the common fileds is not essential for they are managed in the BaseModel



