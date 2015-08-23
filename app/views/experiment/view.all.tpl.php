<style>
.exp-string .remove-button,
.exp-string .edit-button {
  background: white;
  color: white;
  border: none;
  box-shadow: none;
}
.exp-string .edit-button {
  transition-property: background;
  transition-duration: 1s;
}
.exp-string .remove-button {
  transition-property: background;
  transition-duration: 1.2s;
}
.exp-string:hover .remove-button {
  background: #419641;
}
.exp-string:hover .edit-button {
  background: #428bca;
}
</style>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1>
				<span>Все эксперименты</span>
				<a href="?q=experiment" class="btn btn-primary">Новый эксперимент</a>
			</h1>
		</div>
	</div>
	<? if(isset($this->view->content->list )) : ?>
		<table class="table">
			<thead>
				<? if($this->session()->getUserLevel()  == 3) :?>
				<td>
					<label>Название сессии</label>
				</td>
				<? endif; ?>
				<td>
					<label>Название эксперимента</label>
				</td>
				<td>
					Дата начала
				</td>
				<td>
					Дата завершения
				</td>
			</thead>
			<? foreach($this->view->content->list as $item) :?>
				<tr class="exp-string 
					<?
						if(empty($item->DateEnd_exp) && !empty($item->DateStart_exp))
						{
							print 'warning';
						}
						elseif (!empty($item->DateEnd_exp))
						{
							print 'success';
						}
					?>">
					<?
						if($this->session()->getUserLevel()  == 3) :
							$user = (new Session)->load($item->session_key);
					?>
					<td>
						<? print $user->name;?>
					</td>
					<? endif; ?>
					<td>
						<a href="/?q=experiment/view/<? print $item->id; ?>">
							<? print $item->title; ?>
						</a>
						<a href="/?q=experiment/edit/<? print $item->id; ?>" class="edit-button btn-edit btn-info btn btn-sm btn-default">
							<span class="glyphicon glyphicon-pencil"></span>
						</a>
						<a href="#" class="remove-button btn-edit btn-info btn btn-sm btn-default">
							<span class="glyphicon glyphicon-remove"></span>
						</a>
					</td>
					<td>
						<? if(!empty($item->DateStart_exp)) print System::dateformat($item->DateStart_exp); ?>
					</td>
					<td>
						<? if(!empty($item->DateEnd_exp)) print System::dateformat($item->DateEnd_exp); ?>
					</td>
				</tr>
			<? endforeach; ?>
		</table>
	<? endif; ?>
	<div class="row">
		<div class="col-md-12 text-right">
		</div>
	</div>
</div>
