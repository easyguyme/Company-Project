<?php
    $contentChange = array('<script>'=>'&lt;script&gt;');
    $contentChange += array('</script>'=>'&lt;/script&gt;');
    $content = strtr($content, $contentChange);
?>
<div class="m-container m-bborder">
   <div class="m-html-box m-clearfix">
       <div class="m-template-html m-mediate m-taleft">
           <span class="m-html-version m-vamiddle <?php echo (isset($content) && (!empty($content) || $content == '0') ? '' : 'm-empty-version'); ?>"><?php echo (isset($content) && (!empty($content) || $content == '0') ? $content : '您尚未输入任何Html文本'); ?></span>
       </div>
   </div>
</div> 