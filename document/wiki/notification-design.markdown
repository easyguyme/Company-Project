## Message structure
 * _id: Unique identification
 * accountId: Account it belongs to
 * title
 * content
 * sender {id, from: 'system' or 'user'}
 * to {id, target: 'account' or 'user'}
 * status: 'warning', 'error', 'success'
 * isRead: Mark the notification whether read
 * createdAt
 * readAt

## Create message sample
```php
    $message = new Message;
    $message->accountId = new \MongoId('54aa37c32736e766718b4567');
    $message->to = ['id' => new \MongoId('555a8cfa2736e79b1a8b4567'), 'target' => Message::TO_TARGET_ACCOUNT];
    $message->sender = ['id' => new \MongoId('555a8cfa2736e79b1a8b4527'), 'from' => Message::SENDER_FROM_SYSTEM];
    $message->title = "title test";
    $message->content = "<a href='http://www.baidu.com'>Baidu</a> Test";
    $message->status = Message::STATUS_ERROR;
    $message->readAt = new \MongoDate();
    $message->save();
```

## Sequence Diagrams
* Render message sequence diagram

![message](http://git.augmentum.com.cn/scrm/aug-marketing/uploads/c19e1dc99723f1d8d6328a8ace93b252/message.png)

* Update progress bar status sequence diagram

![export](http://git.augmentum.com.cn/scrm/aug-marketing/uploads/b017112aa56a8cda53105180915aa322/export.png)

 * When message is created successfully, the Tuisongbao API will be called to push message, Tuisongbao service dispatches the message to the SCRM frontend, then the frontend renders the message list according to the type and status.

backend push message to the below channel and event, and frontend listen to the channel and event to get message.

```php
channel: "presence-message-wm-global" . $accountId
event: "new_message"
data: {sender: {id: "system", from: "system"}}
```
 * When the export button is clicked by user, the export job will be created in the backend. As the file generated, the job will invoke the Tuisongbao API to push file generated progress message, Tuisongbao service dispatches the message to the frontend, then the frontend updates the status of the progress bar.

backend push message to the below channel and event, and frontend listen to the channel and event to get message.

```php
channel: "presence-message-wm-global" . $accountId
event: "export_finish"
data: {key: "556ff0712736e726058b4568"}
```