// Переменные для отправки файла и формы для получения данных
let formData;
let formDataGet;

// Кнопка отправки файла на сервер. Вызов функции отправки файла.
$('#sendFileBtn').on('click', function(){
	let getForm = document.querySelector('#sendFile');
	formData = new FormData(getForm);
	event.preventDefault();
	sendFile();
});

// Кнопка отправки запроса на выгрузку данных из БД
$('#sendDataBtn').on('click', function () {
	let sendForm = document.querySelector('#sendData');
	formDataGet = new FormData(sendForm);
	event.preventDefault();
	sendData();
})

// Функция для отправки файла на сервер
function sendFile() {
	$.ajax({
		url: '/test-ag/main.php',
		method: 'POST',
		contentType: false,
		processData: false,
		dataType: 'json',
		data: formData,
		success: function(response) {
			if (response.result == true) 
			{
				$('.message').html('<div>' + response.data + '</div');
			}
			else if (response.result == false)
			{
				$('.message').html('<div>' + response.errors + '</div');
			}
		},
		error: function(response) {
			$('.message').html('<div> Не удалось отправить запрос </div>');
		}
	})
};

// Функция для отправки запроса на выгрузку данных из БД
function sendData() {
	$.ajax({
			url: '/test-ag/data.php',
			method: 'POST',
			contentType: false,
			processData: false,
			dataType: 'json',
			data: formDataGet,
			success: function(responses) {
				if (responses.result == true)
				{
					$('.resultData').html(`${responses.data}`);
				}
				else if (responses.result == false) 
				{
					$('.resultData').html('<div>' + responses.errors + '</div>');
				}
			},
			error: function(responses) {
				$('.resultData').html('<div> Не удалось отправить запрос </div>');
			}
	})
}