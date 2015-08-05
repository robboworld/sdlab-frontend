<div class="col-md-4 pull-right">
	<h3>
		Ключ сессии:
		<span class="text-danger"><? print $this->session()->getKey(); ?></span>
	</h3>
</div>
<div class="col-md-8">
	<h3>Редактирование сессии</h3>
</div>	
<div class="col-md-12">
	<form class="row" action="?<? print $_SERVER['QUERY_STRING'];?>" method="post">
		<input type="hidden" name="form-id" value="edit-session-form">
		<div class="form-group col-md-6">
			<label>Участники</label>
			<input type="text" class="form-control" name="session_name" placeholder="ФИО" value="<? print $this->session()->name;?>" <? if($this->session()->getUserLevel() >1 ) print "disabled";?>>
		</div>
		<div class="form-group col-md-3">
			<label>Название работы</label>
			<input type="text" class="form-control" name="session_title" placeholder="Название" value="<? print $this->session()->title;?>" <? if($this->session()->getUserLevel() >1 ) print "disabled";?>>
		</div>
		<div class="form-group col-md-3">
			<label>Срок хранения(дней)</label>
			<input type="text" class="form-control" name="session_expiry" placeholder="Срок хранения" value="<? print $this->session()->expiry;?>" size="3" <? if($this->session()->getUserLevel() >1 ) print "disabled";?>>
		</div>

		<div class="form-group col-md-12">
			<label>Комментарий</label>
			<textarea class="text-area form-control" maxlength="2000" name="session_comments" placeholder="Комментарий"><?print $this->session()->comments;?></textarea>
		</div>
		<div class="form-group col-md-offset-9 col-md-3">
			<input type="submit" class="form-control btn-success" value="Сохранить">
		</div>
	</form>
</div>

<div class="col-lg-12">
	<hr class="clearfix">
</div>
<div class="col-md-12">
	<h3>Эксперименты в этой сессии</h3>

	<? if($this->view->experiments_in_session) : ?>
	<table class="table">
		<thead>
			<td><label>#ID</label></td>
			<td><label>Дата начала / Дата окончания</label></td>
			<td><label>Название экссперимента</label></td>
			<td><label>Установка</label></td>
		</thead>
		<? foreach ($this->view->experiments_in_session as $item) : ?>
		<tr>
			<td>#<? print $item->id?></td>
			<td><? print System::dateformat($item->DateStart_exp);?> / <? if(!empty($item->DateEnd_exp)) print System::dateformat($item->DateEnd_exp);?></td>
			<td><a href="?q=experiment/view/<? print $item->id?>"><? print $item->title?></a></td>
			<td><? print (new Setup())->load($item->setup_id)->title; ?></td>
		</tr>
		<? endforeach; ?>
	</table>
	<? endif; ?>
	<div class="col-md-12 text-right">
		<a class="btn btn-primary" href="?q=experiment">Новый эксперимент</a>
	</div>
</div>
