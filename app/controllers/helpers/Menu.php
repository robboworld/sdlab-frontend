<?php
/**
 * Class Menu 
 * 
 * Store and render menu items
 */
class Menu
{
	public static function render(array $menu, $user_level = 0)
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
					$menu_html .= '<li><a id="'.$id.'" href="'.$href.'">'.htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8').'</a>'.$sub_menu_html.'</li>';
				}
				else
				{
					$item_title = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
					if(isset($item['icon']))
					{
						$item_title = '<span class="'.$item['icon'].'"></span><span class="hidden-xs">&nbsp;'.htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8').'</span>';
					}
					$menu_html .= '<li><a id="'.$id.'" href="'.$href.'">'.$item_title.'</a></li>';
				}
			}

		}
		return $menu_html;
	}

	/**
	 * Stub for static menu.
	 * 
	 * todo: refactor, create dinamic stored in database menu
	 * 
	 * @return mixed
	 * 
	 */
	public static function get()
	{
		$menu['page/view'] = array(
			'title' => L::SYSTEM,
			'icon' => 'glyphicon glyphicon-home',
			'user_level' => 0
		);
		/*
		$this->menu['sensors'] = array(
			'title' => L::SENSORS,
			'glyphicon' => '',
			'user_level' => 1
		);
		*/
		$menu['experiment/view'] = array(
			'title' => L::EXPERIMENTS,
			'icon' => 'glyphicon glyphicon-list',
			'user_level' => 1
		);

		/*
		$menu['page/view/journal'] = array(
			'title' => L::JOURNAL,
			'icon' => '',
			'user_level' => 1
		);

		$menu['page/view/graphs'] = array(
			'title' => L::GRAPHS,
			'icon' => '',
			'user_level' => 1
		);
		*/

		$menu['page/view/help'] = array(
			'title' => L::HELP,
			'icon' => 'glyphicon glyphicon-info-sign',
			'user_level' => 0
		);

		return $menu;
	}
}
