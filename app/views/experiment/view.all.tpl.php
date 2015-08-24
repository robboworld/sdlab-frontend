<div class="col-md-12">
	<h1>Все эксперименты</h1>
	<form id="sdform" method="post" action="?<? print $_SERVER['QUERY_STRING']?>" >
		<input type="hidden" name="force" value="0">
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
				<? if($this->session()->getUserLevel() == 3) :?>
				<td class="text-right">
					Действие
				</td>
				<? endif; ?>
			</tr>
			</thead>
			<tbody>
			<? foreach($this->view->content->list as $item) :?>
				<tr
					<?
						if(empty($item->DateEnd_exp) && !empty($item->DateStart_exp))
						{
							print 'class="warning"';
						}
						elseif (!empty($item->DateEnd_exp))
						{
							print 'class="success"';
						}
					?>>
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
						<? if(!empty($item->DateStart_exp)) print System::dateformat($item->DateStart_exp); ?>
					</td>
					<td>
						<? if(!empty($item->DateEnd_exp)) print System::dateformat($item->DateEnd_exp); ?>
					</td>
					<? if($this->session()->getUserLevel() == 3) :?>
					<td class="text-right">
						<button type="button" class="experiment-delete-btn btn btn-danger" data-experiment="<? echo (int)$item->id; ?>">Удалить</button>
					</td>
					<? endif; ?>
				</tr>
			<? endforeach; ?>
			</tbody>
		</table>
	<? endif; ?>
		<div class="sensors-list">
		</div>
		<div class="row">
			<? if($this->session()->getUserLevel() == 3) :?>
			<div class="col-md-2 text-left">
				<a href="javascript:void(0)" id="sensors-rescan" class="btn btn-primary">Обновить список датчиков</a>
			</div>
			<? endif; ?>
			<div class="col-md-<? echo (($this->session()->getUserLevel() == 3) ? '10' : '12');?> text-right">

				<a href="?q=experiment" class="btn btn-primary">Новый эксперимент</a>
			</div>
		</div>
	</form>
</div>
