<form-items>
  <form class="c-form-items" onsubmit={ submit }>
    <ul class="c-form-items__elems">
      <form-group class="c-form-items__elems__elem" each= { items }></form-group>
    </ul>
  </form>
  <script>
    var self, defaultValidateFuc;
    const C_REQUIRED_TIP = '请填写此字段';
    const C_FUNCTION = 'function';

    self = this;
    this.items = this.items || this.opts.items;
    this.submitHandler = this.submitHandler || this.opts.submitHandler;

    defaultValidateFuc = (value) => {
      var msg = '';
      if (!value) {
        msg = C_REQUIRED_TIP
      }
      return msg;
    }

    this.validate = () => {
      var formGroupTags = self.tags['form-group'], cansubmit = true;

      for (var i = 0, length = self.items.length; i < length; i++) {
        var item, value, curFormGroupTag, validateMsg = '';
        item = self.items[i];
        curFormGroupTag = formGroupTags[i];
        value = self[item.name].value;
        item.value = value;
        curFormGroupTag.value = value;

        if (item.validateHandle && typeof(item.validateHandle) === C_FUNCTION) {
          if (item.type === 'text' && item.subtype === 'location') {
            value = item.location
          }

          validateMsg = item.validateHandle(value);
        } else if(item.required) {
          validateMsg = defaultValidateFuc(value);
        }

        if (!!validateMsg) {
          item.errortip = validateMsg;
          curFormGroupTag.showError(curFormGroupTag, validateMsg);
          cansubmit = false;
        }
      };

      return cansubmit;
    }

    this.removeGroupTip = (name) => {
      var formGroupTags = self.tags['form-group'];

      for (var i = 0, length = self.items.length; i < length; i++) {
        var item, curFormGroupTag;
        item = self.items[i];

        if (item.name && item.name === name) {
          curFormGroupTag = formGroupTags[i];

          item.errortip = '';
          curFormGroupTag.restore(curFormGroupTag);
        }
      }
    }

    this.submit = (e) => {
      if (self.submitHandler) {
        self.submitHandler(e);
      }
    }
  </script>
</form-items>
