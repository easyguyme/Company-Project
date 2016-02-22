<header class="c-header">
  <span class="c-header__return" onclick={ back }></span>
  <div class="c-header__title">{ opts.title }</div>

  <script>
    var self = this;
    const C_FUNCTION = 'function';

    this.beforeHandler = this.beforeHandler || this.opts.beforeHandler;
    this.customHandler = this.customHandler || this.opts.customHandler;

    this.back = () => {
      if (self.customHandler && typeof(self.customHandler) === C_FUNCTION) {
        self.customHandler.apply(self);
      } else {
        if (self.beforeHandler && typeof(self.beforeHandler) === C_FUNCTION) {
          self.beforeHandler.apply(self);
        }
        window.history.back();
      }
    }
  </script>

</header>
