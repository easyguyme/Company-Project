<kv-list>
  <ul class="c-kv-list">
    <li class="c-kv-list__item" each= { opts.items }>
      <div class="c-kv-list__item__key">{ key }：</div>
      <div class="c-kv-list__item__value">
        { value || '-' }
        <div if={ tip } class="c-kv-list__item__value__illustration">{ tip }</div>
      </div>
    </li>
  </ul>
</kv-list>
