<?php 
// todo: can edit and delete access check, edit only for admin or owner
$show_action = true;
?>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1><?php echo L('experiment_TITLE_ALL'); ?></h1>
		</div>
	</div>
	<div>
		<a href="?q=experiment" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span>&nbsp;<?php echo L('experiment_NEW_EXPERIMENT'); ?></a>
		<?php if($this->session()->getUserLevel() == 3) :?>
		<a href="?q=sensors/view" class="btn btn-primary"><span class="fa fa-wrench"></span>&nbsp;<?php echo L('SENSORS'); ?></a>
		<?php endif; ?>

	</div>
	<form id="sdform" method="post" action="?<?php echo $_SERVER['QUERY_STRING']?>">
		<input type="hidden" name="force" value="0"/>
	<?php if(isset($this->view->content->list )) : ?>
		<table class="table">
			<thead>
			<tr>
				<?php if($this->session()->getUserLevel() == 3) :?>
				<td>
					<label><?php echo L('session_NAME'); ?></label>
				</td>
				<?php endif; ?>
				<td>
					<label><?php echo L('experiment_NAME'); ?></label>
				</td>
				<td>
					<?php echo L('experiment_DATE_START'); ?>
				</td>
				<td>
					<?php echo L('experiment_DATE_END'); ?>
				</td>
				<?php /*if($this->session()->getUserLevel() == 3) :*/?>
				<td class="text-right">
					<?php echo L('ACTION'); ?>
				</td>
				<?php /*endif;*/ ?>
			</tr>
			</thead>
			<tbody>
			<?php foreach($this->view->content->list as $item) :?>
				<tr class="row-experiment <?php
						if(empty($item->DateEnd_exp) && !empty($item->DateStart_exp))
						{
							echo 'warning';
						}
						elseif (!empty($item->DateEnd_exp))
						{
							echo 'success';
						}
					?>">
					<?php
						if($this->session()->getUserLevel() == 3) :
							$user = (new Session)->load($item->session_key);
					?>
					<td>
						<?php echo htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');?>
					</td>
					<?php endif; ?>
					<td>
						<a href="/?q=experiment/view/<?php echo $item->id; ?>">
							<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</td>
					<td>
						<?php if(!empty($item->DateStart_exp)) echo System::dateformat('@'.$item->DateStart_exp, System::DATETIME_FORMAT1, 'now'); ?>
					</td>
					<td>
						<?php if(!empty($item->DateEnd_exp)) echo System::dateformat('@'.$item->DateEnd_exp, System::DATETIME_FORMAT1, 'now'); ?>
					</td>

					<td class="text-right">
						<a href="/?q=experiment/edit/<?php echo $item->id; ?>" class="btn btn-sm btn-default btn-info"><span class="glyphicon glyphicon-pencil"></span></a>
						<?php if($this->session()->getUserLevel() == 3) :?>
						<button type="button" class="experiment-delete-btn btn btn-sm btn-danger" data-experiment="<?php echo (int)$item->id; ?>"><span class="glyphicon glyphicon-remove"></span></button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
		<div class="sensors-list">
		</div>
		<div class="row">
			<?php if($this->session()->getUserLevel() == 3) :?>
			<div class="col-md-4 text-left">
				<a href="javascript:void(0)" id="sensors-rescan" class="btn btn-primary"><?php echo L('sensor_REFRESH_LIST'); ?></a>
			</div>
			<?php endif; ?>
		</div>
	</form>
</div>