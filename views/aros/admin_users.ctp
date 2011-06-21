<?php
echo $this->element('design/header');
?>

<?php
echo $this->element('aros/links');
?>

<?php
echo $this->Form->create('User', array('url' => '/' . $this->params['url']['url']));
echo __d('acl', 'name', true);
echo '<br/>';
echo $this->Form->input($user_display_field, array('label' => false, 'div' => false));
echo ' ';
echo $this->Form->end(array('label' =>__d('acl', 'filter', true), 'div' => false));
echo '<br/>';
?>
<table border="0" cellpadding="5" cellspacing="2">
<tr>
	<?php
	$column_count = 1;
	
	$headers = array($paginator->sort(__d('acl', 'name', true), $user_display_field));
	
	foreach($roles as $role)
	{
	    $headers[] = $role[$role_model_name][$role_display_field];
	    $column_count++;
	}
	
	echo $this->Html->tableHeaders($headers);
	
	?>
	
</tr>
<?php
foreach($users as $user)
{
    $style = isset($user['Aro']) ? '' : ' class="line_warning"';
    
    echo '<tr' . $style . '>';
    echo '  <td>' . $user[$user_model_name][$user_display_field] . '</td>';
    
    foreach($roles as $role)
	{
	   if(isset($user['Aro']) && $role[$role_model_name]['id'] == $user[$user_model_name][$role_fk_name])
	   {
	       echo '  <td>' . $this->Html->image('/acl/img/design/tick.png') . '</td>';
	   }
	   else
	   {
	   	   $title = __d('acl', 'Update the user role', true);
	       echo '  <td>' . $this->Html->link($this->Html->image('/acl/img/design/tick_disabled.png'), '/admin/acl/aros/update_user_role/user:' . $user[$user_model_name]['id'] . '/role:' . $role[$role_model_name]['id'], array('title' => $title, 'alt' => $title, 'escape' => false)) . '</td>';
	   }
	}
	
    //echo '  <td>' . (isset($user['Aro']) ? $this->Html->image('/acl/img/design/tick.png') : $this->Html->image('/acl/img/design/cross.png')) . '</td>';
    
    echo '</tr>';
}
?>
<tr>
	<td class="paging" colspan="<?php echo $column_count ?>">
		<?php echo $paginator->numbers(); ?>
	</td>
</tr>
</table>


<?php
if($missing_aro)
{
?>
    <div style="margin-top:20px">
    
    <p class="warning"><?php echo __d('acl', 'Some users AROS are missing. Click on a role to assign one to a user.', true) ?></p>
    
    <?php
    //echo $this->Html->link(___('generate missing AROs', true), array('plugin' => 'acl', 'controller' => 'aros', 'action' => 'generate_missing'));
    ?>
    
    </div>

<?php
}
?>

<?php
echo $this->element('design/footer');
?>