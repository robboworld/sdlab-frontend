<?php 
// todo: can edit and delete access check, edit only for admin or owner
?>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><?php echo L::users_TITLE_ALL; ?></h1>
		</div>
	</div>
	<form id="sdform" method="post" action="?<?php echo $_SERVER['QUERY_STRING']?>" >
	<?php if(isset($this->view->content->list )) : ?>
		<table class="table">
			<thead>
			<tr>
				<td class="text-left">
					<?php echo L::session_ID; ?>
				</td>
				<td>
					<?php echo L::MEMBER; ?>
				</td>
				<td>
					<?php echo L::NAME; ?>
				</td>
				<td>
					<?php echo L::session_KEY; ?>
				</td>
				<td class="text-left">
					<?php echo L::session_DATE_START; ?>
				</td>
				<td class="text-left">
					<?php echo L::session_DATE_END; ?>
				</td>
				<td>
					<?php echo L::COMMENT; ?>
				</td>
				<td class="text-right">
					<?php echo L::session_EXPIRES_TIME_DAYS; ?>
				</td>
			</tr>
			</thead>
			<tbody>
			<?php foreach($this->view->content->list as $item) :?>
				<tr class="row-session <?php
						if($item->id == 1 ) // TODO: Test for user level field for admin/registered and other
						{
							echo 'error';
						}
						elseif ($item->id > 1)
						{
							//echo 'success';
						}
						else
						{
							//echo 'warning';
						}

						// TODO: check expire
						/*
						if($item->expiry
						{
							echo 'error';
						}
						*/
					?>">
					<?php
						//if($this->session()->getUserLevel() == 3): endif;
						//$user = (new Session)->load($item->session_key);
					?>
					<td class="text-left">
						<?php echo (int)$item->id; ?>
					</td>
					<td>
						<?php echo htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8'); ?>
					</td>
					<td>
						<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
					</td>
					<td>
						<?php echo htmlspecialchars($item->session_key, ENT_QUOTES, 'UTF-8'); ?>
					</td>
					<td class="text-left">
						<?php if(!empty($item->DateStart)) echo System::dateformat('@'.$item->DateStart, System::DATETIME_FORMAT2, 'now'); ?>
					</td class="text-left">
					<td>
						<?php if(!empty($item->DateEnd)) echo System::dateformat('@'.$item->DateEnd, System::DATETIME_FORMAT2, 'now'); ?>
					</td>
					<td>
						<?php echo htmlspecialchars($item->comments, ENT_QUOTES, 'UTF-8'); ?>
					</td>
					<td class="text-right">
						<?php echo (((int)$item->expiry > 0) ? '' : (int)$item->expiry); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	</form>
</div>