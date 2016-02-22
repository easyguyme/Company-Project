<img-panel>
  <panel class="c-img-panel { isHorizonal ? 'c-img-panel--horizonal' : ''}">
    <div class="c-img-panel__img" style="background-image:url({ img })">
      <div class="loading c-img-panel__img__loading" show={ !img }></div>
    </div>
    <div class="c-img-panel__bottom">
      <yield/>
    </div>
  </panel>

  let self = this
  self.img = opts.img
  self.isHorizonal = opts['is-horizonal'] || false

  self.on('updated', function() {
    obj = {
      img: opts.img || self.img,
      isHorizonal: opts['is-horizonal'] || self.isHorizonal
    }
    self.update(obj)
  })

</img-panel>
