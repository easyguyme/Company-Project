## wmFileUpload
The `wmFileUpload` directive for upoload picture to qiniu server and return url.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<div wm-file-upload
    ng-model=""
    [max-size=""]
    [process-bar=""]
    [callback=""]
    [pic-info=""]>
</div>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ngModel                 | `string`      |        | Assignable string to data-bind to.
maxSize(*optional*)     | `number`      | 300    | The biggest size allows to upload pictures.
processBar(*optional*)  | `boolean`     |        | Whether to display progress bar.
callback(*optional*)    | `expression`  |        | Angular expression to be executed when the picture uploaded to qiniu success.
picInfo(*optional*)     | `boolean`      |        | Upload multiple pictures when 'picInfo' is true.

---

## Example
html
```
<div wm-file-upload
  ng-model="upload.image"
  max-size="upload.maxsize"
  pic-info="upload.picInfo"
  process-bar="upload.processBar"
  callback="upload.callback">
</div>
```

coffee
```
  upload =
    image: ""
    maxsize: 100
    picInfo: true
    processBar: true
    callback: ->
      console.log 'callback'
```
