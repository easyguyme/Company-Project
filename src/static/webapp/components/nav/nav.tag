<nav class="swiper-container">
  <ul class="c-nav swiper-wrapper">
    <li each={ nav, index in opts.navs } class="c-nav__item swiper-slide { active: nav.active }" onclick={ clickNav }>{ nav.name }</li>
  </ul>

  var self = this

  self.on('mount', () => {
    var tabs = new Swiper('.swiper-container', {
      freeMode: true,
      slidesPerView: 'auto'
    })
  })

  self.clickNav = (e) => {
    for(let index = 0, len = opts.navs.length; index < len; index++) {
      opts.navs[index].active = index === e.item.index
    }
    opts.clickNav(e.item.nav)
  }
</nav>
