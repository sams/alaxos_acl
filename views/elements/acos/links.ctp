<div id="acos_link" class="acl_links">
<?php
$selected = isset($selected) ? $selected : $this->params['action'];

$links = array();
$links[] = $this->Html->link(__d('acl', 'Build actions ACOs', true), '/admin/acl/acos/build_acl', array('class' => ($selected == 'admin_build_acl' )? 'selected' : null));
$links[] = $this->Html->link(__d('acl', 'Clear actions ACOs', true), '/admin/acl/acos/empty_acos', array(array('confirm' => __d('acl', 'are you sure ?', true)), 'class' => ($selected == 'admin_empty_acos' )? 'selected' : null));

echo $this->Html->nestedList($links, array('class' => 'acl_links'));
?>
</div>