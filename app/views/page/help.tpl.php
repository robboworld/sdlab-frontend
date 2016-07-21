<h3><? echo L::HELP_CAPTION; ?></h3>
<p>
	<?php echo L::HELP_TEXT; ?>

	<?php if (isset($this->app->config['lab']['admin_key'])) :
		//echo '<br/>' . L::HELP_TEXT_ADMIN(htmlspecialchars($this->app->config['lab']['admin_key'], ENT_QUOTES, 'UTF-8'));
	endif; ?>
</p>