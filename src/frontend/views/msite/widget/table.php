<?php
    $contentChange = array('<table'=>'<table class="m-table"');
    $contentChange += array('valign="top"'=>'class="m-vatop"');
    $contentChange += array('valign="middle"'=>'class="m-vamiddle"');
    $contentChange += array('valign="bottom"'=>'class="m-vabottom"');
    $content = strtr($content, $contentChange);
?>
<div class="m-tal-height m-bborder">
    <div class="m-table-box">
        <?php echo $content; ?>
    </div>
</div>
