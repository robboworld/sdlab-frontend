<? 
// todo: can edit and delete access check, edit only for admin or owner
$show_action = true;
?>
<div class="col-md-12">
	<div class="row">
		<div class="col-md-6">
			<h1>Все эксперименты</h1>
		</div>
	</div>
	<div>
		<a href="?q=experiment" class="btn btn-primary"><span class="glyphicon glyphicon-plus">&nbsp;</span>Новый эксперимент</a>
	</div>
	<form id="sdform" method="post" action="?<? print $_SERVER['QUERY_STRING']?>" >
		<input type="hidden" name="force" value="0"/>
	<? if(isset($this->view->content->list )) : ?>
		<table class="table">
			<thead>
			<tr>
				<? if($this->session()->getUserLevel() == 3) :?>
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
				<? /*if($this->session()->getUserLevel() == 3) :*/?>
				<td class="text-right">
					Действие
				</td>
				<? /*endif;*/ ?>
			</tr>
			</thead>
			<tbody>
			<? foreach($this->view->content->list as $item) :?>
				<tr class="row-experiment 
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
						if($this->session()->getUserLevel() == 3) :
							$user = (new Session)->load($item->session_key);
					?>
					<td>
						<? print htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');?>
					</td>
					<? endif; ?>
					<td>
						<a href="/?q=experiment/view/<? print $item->id; ?>">
							<? print htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>
						</a>
					</td>
					<td>
						<? if(!empty($item->DateStart_exp)) print System::dateformat('@'.$item->DateStart_exp); ?>
					</td>
					<td>
						<? if(!empty($item->DateEnd_exp)) print System::dateformat('@'.$item->DateEnd_exp); ?>
					</td>
					
					<td class="text-right">
						<a href="/?q=experiment/edit/<? print $item->id; ?>" class="btn btn-sm btn-default btn-info"><span class="glyphicon glyphicon-pencil"></span></a>
						<? if($this->session()->getUserLevel() == 3) :?>
						<button type="button" class="experiment-delete-btn btn btn-sm btn-danger" data-experiment="<? echo (int)$item->id; ?>"><span class="glyphicon glyphicon-remove"></span></button>
						<? endif; ?>
					</td>
				</tr>
			<? endforeach; ?>
			</tbody>
		</table>
	<? endif; ?>
		<div class="sensors-list">
		</div>
		<div class="row">
			<? if($this->session()->getUserLevel() == 3) :?>
			<div class="col-md-4 text-left">
				<a href="javascript:void(0)" id="sensors-rescan" class="btn btn-primary">Обновить список датчиков</a>
			</div>
			<? endif; ?>
		</div>
	</form>
</div>