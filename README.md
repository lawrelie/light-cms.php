# light-cms.php
CMS (PHP>=8.0)

```php
include 'path/to/light-cms.php';
$cms = new Lawrelie\LightCms\Cms([
    'db' => new PDO($dsn, options: [PDO::ATTR_PERSISTENT => true]),
    'index' => ['id' => '', 'name' => 'サイト名', 'description' => 'サイト説明', 'order' => 'DESC', 'orderby' => 'update'],
    'locale' => 'ja-JP',
    'query' => '',
    'tag' => ['id' => '_tag', 'name' => 'タグ', 'order' => 'ASC', 'orderby' => 'name'],
    'timezone' => timezone_open('Asia/Tokyo'),
]);
```
