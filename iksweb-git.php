<?php
/*
 * Plugin Name: Информер ветки git
 * Plugin URI: https://iksweb.ru/plugins/git/
 * Description: Плагин добавляет кнопку-информер в админ. панель в публичной части, с помощью которой можно узнать текущую ветку git, а так же изменить ее.
 * Author: IKSWEB
 * Author URI: https://iksweb.ru/plugins/git/
 * Copyright: IKSWEB
 * Version: 2.3
 * Tags: git, iksweb, branch, git branch, repository
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

class IKSWEB_GIT
{
	/** @var string The plugin version number */
	var $version = '2.3';

	/** @var array The plugin settings array */
	var $settings = array();

	var $gitInfo = array();

	/*
	* Запускаем
	*/
	function init ()
	{
		// Объединяем с другими плагинами IKSWEB
		global $IKSWEB, $APPLICATION;

		$IKSWEB[]=array(
			'PLUGIN'=>'iksweb-git',
			);

		$arParams = get_option('GIT_SETTINGS');

		$this->settings = array(
			'VERSIA'		=> $this->version,
			'NAME'			=> 'GIT',
			'TITLE'			=> 'Управление GIT | IKSWEB',
			'ACTIVE'		=> isset( $arParams['ACTIVE'] ) ?  'Y' : 'N',
			'SLUG'			=> 'iks-git',
			'SETTINGS_NAME' => 'GIT_SETTINGS',
			'SITE_CHARSET'	=> get_bloginfo( 'charset' ),
			'PLUGIN_URL'	=> plugins_url( 'iksweb-git' ),
			'PLUGIN_DIR'	=> plugin_dir_path( __FILE__ ),
			'LANG'			=> determine_locale(),
		);


		// Получаем текущие настройки
		$arParams = $this->settings;

		// Регистрируем меню и настройки
		add_action( 'admin_menu' , array( $this , 'RegisterMenu' ) );
		add_action( 'admin_init' , array( $this , 'RegisterSettings' ) );
		
		add_action( 'admin_enqueue_scripts' , array( $this , 'ShowHeadScripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this , 'ShowHeadScripts' ) );
		
		if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/.git')) {
			return false;
		}

		if (!function_exists('exec')) {
			return false;
		}

		if ($arParams['ACTIVE']=='Y') {

			// Добавляет ссылку в админ бар
			add_action( 'admin_bar_menu',  array( $this , 'git' ), 90 );

		}
		
	}

	/*
	* Основная логика компонента
	*/
	function git()
	{

		// Получаем текущие настройки
		$arParams = $this->settings;

		if(!is_user_logged_in()){
			return false;
		}

		$log = $branch = $status = $config = $branches = array();

		if (isset($_REQUEST['checkout']))
			$checkout = htmlspecialchars($_REQUEST['checkout']);

		if (!empty($checkout) && $br = $checkout) {
			exec("git branch", $branches);
			foreach ($branches as $branch) {
				$branch = trim($branch);
				if ($branch == $br) {
					exec('git checkout '.$branch);
				}
			}
		}

		// Запрашиваем информацию
		exec("git branch | grep \* | cut -d ' ' -f2", $branch);
		exec("git log", $log);
		exec("git status", $status);
		exec("git branch", $branches);
		exec("git config --list", $config);

		$this->gitInfo['LOGS']		= $log;
		$this->gitInfo['BRANCH']	= $branches;
		$this->gitInfo['STATUS']	= $status;
		$this->gitInfo['CONFIG']	= $config;

		$brText = $currentBr = current($branch);

		$arMenu = array();

		if (strpos(implode('', $status), 'nothing to commit') !== false) {

			if ($arParams['SITE_CHARSET'] == 'UTF-8') $brText .= ' &#10003;';

			$commit = true;

		} else {

			if ($arParams['SITE_CHARSET'] == 'UTF-8') $brText .= ' &#9888;';

			$commit = false;
		}

		// Проверяем есть или незакомиченные файлы
		if ($commit) {
			foreach ($branches as $branch) {
				$key = preg_replace('/[^a-zA-Z0-9\_\.\-]/ui', '',$branch);
				$branch = trim($branch);
				if($currentBr!=$key){
					$arMenu['BRANCH'][$key] = array(
						"TEXT" => $branch,
						"TITLE" => $this->GetMessage('perekl-vetk').$branch,
						"ACTION" => "/?checkout=".$key,
					);
				}
			}
		} else {
			$arMenu['BRANCH']['none'] = array(
				"TEXT" => $this->GetMessage('nelza-perekl-vetk'),
				"TITLE" => $this->GetMessage('est-nezakom-prav-detl'),
				"ACTION" => "/wp-admin/admin.php?page=iks-git#4",
			);
		}

		// Формируем меню
		global $wp_admin_bar;

		$wp_admin_bar->add_menu(array(
			'id'    => 'gitinfo',
			'title' => 'GIT ['.$brText.']',
			'href'  => "/wp-admin/admin.php?page=iks-git#4",
			'meta'   => array(
				'title' => $this->GetMessage('tekushaya-vetka').' '.$currentBr,
				'class'    => 'git-icon',
			),
		));

		// Список веток
		foreach($arMenu['BRANCH'] as $key=>$item){
			$wp_admin_bar->add_menu( array(
				'parent' => 'gitinfo',
				'id'	 => 'git-'.$key,
				'title'  => $item['TEXT'],
				'href'   => $item['ACTION'],
				'meta'   => array(
					'title'    => $item['TITLE'],
				),
			));
		}
	}

	/*
	* Подключаем JS и CSS к панели
	*/
	function ShowHeadScripts()
	{

		global $APPLICATION;

		// Получаем текущие настройки
		$arParams = $this->settings;
		
		// Для обычных гостей не грузим стили
		if(!is_user_logged_in()){
			return false;
		}
		
		if(!isset($APPLICATION)){
			wp_enqueue_script( 'tooltip', $arParams['PLUGIN_URL'].'/assets/js/bootstrap.tooltip.min.js', array(), $arParams['VERSIA'] , true );
			wp_enqueue_style('iksweb', $arParams['PLUGIN_URL'].'/assets/css/iksweb.css', array(), $arParams['VERSIA'] );
			wp_enqueue_script('iksweb', $arParams['PLUGIN_URL'].'/assets/js/iksweb.js', array(), $arParams['VERSIA'] , true );
		}

		wp_enqueue_style('iks-git', $arParams['PLUGIN_URL'].'/assets/css/style.css', array(), $arParams['VERSIA'] );
		wp_enqueue_script('iks-git', $arParams['PLUGIN_URL'].'/assets/js/script.js', array(), $arParams['VERSIA'] , true );
	}

	/*
	* Регистрируем меню
	*/
    function RegisterMenu()
    {
    	global $APPLICATION, $IKSUPDATE;

		// Получаем параметры
		$arParams = $this->settings;

    	// Если на сайте установлен главный модуль IKSWEB, то делаем подменю
		if(isset($APPLICATION)){
			add_menu_page( $APPLICATION->settings['PLUGIN']['TITLE'], $APPLICATION->settings['PLUGIN']['NAME'] , 'manage_options', $APPLICATION->settings['PLUGIN']['SLUG'] , array( $APPLICATION, 'ShowPageParams' ), '' , 60 );
			add_submenu_page( $APPLICATION->settings['PLUGIN']['SLUG'], $arParams['TITLE'], $arParams['NAME'], 'manage_options', $arParams['SLUG'], array( $this,'ShowPageGit') );
		}else{
			add_menu_page( $arParams['TITLE'], $arParams['NAME'], 'manage_options', $arParams['SLUG'] , array( $this, 'ShowPageGit' ), '' , 60 );

			if(!$IKSUPDATE){
				add_submenu_page( $arParams['SLUG'] , 'PRO версия', 'PRO версия', 'manage_options', 'iks-pro-git', array( $this , 'ShowPagePro' ));
			}
		}

	}

	/*
	* Отображение страницы параметров
	*/
	function ShowPageGit()
	{
		global $APPLICATION;

		// Получаем параметры
		$arParams = $this->settings;

		if(isset($APPLICATION)){
			$APPLICATION->ShowPageHeader();
		}else{
			$this->ShowPageHeader();
		}
		?>
		<div class="tabs">
			<ul class="adm-detail-tabs-block">
				<li class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last active" data-id="1">Общие настройки</li>
				<?php if ($arParams['ACTIVE']=='Y') {?>
				<li class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last" data-id="2">Логи</li>
				<li class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last" data-id="3">Ветки/Статус</li>
				<?php } ?>
			</ul>
			<form method="post" enctype="multipart/form-data" action="options.php">
				<?php
		    	$s_name = $arParams['SETTINGS_NAME'];
		    	settings_fields($s_name); ?>
				<div class="adm-detail-content-wrap active">
					<div class="adm-detail-content">
						<div class="adm-detail-title">Основные параметры</div>
						<div class="adm-detail-content-item-block">
							<table class="adm-detail-content-table edit-table">
								<tbody>
									<tr>
										<td class="adm-detail-content-cell-l">
											<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Позволяет включать и отключать работу отдельных компонентов.">
												<span class="type-3"></span>
											</div>
											Активность
										</td>
										<td class="adm-detail-content-cell-r">
											<input name="<?php echo $s_name?>[ACTIVE]" type="checkbox" <?php if($arParams['ACTIVE']=='Y'){ ?>checked<?php } ?>  value="Y" class="adm-designed-checkbox">
											<?php if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/.git')) { ?>
											<b>Плагин не будет работать, пока не выполнена установка GIT в дерикторию /.git</b>
											<?php } ?>
										</td>
									</tr>
									<?php if ($arParams['ACTIVE']!=='Y') {?>
									<tr class="heading">
										<td colspan="2">Инструкция по установке GIT</td>
									</tr>
									<tr>
										<td  colspan="2">

												<p>1. Подключаемся к вашему серверу по SSH и переходим в дерикторию сайта с помощью команды <b>cd</b> в нужный раздел вашего сайта</p>
												<code>cd /var/www/site</code>
												<p>Корневой адрес сайта можно определить с помощью команды <b>pwd</b>.</p>
												<br>
												<p>2. Производим установку (введите команду)</p>
												<code>git init</code>
												<br>
												<p>3.  С помощью команд ниже укажите имя пользователя и e-mail, которые будут отображаться в удаленном репозитории Github (в ветках при коммитах и т.д.). Замените имя_пользователя и test@example.com на нужные вам значения; они могут быть любыми.</p>
												<code>
													git config --global user.name "имя_пользователя"<br>
													git config --global user.email test@example.com
												</code>
												<br>
												<p>4. Проиндексируйте файлы, которые нужно отслеживать. (не забудьте перед этим добавить исключения в файл .gitignore)</p>
												<code>
													git add -A
												</code>
												<br>
												<p>5. Сделайте коммит, чтобы сохранить текущее состояние проекта в репозиторий:</p>
												<code>
													git commit -m 'first commit'
												</code>
												<br>
												<p>6. Подключите удалённый репозиторий, указав полученную ссылку командой:</p>
												<code>
													git remote add origin https://ссылка на ваш репозиторий.git
												</code>
												<br>
												<p>7. Теперь вы можете отправить локальную ветку master в ваш репозиторий, командой:</p>
												<code>
													git push -u origin master
												</code>
												<p><i>Если действие было выполнено успешно, вы увидите сообщение Branch 'master' set up to track remote branch 'master' from 'origin'.</i></p>
												<br>
												<p>Плагин автоматически проверит ветки и предоставит возможноть их менять.</p>
												<p><b>Вы не сможете переключить ветки GIT, пока правки в текущей ветке незакомичены.</b></p>
										</td>
									</tr>
									<?php } ?>
								</tbody>
							</table>

						</div>
						<div class="adm-detail-content-btns">
						    <input type="submit" name="submit" id="submit" class="iksweb-btn" value="Сохранить">
					    </div>
					</div>
				</div>

				<?php if ($arParams['ACTIVE']=='Y') {?>
				<div class="adm-detail-content-wrap">
					<div class="adm-detail-content">
						<div class="adm-detail-title">Логи коммитов GIT</div>
						<div class="adm-detail-content-item-block">
							<?php if($logs = $this->gitInfo['LOGS']){?>
							<textarea cols="50" rows="10" style="width:100%;height:600px;max-height:600px;padding:10px"><?php foreach($logs as $item){
								echo (empty($item))? "\n" : trim($item)."\n";
								} ?></textarea>
							<?php }else{ echo 'Логов не обнаружено';} ?>
						</div>
					</div>
				</div>
				<div class="adm-detail-content-wrap">
					<div class="adm-detail-content">
						<div class="adm-detail-title">Информация о GIT</div>
						<div class="adm-detail-content-item-block">
						<table class="adm-detail-content-table edit-table">
						<tbody>
							<tr class="heading">
								<td colspan="2">git branch</td>
							</tr>
							<tr>
								<td class="adm-detail-content-cell-l">
									<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Отображаются существующие ветки Git">
										<span class="type-3"></span>
									</div>
									Ветки GIT
								</td>
								<td class="adm-detail-content-cell-r">
									<?php if($branch = $this->gitInfo['BRANCH']){ ?>
										<?php
										foreach($branch as $item){
											echo $item.'<br>';
										}
										?>
									<?php }else{ echo 'Ветки GIT не обнаружены';} ?>
								</td>
							</tr>
							<tr class="heading">
								<td colspan="2">git status</td>
							</tr>
							<tr>
								<td class="adm-detail-content-cell-l">
									<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Отображаются текущий стату Git">
										<span class="type-3"></span>
									</div>
									Статус
								</td>
								<td class="adm-detail-content-cell-r">
									<?php if($status = $this->gitInfo['STATUS']){?>
										<textarea cols="50" rows="10" style="width:100%;height:200px;max-height:600px;padding:10px"><?php
										foreach($status as $item){
											echo $item."\n";
										}
										?></textarea>
									<?php }else{ echo 'Ветки GIT не обнаружены';} ?>
								</td>
							</tr>
							<tr class="heading">
								<td colspan="2">git config --list</td>
							</tr>
							<tr>
								<td class="adm-detail-content-cell-l">
									<div class="massage-page" data-toggle="factory-tooltip" data-placement="right" title="" data-original-title="Отображаются текущий стату Git">
										<span class="type-3"></span>
									</div>
									Конфигурация
								</td>
								<td class="adm-detail-content-cell-r">
									<?php if($config = $this->gitInfo['CONFIG']){?>
										<textarea cols="50" rows="10" style="width:100%;height:200px;max-height:600px;padding:10px"><?
										foreach($config as $item){
											echo $item."\n";
										}
										?></textarea>
									<?php }else{ echo 'Произошла ошибка при получение конфигурации.';} ?>
								</td>
							</tr>
						</tbody>
						</table>

						</div>
					</div>
				</div>
				<?php } ?>
			</form>
		</div>

		<?php
		$this->ShowPageFooter();
	}

	/*
	 * Отображение страницу покупки PRO версии для FREE
	*/
	public function ShowPagePro()
	{
		$this->ShowPageHeader();
		?>
		<div class="tabs">
			<ul class="adm-detail-tabs-block">
				<li class="adm-detail-tab adm-detail-tab-active adm-detail-tab-last active">IKSWEB</li>
			</ul>
			<div class="adm-detail-content-wrap active">
					<div class="adm-detail-content">
						<div class="adm-detail-title">Обновить плагин до PRO</div>
						<div class="adm-detail-content-item-block">
							<p>Если вам понравилась работа нашего плагина, вы можете приобрести PRO версию и получать уникальные обновления.</p>
							<h2>Что же вы получите в PRO версии?</h2>
							<ul>
								<li><span class="dashicons dashicons-saved"></span> Первоклассную поддержку</li>
								<li><span class="dashicons dashicons-saved"></span> Расширенный набор функций</li>
								<li><span class="dashicons dashicons-saved"></span> Бесплатные обновления</li>
							</ul>
							<br>
							<a target="_blank"  href="//iksweb.ru/plugins/git/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" class="iksweb-btn">Подробнее о PRO версии</a>

							<br><br><br>
							
							<h2>Помочь развитию проекта</h2>
							<p>Наш проект нуждается в вашей помощи. На разработку и поддержание плагинов уходит много средств и сил. Мы будем рады любой помощи.</p>
							
							<iframe src="https://yoomoney.ru/quickpay/shop-widget?writer=seller&targets=%D0%A1%D0%B1%D0%BE%D1%80%20%D1%81%D1%80%D0%B5%D0%B4%D1%81%D1%82%D0%B2%20%D0%BD%D0%B0%20%D0%BE%D0%B1%D0%BD%D0%BE%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5%20%D0%BF%D0%BB%D0%B0%D0%B3%D0%B8%D0%BD%D0%BE%D0%B2&targets-hint=&default-sum=100&button-text=14&payment-type-choice=on&mobile-payment-type-choice=on&comment=on&hint=&successURL=https%3A%2F%2Fplugin.iksweb.ru%2Fwordpress%2F&quickpay=shop&account=4100116825216739" width="100%" height="303" frameborder="0" allowtransparency="true" scrolling="no"></iframe>
						</div>
					</div>
				</div>
		</div>
		<?php
		$this->ShowPageFooter();
	}

	/*
	* Регистрируем настройки
	*/
	function RegisterSettings()
	{
		// Получаем параметры
		$arParams=$this->settings;

		/* настраиваемые пользователем значения */
    add_option( $arParams['SETTINGS_NAME'] , '');

		/* настраиваемая пользователем проверка значений общедоступных статических функций */
		register_setting($arParams['SETTINGS_NAME'], $arParams['SETTINGS_NAME'] , array( $this , 'CheckSettings' ));
	}

	/*
	* Проверка правильности вводимых полей
	*/
	function CheckSettings($input)
	{
		return $input;
	}

	/*
	 *  Выводи оповещение об обновление настроек
	 *  notice-success - для успешных операций. Зеленая полоска слева.
	 *	notice-error - для ошибок. Красная полоска слева.
	 *	notice-warning - для предупреждений. Оранжевая полоска слева.
	 *	notice-info - для информации. Синяя полоска слева.
	 */
	function ShowNotices($massage=false, $type='notice-success')
	{
		if($massage!==false){
		?>
		<div class="notice <?php echo $type?> is-dismissible">
			<p><?php echo $massage?></p>
		</div>
		<?php
		}
	}

	/*
	*  Вывод текста для разных языков
	*/
	function GetMessage($name, $aReplace = null)
	{
		
		global $MESS;
		
		$arParams = $this->settings;
	
		// Подключаем язык
		if ($arParams['LANG'] == 'ru_RU') {
			require_once($arParams['PLUGIN_DIR'].'lang/ru_RU.php');
		} else {
			require_once($arParams['PLUGIN_DIR'].'lang/en_EN.php');
		}

	    if (isset($MESS[$name]))
	    {
	        $s = $MESS[$name];

	        if ($aReplace !== null && is_array($aReplace))
	        {
	            foreach($aReplace as $search => $replace)
	            {
	                $s = str_replace($search, $replace, $s);
	            }
	        }

	        return $s;
	    }
	}

	/*
	 * Шапка для всех страниц
	*/
	public function ShowPageHeader($title=false)
	{
		global $APPLICATION;
	?>
	<div class="wrap iks-wrap">
		<h1 class="wp-heading-inline"><?php echo !empty($title)? $title : 'Настройки модуля' ;?></h1>
		<?php
		if(!empty($_REQUEST['settings-updated'])){
			$this->ShowNotices('Настройки компонента сохранены.');
		}

		if(!isset($APPLICATION)){
			$this->ShowNotices('Рекомендуем установить главный модуль плагина от разработчика IKSWEB. Главный модуль позволит подключить reCaptha, собирать данные из форм и производит транслитерацию URL, а также улучшит внешний вид панели. Вы можете попробовать бесплатную версию по ссылке - <b><a href="//plugin.iksweb.ru/wordpress/wordpress-start/" target="_blank">Попробовать</a></b> и если вам понравится, то сможете приобрести платную версию с увеличенным функционалом и постоянными обновлениями.','notice-info');
		}

	}

	/*
	* Подвал для всех страниц
	*/
	public function ShowPageFooter()
	{?>
		<div class="footer-page">
				<div class="iksweb-box">
					<ul>
						<li><span class="type-1"></span> - Нейтральная настройка, которая не может нанести вред вашему сайту.</li>
						<li><span class="type-2"></span> - При включении этой настройки, вы должны быть осторожны. Некоторые плагины и темы могут зависеть от этой функции.</li>
		        <li><span class="type-3"></span> - Абсолютно безопасная настройка, рекомендуем использовать.</li>
						<li>----------</li>
						<li>Наведите указатель мыши на значок, чтобы получить справку по выбранной функции.</li>
		      </ul>
				</div>
				<div class="iksweb-box">
					<p><b>Вы хотите, чтобы плагин улучшался и обновлялся?</b></p>
					<p>Помогите нам, оставьте отзыв на wordpress.org. Благодаря отзывам, мы будем знать, что плагин действительно полезен для вас и необходим.</p>
					<p style="margin: 9px 0;">А также напишите свои идеи о том, как расширить или улучшить плагин.</p>
					<div class="vote-me">
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<a href="//wordpress.org/plugins/iksweb-git/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank"><strong>Оставить отзыв или поделиться идеей</strong></a>

					<p style="margin: 5px 0 0 0; font-weight: bold; color: #d63638;">Хотите поддержать плагин? - <a href="//iksweb.ru/payment/" target="_blank">Пожертвовать</a></p>
				</div>
				<div class="iksweb-box">
					<p><b>Возникли проблемы?</b></p>
					<p>Мы предоставляем платную и бесплатную поддержку для наших <a href="//iksweb.ru/plugins/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank">плагинов</a>. Если вас столкнули с проблемой, просто создайте новый тикет. Мы обязательно вам поможем!</p>
					<p><span class="dashicons dashicons-sos" style="margin: -4px 5px 0 0;"></span> <a href="//iksweb.ru/plugins/support/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank">Получите поддержку</a></p>
					<div style="margin: 15px 0 10px;background: #fff4f1;padding: 10px;color: #a58074;">
						<span class="dashicons dashicons-warning" style="margin: -4px 5px 0 0;"></span> Если вы обнаружите ошибку php или уязвимость в плагине, вы можете <a href="//iksweb.ru/plugins/support/?utm_content=pligin&utm_medium=wp&utm_source=<?php echo $_SERVER['SERVER_NAME'];?>&utm_campaign=plugin" target="_blank">создать тикет</a> в поддержке, на который мы ответим мгновенно.
					</div>
				</div>
			</div>
	<?php
	}

}

// initialize
if( !isset($IKSWEB_GIT) ) {
	$IKSWEB_GIT = new IKSWEB_GIT();
	$IKSWEB_GIT->init();
}
?>