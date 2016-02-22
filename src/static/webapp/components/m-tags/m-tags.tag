<m-tags>
  <section class="c-tags">
    <label if={ opts.item.key } class="c-tags__key">{ opts.item.key }：</label>
    <ul if={ opts.item.value } class="c-tags__value">
      <li each={ item in opts.item.value }>
        { item }
      </li>
    </ul>
    <span class="c-tags-nodata" show={ opts.item.value.length === 0 }>--</span>
  </section>
</m-tags>
