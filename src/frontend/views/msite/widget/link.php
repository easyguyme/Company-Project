<?php
    $display = !empty($display) ? $display : 'left';
?>
<div class="m-link">
    <div class='m-link-layout' style='text-align:<?php echo $display?>;'>
        <?php
            // url of link
            $url = isset($linkUrl) && trim($linkUrl) !== '' ? $linkUrl : '';
            $url = stripos($url, 'http') === false || stripos($url, 'http') != 0 ? ('http://'. $url) : $url;
            // style of opening new page
            $target = strpos($url, DOMAIN) === false ? '_blank' : '_self';
        ?>
        <a target='<?php echo $target; ?>' class='m-link-font-text' href='<?php echo $url; ?>'>
            <?php echo isset($name) && trim($name) !== '' ? htmlspecialchars($name) : $defaultName; ?>
        </a>
    </div>
</div>