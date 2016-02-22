<loading class="c-loading"  if={ status } ontouchmove={ touchmove }>
  <div class="c-loading-icon"></div>

  <script>
    var self = this, _init;

    _init = () => {
      self.status = self.status || self.opts.status;
    }

    this.touchmove = (e) => {
      e.stopPropagation();
      e.preventDefault();
    }

    _init();

  </script>
</loading>
