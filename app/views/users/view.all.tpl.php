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
		<table class="table table-condensed table-striped">
			<thead>
			<tr>
				<th class="text-left">
					<?php echo L::session_ID; ?>
				</th>
				<th>
					<?php echo L::MEMBER; ?>
				</th>
				<th>
					<?php echo L::NAME; ?>
				</th>
				<th>
					<?php echo L::session_KEY; ?>
				</th>
				<th class="text-left">
					<?php echo L::session_DATE_START; ?>
				</th>
				<th class="text-left">
					<?php echo L::session_DATE_END; ?>
				</th>
				<th>
					<?php echo L::COMMENT; ?>
				</th>
				<th class="text-right">
					<?php echo L::session_EXPIRES_TIME_DAYS; ?>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($this->view->content->list as $item) :?>
				<tr class="row-session <?php
						if($item->id == 1 ) // TODO: Test for user level field for admin/registered and other
						{
							echo 'danger';
						}
						elseif ($item->id > 1)
						{
							//echo 'success';
						}
						else
						{
							//echo 'info';
						}

						// TODO: check expire
						/*
						if($item->expiry
						{
							echo 'danger';
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
						<?php if(!empty($item->DateStart)) echo System::dateformat('@'.$item->DateStart, System::DATETIME_FORMAT1, 'now'); ?>
					</td>
					<td class="text-left">
						<?php if(!empty($item->DateEnd)) echo System::dateformat('@'.$item->DateEnd, System::DATETIME_FORMAT1, 'now'); ?>
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