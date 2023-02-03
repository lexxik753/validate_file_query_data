<?php
	$errors = []; //Массив для сообщений об ошибках
	$data = []; // Массив для сообщений с полезной нагрузкой
	// Переменные для передачи данных в БД
	$family_name = "";
	$name = "";
	$parent_name = "";
	$gender = "";
	$date_of_birth = "";
	$age = "";
	// 

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

	// Задаем параметры проверки файла на корректность передаваемого файла и принимаем файл со стороны клиента
	$allowed = ['csv'];
	$get_file = $_FILES['user_file']['name'];

			$find = explode('.', $get_file);
			$ext = end($find);
	
			if (in_array($ext, $allowed)) 
			{
				$data[] = "Допустимый формат файла!";
			}
			else 
			{
				$errors[] = "Недопустимый формат файла!";
				goto EXIT_POINT;
			};

	// Работа с файлом CSV.
	// Функция для валидации дат.
	function validateDate($date, $format = 'Y-m-d H:i:s')
	{
	    $d = DateTime::createFromFormat($format, $date);
	    return $d && $d->format($format) == $date;
	};

	// Читаем файл построчно и выполняем валидацию файла
	$arr = [];
	$arr2 = [];

	$open_file = fopen($get_file, "r");
	while ($to_parse = fgetcsv($open_file, 200, ';')) {
		for ($i=0; $i < count($arr); $i++) { 
			if (mb_strlen($to_parse[0]) < 100 && mb_strlen($to_parse[1]) < 100 && mb_strlen($to_parse[2]) < 100) 
			{
				// echo "Ok";
			}
			else 
			{
				$errors[] = "error on line: {$to_parse[$i]}";
				goto EXIT_POINT;
			};
			if ($to_parse[3] == 'Мужчина' || $to_parse[3] == 'Женщина')
			{
				// echo "Ok";
			}
				
			else 
			{
				$errors[] = "error on line: {$to_parse[$i]}";
				goto EXIT_POINT;
			};
			if (validateDate($to_parse[4], 'd.m.Y'))
			{
				// echo "Ok";
			}
			else
			{
				$errors[] = "error on line: {$to_parse[$i]}";
				goto EXIT_POINT;
			};
			if ($to_parse[5] <= 130 && $to_parse[5] > 0)
			{
				// echo "Ok";
			}
			else
			{
				$errors[] = "error on line: {$to_parse[$i]}";
				goto EXIT_POINT;
			};
			if (mb_strlen($to_parse[6]) < 100) {
				// echo "Ok";
			}
			else
			{
				$errors[] = "error on line: {$to_parse[$i]}";
				goto EXIT_POINT;
			}
		};
		$arr[] = $to_parse;
		$arr2[] = $to_parse[6];
	};

	fclose($open_file);

	// Очищаем таблицы people и dict_country перед заливкой данных в базу
	$clear_table_people = $connect_db->prepare("
		DELETE FROM `people`
	");
	$clear_table_dict = $connect_db->prepare("
		DELETE FROM `dict_country`
	");

	$clear_table_people->execute();
	$clear_table_dict->execute();

	//Подготовка запроса в таблицу dict_country
	$sql_ins_dict = $connect_db->prepare("
		INSERT INTO dict_country (name) VALUES (:name)
	");
	$sql_ins_dict->bindParam(':name', $name);

	// Пробегаем массив циклом и на каждом шаге отправляем значение в базу. Поле ID заполняется автоматически и соответсвует каждой строке из файла
	for ($k = 0; $k < count($arr2); $k++) {
		$name = $arr2[$k];
		$sql_ins_dict->execute();
	};

	//Подготовка запроса в таблицу people
	$sql_ins_people = $connect_db->prepare("
		INSERT INTO people (family_name, name, parent_name, gender, date_of_birth, age, id_dict_country) VALUES (:family_name, :name, :parent_name, :gender, :date_of_birth, :age, (SELECT dict_country.id 
			FROM dict_country 
			WHERE dict_country.name = :id_dict_country))
	");

	$sql_ins_people->bindParam(':family_name', $family_name);
	$sql_ins_people->bindParam(':name', $name);
	$sql_ins_people->bindParam(':parent_name', $parent_name);
	$sql_ins_people->bindParam(':gender', $gender);
	$sql_ins_people->bindParam(':date_of_birth', $date_of_birth);
	$sql_ins_people->bindParam(':age', $age);
	$sql_ins_people->bindParam(':id_dict_country', $id_dict_country);
	
	foreach ($arr as $key => $value) {
		$family_name = $value[0];
		$name = $value[1];
		$parent_name = $value[2];
		$gender = $value[3] == 'Мужчина' ? $value[3] = 1 : $value[3] = 2;
		$form_date = new DateTime($value[4]);
		$date_of_birth = date_format($form_date, 'Y-m-d');
		$age = $value[5];
		$id_dict_country = $value[6];
		$sql_ins_people->execute();
	};
	
	$data = 'Файл успешно загружен';
	

	EXIT_POINT: //Точка выхода (формируем ответ сервера, передаем заголовки и ответ на клиент)
	$response = [
        'result' => (empty($errors) ? true : false),
        'data' => $data, //сюда пишем полезную нагрузку ответа
        'errors' => $errors,
  	];
	
	header('Content-type: application/json; charset=utf-8');
	header('Content-language: ru');
	echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); // Массив $response преобразуется в JSON стандартными средствами PHP
?>