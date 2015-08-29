<h1><? print $this->view->content->title; ?></h1>
<div class="col-md-6">
	<h3>Информация</h3>
	<table class="table">
		<tbody>
		<tr>
			<td>Имя устройства: </td>
			<td><? print $this->app->config['lab']['name']?></td>
		</tr>
		<? if(isset($this->view->ip_address)) : ?>
		<tr>
			<td>Сетевые интерфейсы: </td>
			<td><? echo 'eth0 : ' . (empty($this->view->ip_address) ? 'Адрес неизвестен' : $this->view->ip_address); ?></td>
		</tr>
		<? endif; ?>
		</tbody>
	</table>
</div>
<!--
<div class="col-md-6">
	<div class="well">
		<span class="pull-right label label-warning">Осталось 2 дн. 5:45</span>
		<h3 >
			<span class="label label-danger">
				<span class="glyphicon glyphicon-exclamation-sign"></span> Активное измерение
			</span>
		</h3>
		<div>
			Установка: <a href="?q=page/view/config.create">TP1D</a>
		</div>
		<span class="pull-right">Начало эксперимента: 5.04.2014 13:48</span>
		<br>
		<span class="pull-right">Плановое время окончания: 7.04.2014 19:33</span>


		<table class="table">
			<tbody>
			<tr>
				<td>{ФИО}</td>
				<td><a href="?q=page/view/experiment">{experiment name}</a></td>
			</tr>
			<tr>
				<td>{ФИО}</td>
				<td><a href="?q=page/view/experiment">{experiment name}</a></td>
			</tr>
			<tr>
				<td>{ФИО}</td>
				<td><a href="?q=page/view/experiment">{experiment name}</a></td>
			</tr>
			</tbody>
		</table>
	</div>
</div>
-->