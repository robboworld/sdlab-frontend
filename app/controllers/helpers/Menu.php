<?

class Menu
{
	static function render(array $menu, $user_level = 0)
	{
		$menu_html = '';
		foreach($menu as $key => $item)
		{
			if($item['user_level'] == 0 || $item['user_level'] <= $user_level)
			{
				strpos($key, '#') === 0 ? $href = $key : $href = '?q='.$key;
				isset($item['id']) ? $id = $item['id'] : $id = '';
				if(isset($item['menu']))
				{
					$sub_menu_html = '<ul>'.self::render($item['menu'], $user_level).'</ul>';
					$menu_html .= '<li><a id="'.$id.'" href="'.$href.'">'.$item['title'].'</a>'.$sub_menu_html.'</li>';
				}
				else
				{
					$item_title = $item['title'];
					if(isset($item['icon']))
					{
						$item_title = '<span class="'.$item['icon'].'"></span><span class="hidden-xs">&nbsp;'.$item['title'].'</span>';
					}
					$menu_html .= '<li><a id="'.$id.'" href="'.$href.'">'.$item_title.'</a></li>';
				}
			}

		}
		return $menu_html;
	}

	/**
	 * @return mixed
	 * Заглушка для статического меню, в дальнейшем возможно стоит переработать.
	 */
	static function get()
	{
		$menu['page/view'] = array(
			'title' => 'Система',
			'icon' => 'glyphicon glyphicon-home',
			'user_level' => 0
		);
		/*
		$this->menu['sensors'] = array(
			'title' => 'Датчики',
			'glyphicon' => '',
			'user_level' => 1
		);
		*/
		$menu['experiment/view'] = array(
			'title' => 'Эксперименты',
			'icon' => 'glyphicon glyphicon-list',
			'user_level' => 1
		);

		/*
		$menu['page/view/journal'] = array(
			'title' => 'Журнал',
			'icon' => '',
			'user_level' => 1
		);

		$menu['page/view/graphs'] = array(
			'title' => 'Графики',
			'icon' => '',
			'user_level' => 1
		);
		*/

		$menu['page/view/help'] = array(
			'title' => 'Помощь',
			'icon' => 'glyphicon glyphicon-info-sign',
			'user_level' => 0
		);

		return $menu;
	}
}
