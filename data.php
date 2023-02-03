<?php
	$errors = []; //Массив для сообщений об ошибках
	$data = []; // Массив для сообщений с полезной нагрузкой

	// Реквизиты подключения к БД
	$wgDBtype = "mysql";
	$wgDBserver = "127.0.0.1";
	$wgDBname = "test_ag";
	$wgDBuser = "ag";
	$wgDBpassword = "ag";

	// Подключение к БД
	try {
		$connect_db = new PDO("$wgDBtype:host=$wgDBserver;dbname=$wgDBname", $wgDBuser, $wgDBpassword);
	} catch (PDOException $e) {
		$errors[] = 'Не получилось установить соединение с БД';
		goto EXIT_POINT;
	};

	//… основной код скрипта …

	// Принимаем параметры со стороны клиента
	$get_age_with = (empty($_POST['age_with']) ? 1 : $_POST['age_with']);
	$get_age_on = (empty($_POST['age_on']) ? 130 : $_POST['age_on']);
	$get_change_data = ($_POST['change_data']) == 'easy' ? 1 : 2;
	$get_citizenship = $_POST['citizenship'];

	if (!($get_age_with > 0 && $get_age_with <= 130) || !($get_age_on > 0 && $get_age_on <= 130))
	{
		$errors[] = 'Некорректно заполнено поле возраста';
		goto EXIT_POINT;
	};

	if ($get_age_with <= $get_age_on)
	{
		$get_age_with;
		$get_age_on;
	}
	else
	{
		$errors[] = 'Некорректно указан возраст';
		goto EXIT_POINT;
	};

	if (count($get_citizenship) == 1) 
	{
		$get_citizenship_one = $get_citizenship[0];
	}
	else if (count($get_citizenship) == 2) 
	{
		$get_citizenship_one = $get_citizenship[0];
		$get_citizenship_two = $get_citizenship[1];
	}

	else 
	{
		$errors[] = 'Превышено допустимое количество выбранных стран';
		goto EXIT_POINT;
	};

	// Подготовка запроса для выгрузки по всем полям с селектом одной страны
	$sql_query_one_countr = $connect_db->prepare("
		SELECT people.family_name, people.name, people.parent_name, people.gender, people.date_of_birth, people.age, dict_country.name as countr_name
		FROM people
		LEFT JOIN dict_country ON dict_country.id = people.id_dict_country
		WHERE (people.age >= :age_with AND people.age <= :age_on) AND (dict_country.name = :citizenship_one)
	");

	// Подготовка запроса для выгрузки по всем полям с селектом двух стран
	$sql_query_select_all = $connect_db->prepare("
		SELECT people.family_name, people.name, people.parent_name, people.gender, people.date_of_birth, people.age, dict_country.name as countr_name
		FROM people
		LEFT JOIN dict_country ON dict_country.id = people.id_dict_country
		WHERE (people.age >= :age_with AND people.age <= :age_on) AND (dict_country.name = :citizenship_one OR dict_country.name = :citizenship_two)
	");	

	// Подготовка запроса для группировки полей
	$sql_query_group = $connect_db->prepare("
		SELECT dict_country.name, COUNT(people.id) AS all_people
		FROM people
		LEFT JOIN dict_country ON dict_country.id = people.id_dict_country
		GROUP BY dict_country.name
	");


	if ($get_change_data == 1 && count($get_citizenship) == 1) 
	{
		$sql_query_one_countr->execute(['age_with'=>$get_age_with, 'age_on'=>$get_age_on, 'citizenship_one'=>$get_citizenship_one]);
		$to_table = $sql_query_one_countr->fetchAll(PDO::FETCH_ASSOC);
		create_table_all($to_table);
		$data = create_table_all($to_table);
	}
	else if ($get_change_data == 1 && count($get_citizenship) == 2)
	{
		$sql_query_select_all->execute(['age_with'=>$get_age_with, 'age_on'=>$get_age_on, 'citizenship_one'=>$get_citizenship_one, 'citizenship_two'=>$get_citizenship_two]);
		$to_table = $sql_query_select_all->fetchAll(PDO::FETCH_ASSOC);
		$data = create_table_all($to_table);
	}
	else 
	{
		$sql_query_group->execute();
		$to_table_group = $sql_query_group->fetchAll(PDO::FETCH_ASSOC);
		$data = create_table_group($to_table_group);
	};

	// Функция для создания таблицы полной выгрузки
	function create_table_all($arr) {
		// Формируем шапку таблицы для полной выгрузки
		$table_all_head = "<table>
			<thead>
				<tr>
					<th> Фамилия </th>
					<th> Имя </th>
					<th> Отчество </th>
					<th> Пол </th>
					<th> Дата рождения </th>
					<th> Возраст </th>
					<th> Страна </th>
				</tr>
			</thead>
			<tbody>";
			// Формируем тело таблицы и передаем данные выгрузки (таблица полной выгрузки)
		$table_all_body = '';
		foreach ($arr as $value) {
			$table_all_body .= "
				<tr>
					<td>{$value['family_name']}</td>
					<td>{$value['name']}</td>
					<td>{$value['parent_name']}</td>
					<td>{$value['gender']}</td>
					<td>{$value['date_of_birth']}</td>
					<td>{$value['age']}</td>
					<td>{$value['countr_name']}</td>
				</tr>";
	};
		$table_all_body .= "</tbody></table>";	
		$table_all_head .= $table_all_body;
		return $table_all_head;
	};

	// Функция для создания таблицы сгруппированной выгрузки
	function create_table_group($arr) {
		// Формируем шапку таблицы для сгруппированной выгрузки
		$table_group_head = "<table>
			<thead>
				<tr>
					<th> Страна </th>
					<th> Количество людей </th>
				</tr>
			</thead>
			<tbody>"; 
		
		// Формируем тело таблицы и передаем данные выгрузки (таблица сгруппированной выгрузки)
		$table_group_body = '';
		foreach ($arr as $value) {
			$table_group_body .= "
				<tr>
					<td>{$value['name']}</td>
					<td>{$value['all_people']}</td>
				</tr>
			";
		};
		$table_group_body .= "</tbody></table>";	
		$table_group_head .= $table_group_body;
		return $table_group_head;
	};

	EXIT_POINT: //Точка выхода (формируем ответ сервера, передаем заголовки и ответ на клиент)
	$responses = [
        'result' => (empty($errors) ? true : false),
        'data' => $data, //сюда пишем полезную нагрузку ответа
        'errors' => $errors,
  	];
	
	header('Content-type: application/json; charset=utf-8');
	header('Content-language: ru');
	echo json_encode($responses, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); // Массив $response преобразуется в JSON стандартными средствами PHP
?>