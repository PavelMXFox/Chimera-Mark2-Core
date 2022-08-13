## V.1.0.0
1.  Add disabled flag for createLeftPanel and crealeTabsPanel
2.  Remove context menu title if empty title
3.  onEnter for fieldGroup
4.  Поиск по группам пользователей
5.  Поиск по пользователям
6.  onContextMenu для поля UI.fieldAdd(type=label) 
7.  Кликабельность поля типа "label" для UI.fieldAdd 
8.  Исправлена ошибка загрузки модуля в Firefox
9.  Исправлена ошибка /users/x
10. Исправлена Ошибка создания форм с полем passwordNew
11. Исправлено Изменение хэша при изменение URL oAuth
12. Установка модулей и fox-start.d
13. Скрывать меню при обновлении 
14. Добавить подпись Powered by chimera fox как было на mark1
15. Добавить заглушку на случай если браузер не поддерживается
17. Установщик пакетов с модулями
18. Экранирование параметра id в baseClass::__construct() @type=string|int
19. added searchResult class, updated system to use it in search
20. Implemented fox\modules::getByFeature and fox\moduleInfo::getByFeature
21. added UI.collectForm().getVals() method
22. Updated session/modules logic
23. Added chars and length props for passwordNew field, added string ref type for smartClick
24. Added Filrebase/jwt into composer.json, added id for tabs-anchor
25. Added API.session.checkAccess(rule, module)
26. added qGetSql() and qGetSqlSelectTemplate()
27. Updated UI panels
28. Implemented usage of user session config if exists
29. Added public function isMember(user $user)
30. Added href (smartClick) type into UI.addField()
31. Added click-protection for foxClick: click blocked if selection not empty
32. table.datatable td(th).icon - icon size reduced
33. Updated Breadcrumbs for use objects
34. Added attrs.disabled translation into item for UI.fieldAdd
35. Implemented companies management
36. Added onClick and onContextMenu for fieldBlock, added .onEnter ext.
37. Added async key into api.exec and disabled flag into LR-tabs
38. API.session.checkAccess fix
39. Added tabs manipulation methods
40. Switched common::getGUIDc to UUID:v4
41. Added UI.getClipboard(callback) function
42. Added onFail callback into UI.getClipboard
43. Iplemented ref.onContextMenu for adField with type label and href
44. Added API.loadModule method
45. Blanker z-index changed
46. added baseClass->search page and pagesize type conversion
47. Added marker into s3client->listobjects method, added listAllObjects and added forced garbage collection
48. Added $__foxRequestInstance
49. Added count into default search, fixed foxpager
50. added UI.stamp2isodateq and UI.stamp2isodatens funcitons
51. Added attrs into accordion panel
52. Updated xSearch to extend orderBy
53. Webhook implemented
54. fox\request @property-read $rawRequestBody
55. multipart cache
(Last commit processed #6b3a7f420d)




## V.0.9.0 RC
### Бэк
1. Огранизация прозрачного REST API через интерфейс fox\externalCallable - позволяет с минимальными затратами но при этом со всеми необходимыми проверками организовать доступ к нужным сущностям.
2. Поддержка сторонних модулей с возможностью установки нескольких инстансов одного модуля (если поддерживается модулем).
3. Аутентификация пользователей — как с помощью встроенных средств так и с помощью внешних источников oAuth (на данный момент реализована поддержка Gitlab, Gitea, Yandex, VK).
4. Авторизация пользователей — с помощью встроенной системы контроля доступа, основанной на ролях производится гибкая проверка наличия у пользователя прав доступа. Списки ролей пользователя проверяются как бэком при выполнении вызовов REST API, так и фронтом — для этого после аутентификации передается список прав доступа текущего пользователя.
5. Возможность выполнения запросов без авторизации, если такое необходимо. Например для реализации Вебхуки.
6. Встроенные миграции БД на основе описания структуры класса.
7. Базовый класс для выполнения стандартных функции работы с БД (запись, чтение, поиск, удаление) на основе которого можно быстро создавать сущности.
8. Поддержка memcached как для встроенных объектов так и для пользовательских для быстрого доступа к часто используемым данным
9. Хранилище конфигурации для каждого модуля в БД (с кэшем) так и глобальной в окружении контейнера.
10. Встроенный функционал регистрации пользователей по приглашениям с возможность автоматического добавления в группы как для предоставления доступа так и для организации в логические группы («списки»).
11. Встроенный функционал кодов подтверждения действий как для встроенных функций (восстановление пароля, подтверждение почты итд) так и для пользовательских модулей.
12. Поддержка управления S3-совместимым хранилищем (создание букетов, удаление данных итд).
13. Возможность добавления любых других объектных хранилищ на основе типового интерфейса.
14. Хранение файлов (вложений электронной почты, вложений в servicedesk, шаблонов и результатов отчетов итд) в S3-совместимом объектном хранилище
15. Встроенная поддержка форматов OpenDocument ODS и ODT для автоматизированного формирования документов (отчеты, счета, акты итд)
16. Встроенная поддержка Fox Converter для экспорта документов в любом офисном формате.
17. Встроенная поддержка на уровне базового класса обмена данными в формате JSON с возможность контроля видимости отдельных свойств и создания виртуальных свойств.
18. Встроенный eMail клиент, поддерживающий одновременную работу как на получение так и на отправку писем в том чисте с вложениями с нескольких учетных записей по протоколам IMAP и SMTP (с авторизацией и без).
19. Хранение метаданных модулей (например, последняя синхронизация итд) во встроенном хранилище с кешированием.
20. Поддержка получения и формирования данных в формате Prometheus
21. Встроенный REST API Client для связи со сторонними системами.
22. Встроенные интерфейсы stringExportable && stringImportable для автоматической конвертации объектов в строку и обратно (например время в unixTime или ISO).
23. Встроенный модуль шифрования критиченых данных (например, паролей) а так же формирования хэшей на основе индивидуального мастер-пароля.
24. Система формирования уникальных идентификаторов в формате 1000-0000-00 с контрольной суммой для формирования реестра документов, инвентаризации и других объектов. Например для организации глобального поиска (в будущих релизах) и штрих-кодов.
25. Встроенный cron, позволяющий запускать периодические процессы модулей параллельно с ограничением по максимальному времени выполнения и с возможностью блокировки повторного запуска, пока не завершится прошлый процесс отдельно для каждой задачи.
26. Поддержка нескольких языков, например для отправки уведомлений на электронную почту или мессенджеры. Список языков может различаться для Chimera Core и дополнительных модулей.
27. Логирование всех действий пользователей.

### Фронт
1. Встроенные методы взаимодействия по REST как с собственным бэком, так и с другими службами.
2. Формирование визуального базового интерфейса пользователя на нескольких языках
3. Базовые функции администрирования системы
4. Авторизациия, регистрации пользователей. Контроль сессий
5. Встроенная библиотека Fox UI для формирования меню, диалогов, кнопок, форм включая формы автоматической генерации паролей и автозаполнения.
6. Библиотека проверки прав доступа для контроля за отображением элементов интерфейса.
7. Возможность кастомизации интерфейса в помощью создания собственных цветовых тем и изображений.
8. Использование FontAwesome 5 для формирования интерфейсов, так же возможно добавление собственных шрифтов с иконками.