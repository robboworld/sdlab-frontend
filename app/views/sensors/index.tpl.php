<div class="my-fluid-container">
	<div class="col-lg-3" id="sensors-list-bar">
		<h3>Список датчиков
			<!--
			<a href="#sensors-list-update" id="sensors-list-update" class="pull-right btn btn-sm btn-primary"><? echo L::REFRESH; ?></a>
			-->
		</h3>
		<div class="list-group" id="available-sensors">
		</div>
	</div>
	<div class="col-lg-12" id="workspace">
		<h3>Активные измерения</h3>
		<p>Информация о датчиках берется из API("Несуществующие" датчики добавлены руками).
			График строится по рандомным данным.
			<br>
			Для полноценной работы нужно чтобы был
			запущен процесс <code>/opt/sdlab/sdlab</code>
		</p>
		<div class="row">
			<div class="col-lg-12 well" id="sensors-workspace">
				<? print $this->view->content->sensors_list; ?>
			</div>
		</div>
	</div>

</div>