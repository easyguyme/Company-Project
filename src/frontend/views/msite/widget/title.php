<?php
if (!empty($link)) {
?>
<a class="m-title" href="
<?= stripos($link, 'http') === false || stripos($link, 'http') != 0 ? ('http://'. $link) : $link; ?>
"
<?= !empty($link) && strpos($link, DOMAIN) === false ? 'target="_blank"' : ''; ?>>
<?php
} else {
?>
<div class="m-title m-rel">
<?php
}
?>
<?php
if ('dot' === $style) {
?>
    <div class="m-dot m-bgcolor"></div>
<?php
}
?>
    <div class="m-title-style m-rel m-<?= $style; ?>-style
    <?= ('plain' == $style || 'dot' == $style) ? '' : 'm-bgcolor'; ?>">
        <span class="m-title-content">
        <?= empty($name) && $name != '0'? '未输入标题' : htmlspecialchars($name, ENT_QUOTES);?>
        </span>
        <?php
        if ('flag' === $style || 'arrow' === $style) {
        ?>
            <div class="m-title-tail m-bg-cover"></div>
        <?php
        } else if ('dot' === $style) {
        ?>
            <div class="m-border m-bgcolor"></div>
        <?php
        }
        ?>
    </div>
<?= !empty($link) ? '</a>' : '</div>'; ?>
