<popselect>
  <div class="c-popselect { disable ? 'c-popselect--disabled': '' }" onclick={ showOptions }>
    <span class="c-popselect__text">{ value ? value : defaultvalue ? defaultvalue : ''}</span>
    <img class="c-popselect__icon" src={ icon ? icon : '/images/mobile/components/detail.png'}/>
  </div>

  <script>
    var self = this;
    const C_DEFAULT_TEXT = '请选择';
    const C_FUNCTION = 'function';

    this.defaultvalue = this.defaultvalue || this.opts.defaultvalue || C_DEFAULT_TEXT;
    this.value = this.value || this.opts.value;
    this.options = this.options || this.opts.options;
    this.optionstag = this.optionstag || this.opts.optionstag;
    this.disable = this.disable || this.opts.disable || !this.options || this.options.length === 0;
    this.pickedHandler = this.pickedHandler || this.opts.pickedHandler;

    this.showOptions = () => {
      if (self.optionstag && !this.disable) {
        var updateItem = {
          show: true,
          options: self.options,
          submitHandler: self.pickedFuc,
          picked: null
        }

        if (self.value !== C_DEFAULT_TEXT) {
          updateItem.picked = self.value
        }
        self.optionstag.update(updateItem);
        self.optionstag.updatePickedOption();
      }
    }

    this.pickedFuc = (value) => {
      if (self.pickedHandler && typeof self.pickedHandler === C_FUNCTION) {
        self.pickedHandler(value);
      }
      self.value = value;
      self.update();
    }

  </script>
</popselect>
