<?php
echo '<div class="error_page_message">';

echo '	<span class="error">' . $message . '</span>';
echo '	<p>&nbsp;</p>';
echo '	<p>&nbsp;</p>';
echo '	<p>' . $this->Html->link(___('go to homepage', true), '/') . '</p>';

echo '</div>';
?>