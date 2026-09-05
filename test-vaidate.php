<?php

$path = sys_get_temp_dir().'/test-validate-'.uniqid();
mkdir($path.'/lang/en', 0755, true);
mkdir($path.'/lang/ar', 0755, true);
file_put_contents($path.'/lang/en/auth.php', '<?php return ["login" => "Login"];');
file_put_contents($path.'/lang/ar/auth.php', '<?php return ["login" => "تسجيل"];');

echo 'path: '.$path.PHP_EOL;
echo 'en content: ';
print_r(include $path.'/lang/en/auth.php');
echo 'ar content: ';
print_r(include $path.'/lang/ar/auth.php');
