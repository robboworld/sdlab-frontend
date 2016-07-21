<div class="col-md-offset-1 col-md-6">
	<h3><? echo L::session_TITLE_EDIT; ?></h3>
</div>
<div class="col-md-4 pull-right">
	<h3>
		<? echo L::session_KEY; ?>:
		<span class="text-danger"><? print htmlspecialchars($this->session()->getKey(), ENT_QUOTES, 'UTF-8'); ?></span>
	</h3>
</div>
<div class="col-md-offset-1 col-md-10">
	<form class="row" action="?<? print $_SERVER['QUERY_STRING'];?>" method="post">
		<input type="hidden" name="form-id" value="edit-session-form">
		<div class="form-group col-md-6">
			<label><? echo L::MEMBER; ?></label>
			<input type="text" class="form-control" name="session_name" placeholder="<? echo L::FULL_NAME; ?>" value="<? print htmlspecialchars($this->session()->name, ENT_QUOTES, 'UTF-8');?>" <? if($this->session()->getUserLevel() >1 ) print "disabled";?>/>
		</div>
		<div class="form-group col-md-3">
			<label><? echo L::session_WORK_NAME; ?></label>
			<input type="text" class="form-control" name="session_title" placeholder="<? echo L::TITLE; ?>" value="<? print htmlspecialchars($this->session()->title, ENT_QUOTES, 'UTF-8');?>" <? if($this->session()->getUserLevel() >1 ) print "disabled";?>/>
		</div>
		<div class="form-group col-md-3">
			<label><? echo L::session_EXPIRES_TIME_DAYS; ?></label>
			<input type="text" class="form-control" name="session_expiry" placeholder="<? echo L::session_EXPIRES_TIME; ?>" value="<? print htmlspecialchars($this->session()->expiry, ENT_QUOTES, 'UTF-8');?>" size="3" <? if($this->session()->getUserLevel() >1 ) print "disabled";?>/>
		</div>

		<div class="form-group col-md-12">
			<label><? echo L::COMMENT; ?></label>
			<textarea class="text-area form-control" maxlength="2000" name="session_comments" placeholder="<? echo L::COMMENT; ?>"><?print htmlspecialchars($this->session()->comments, ENT_QUOTES, 'UTF-8');?></textarea>
		</div>

		<div class="col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4 text-center">
			<div class="btn-group" style="float:none;">
				<input type="submit" class="form-control btn btn-success" value="<? echo L::SAVE; ?>"/>
			</div>
		</div>
	</form>
</div>
<? if($this->session()->getUserLevel() == 3) : ?>

<div class="col-md-offset-1 col-md-10">
	<a href="?q=users/list" class="btn btn-primary">
		<span class="glyphicon glyphicon-list"></span>&nbsp;<? echo L::users_LIST; ?>
	</a>
</div>
<? endif; ?>

<?
/*
<div class="col-lg-12">
	<hr class="clearfix">
</div>
*/
?>
<div class="col-md-offset-1 col-md-10">
	<h3><? echo L::session_EXPERIMENTS_IN_SESSION; ?></h3>

	<? if(isset($this->view->experiments_in_session) && !empty($this->view->experiments_in_session)) : ?>
	<table class="table">
		<thead>
			<tr>
				<td><label>#ID</label></td>
				<td><label><? echo L::experiment_DATE_START; ?> / <? echo L::experiment_DATE_END; ?></label></td>
				<td><label><? echo L::experiment_NAME; ?></label></td>
				<td><label><? echo L::SETUP; ?></label></td>
			</tr>
		</thead>
		<tbody>
			<? foreach ($this->view->experiments_in_session as $item) : ?>
			<tr>
				<td>#<? print (int)$item->id?></td>
				<td><? if(!empty($item->DateStart_exp)) { print System::dateformat('@'.$item->DateStart_exp, System::DATETIME_FORMAT2, 'now'); } ?> / <? if(!empty($item->DateEnd_exp)) { print System::dateformat('@'.$item->DateEnd_exp, System::DATETIME_FORMAT2, 'now'); } ?></td>
				<td><a href="?q=experiment/view/<? print (int)$item->id?>"><? print htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8')?></a></td>
				<td><? if ($setup = (new Setup())->load($item->setup_id)) {print htmlspecialchars($setup->title, ENT_QUOTES, 'UTF-8');} ?></td>
			</tr>
			<? endforeach; ?>
		</tbody>
	</table>
	<? endif; ?>
	<div class="col-sm-offset-4 col-sm-4 col-md-offset-4 col-md-4 text-center">
		<div class="btn-group" style="float: none;">
			<a class="btn btn-primary" href="?q=experiment"><? echo L::experiment_NEW_EXPERIMENT; ?></a>
		</div>
	</div>

	<div class="col-md-12 text-right">
	</div>
</div>
