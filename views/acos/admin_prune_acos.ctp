<?php
echo $this->element('design/header');
?>

<?php
echo $this->element('acos/links');
?>

<?php
if($run)
{
    if(count($logs) > 0)
    {
        echo '<p>';
        echo __d('acl', 'The following actions ACOs have been pruned', true);
        echo '<p>';
        echo $this->Html->nestedList($logs);
    }
    else
    {
        echo '<p>';
        echo __d('acl', 'There was no ACOs to prune', true);
        echo '</p>';
    }
}
else
{
    echo '<p>';
    echo __d('acl', 'This page allows you to prune superfluous ACOs (any ACO pointing to controllers and actions that have been removed).', true);
    echo '</p>';

    echo '<p>';
    echo __d('acl', 'Clicking the link will not change or remove permissions for existing actions ACOs.', true);
    echo '</p>';

    echo '<p>';
    echo $this->Html->link(__d('acl', 'Prune', true), '/admin/acl/acos/prune_acos/run');
    echo '</p>';
}

echo $this->element('design/footer');
?>