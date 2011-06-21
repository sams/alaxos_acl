<?php
echo $this->element('design/header', array('no_acl_links' => true));
?>

<div class="error">
	
	<?php
	echo '<p class="warning">' . __d('acl', 'Some controllers have been modified, resulting in actions that are not referenced as ACO in the database.', true) . ' :</p>';
	
	echo '<p>';
	echo $this->Html->nestedList($missing_aco_nodes);
	echo '</p>';
	
	echo '<p>';
	echo __d('acl', 'You can update the ACOs by clicking on the following link', true) . ' : ';
	echo $this->Html->link(__d('acl', 'Build missing ACOs', true), '/admin/acl/acos/build_acl/run');
	echo '</p>';
	
	echo '<p>';
	echo __d('acl', 'Please be aware that this message will appear only once. But you can always rebuild the ACOs by going to the ACO tab.', true);
	echo '</p>';
	?>
	
</div>

<?php
echo $this->element('design/footer');
?>