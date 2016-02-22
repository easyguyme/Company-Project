<?php $temp = trim($text); $temp = preg_replace('/\s(?=\s)/', '', $temp); $temp = preg_replace('/[\n\r\t]/', '', $temp); $temp = preg_replace('/&nbsp;/', '', $temp); $temp=strip_tags($temp); ?>

<div class="m-text" >
  <div class="m-text-wrapper">

      <?php if($setting == 'full'){ ?>

              <div class="m-text-size"> <?php echo (!empty($text)) ? $text : '<span class="m-text-size">您尚未输入任何文本</span>'; ?></div>

         <?php } else { ?>
              <div class="m-text-content-abbr m-text-content m-text-size m-text-content-less"> <?php echo $temp; ?></div>
              <div class="m-text-content-abbr m-text-content m-text-size m-text-content-more" style="display: none;"> <?php echo $text; ?></div>
              <div class="m-text-more"></div>
          <?php } ?>
  </div>
</div>
