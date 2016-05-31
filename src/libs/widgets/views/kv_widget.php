<table border="<?php echo $this->border ?>" cellspacing="0" cellpadding="5" width="100%" style="font-size:12px;">
    <thead></thead>
    <tbody>
        <?php foreach ($arr as $k => $v): ?>
            <?php $is_color = isset($this->colors[$k]) ? true : false; ?>
            <tr>
                <td style="<?php echo $this->getKeyStyle($is_color) ?>"><?php echo $k; ?></td>
                <td style="<?php echo $this->getValStyle($is_color) ?>"><?php echo is_array($v) ? libs\widgets\KvWidget::widget(['arr' => $v, 'wrap' => false]) : $v; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
