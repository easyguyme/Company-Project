<radio>
  <label class="c-radio { check ? 'c-radio--checked' : '' } { disable ? 'c-radio--disabled' : '' }">
    <input style="display: none" type="radio" name={ name } value={ valued } onclick={ checkFuc } />
  </label>

  <script>
    var self = this;
    var parent = this.parent;

    this.name = this.name || this.opts.name;
    this.valued = this.valued || this.opts.valued;
    this.check = this.check || this.opts.check;
    this.disable = this.disable || this.opts.disable;
    this.group = this.group || this.opts.group || 'radio-groups';

    this.checkFuc = (e) => {
      if (self.disable) {
        return;
      }

      if (parent && parent.checkHandle && typeof(parent.checkHandle) === 'function') {
        if (!self.valued) {
          self.check = true;
        }
        parent.checkHandle(self.valued, self.group, e);
      }
    }

  </script>
</radio>
