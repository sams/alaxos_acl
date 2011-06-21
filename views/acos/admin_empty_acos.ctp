<?php
echo $this->element('design/header');
?>

<?php
echo $this->element('acos/links');
?>

<?php

    echo '<p>';
    echo __d('acl', 'This page allows you to clear all actions ACOs.', true);
    echo '</p>';
    
    echo '<p>';
    echo __d('acl', 'Clicking the link will destroy all existing actions ACOs and associated permissions.', true);
    echo '</p>';
    
    echo '<p>';
    echo $this->Html->link($this->Html->image('/acl/img/design/cross.png') . ' ' . __d('acl', 'Clear ACOs', true), '/admin/acl/acos/empty_acos/run', array('confirm' => __d('acl', 'Are you sure you want to destroy all existing ACOs ?', true), 'escape' => false));
    echo '</p>';

echo $this->element('design/footer');
?>