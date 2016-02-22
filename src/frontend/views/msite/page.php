<?php
if (!empty($components)) {
    foreach ($components as $component) {
        if (isset($component['name']) && !empty($component['jsonConfig'])) {
            echo $this->renderAjax('widget/' . $component['name'], $component['jsonConfig'], true);
        }
    }
}
?>
<script type="text/javascript">
    var options = <?php echo json_encode($signPackage);?>;
    var page = <?php echo json_encode($page);?>;
</script>
