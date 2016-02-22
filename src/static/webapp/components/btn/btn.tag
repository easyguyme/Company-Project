<btn>
  <button if={ !opts.link } class="c-btn c-btn--{ opts.type } { disable ? 'c-btn--disabled' : ''} { hide ? 'c-btn--hidden' : ''} { fixed ? 'c-btn--fixed' : ''}" onclick="{ clickFuc }">{ opts.text }</button>
  <a if={ opts.link } class="c-btn c-btn--{ opts.type } { hide ? 'c-btn--hidden' : ''}" href="{ opts.link }">{ opts.text }</a>

  <script>
    var self = this;
    const C_FUNCTION = 'function';
    this.clickHandler = this.clickHandler || this.opts.clickHandler;
    this.disable = this.disable || this.opts.disable;
    this.hide = this.hide || this.opts.hide;
    this.fixed = this.fixed || this.opts.fixed;

    this.clickFuc = (e) => {
      if (!self.disable) {
        if (self.clickHandler && typeof self.clickHandler === C_FUNCTION) {
          self.clickHandler(e);
        }
      }
    }

    this.on('updated', function() {
      self.clickHandler = self.clickHandler || self.opts.clickHandler || self.opts['click-handler'];
    })

  </script>
</btn>
