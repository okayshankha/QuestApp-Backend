

Setup:
 - php artisan migrate
 - php artisan passport:install
 - php artisan passport:client --personal
 - Then type "QuestApp" without quotes & hit enter
 - php artisan migrate --path=/database/migrations/2020_06_14_180532_create_departments_table.php



 issue: 
 1. When a teacher is invited to a space, he should be automatically invited to all the spaces.
