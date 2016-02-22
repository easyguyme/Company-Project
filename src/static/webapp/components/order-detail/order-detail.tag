<order-detail>
  <section class="c-order-detail { bottom: serviceProvider }">

    <!-- service information -->
    <title-panel title={ service.title }>
      <kv-list items={ service.items }></kv-list>
    </title-panel>

    <!-- order information -->
    <title-panel title={ order.title }>
      <kv-list items={ order.items }></kv-list>
    </title-panel>

    <!-- evalution -->
    <title-panel if={ evaluation } title={ evaluation.title }>
      <ul class="c-order-detail__evalution">
        <li each={ evaluation.evaluation } class="c-order-detail__evalution__item clearfix">
          <div class="c-order-detail__evalution__item__title text-overflow">{ name }</div>
          <div class="c-order-detail__evalution__item__stars">
            <i each={ value, i in [1, 1, 1, 1, 1] } class="c-order-detail__evalution__item__stars__icon { active: i <= score - 1}"></i>
          </div>
        </li>
      </ul>
      <div>{ evaluation.detail }</div>
    </title-panel>

    <!-- refund information -->
    <title-panel if={ refund } title={ refund.title }>
      <p class="c-order-detail__text" each={ text, i in refund.texts }>{ text }</p>
    </title-panel>

    <!-- service provider information -->
    <div class="c-order-detail__providerwrapper" if={ serviceProvider }>
      <panel padding="true">
        <section class="c-order-detail__provider clearfix">
          <div class="c-order-detail__provider__title">{ serviceProvider.title }：</div>
          <div class="c-order-detail__provider__content">{ serviceProvider.name }  { serviceProvider.telephone }</div>
          <a href="tel:{ serviceProvider.telephone }" class="c-order-detail__provider__icon"></a>
        </section>
      </panel>
    </div>

  </section>

  let self = this
  self.service = opts.service
  self.order = opts.order

</order-detail>
