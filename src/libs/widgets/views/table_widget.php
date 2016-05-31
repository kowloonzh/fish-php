<table width="<?php echo $this->width; ?>" border="<?php echo $this->border; ?>" cellspacing="<?php echo $this->cellspacing; ?>" cellpadding="<?php echo $this->cellpadding; ?>" style="<?php echo $this->style; ?>">
    <thead>
        <tr>
            <?php foreach ($this->headers as $k => $head): ?>
                <th><?php echo $head; ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($this->contents)): ?>
            <?php foreach ($this->contents as $k => $content): ?>
                <tr>
                    <?php foreach ($this->headers as $h => $v): ?>
                    <td align='center' style="<?php echo isset($content['colors'])?'color:'.$this->tdColor.';':''?>"><?php echo $content[$h]; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>