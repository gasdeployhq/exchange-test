# Exchange Rates Project

Проект для получения курсов валют с ЦБ РФ, реализованный на Laravel 10 и PHP 8.3 с использованием Docker и Redis.


### Шаги установки

1. Клонируйте репозиторий:

    ```bash
    git clone https://github.com/gasdeployhq/exchange-test.git
    cd exchange-test
    ```

2. Создайте `.env` файл на основе `.env.example` и настройте его:

    ```bash
    cp .env.example .env
    ```

3. Установите зависимости:

    ```bash
    docker-compose up
    docker-compose exec app composer install
    ```
   
4. Запустите воркер:

    ```bash
    docker-compose exec app php artisan queue:work
    ```   

5. Для запуска консольной команды сборки данных за 180 дней:

    ```bash
    docker-compose exec app php artisan fetch:exchange-rates
    ```


6. Для запуска консольной команды сборки данных за 180 дней:

    ```bash
    docker-compose exec app php artisan exchange:fetch 2024-11-07 USD RUB
    ```

7. Для запуска тестов

    ```bash
    docker-compose exec app php artisan test
    ```

