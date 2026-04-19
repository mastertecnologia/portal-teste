<?php
 2 /**
 3  * Configuração LOCAL do SERVIDOR – criar no servidor como app_local.php
 4  *
 5  * No servidor de produção (Windows/XAMPP), copie este arquivo para:
 6  *   config/app_local.php
 7  *
 8  * Use os valores reais (salt, senha do banco, senha do e-mail).
 9  * O arquivo app_local.php está no .gitignore e não deve ser versionado.
10  *
11  * Isso evita depender das variáveis SetEnv do Apache (que às vezes não
12  * chegam ao PHP no XAMPP/Windows).
13  */
14 return [
15     'debug' => true,
16
17     'App' => [
18         'base' => '/portal',
19     ],
20
21     'Security' => [
22         'salt' => '1b84cb295d0bcdd9db2508f5d2c36c01adb86eebc1ecb43795ea318100d3cded',
23     ],
24
25     'Datasources' => [
26         'default' => [
27             'host' => '10.0.2.23',
28             'port' => '5432',
29             'username' => 'postgres',
30             'password' => 'pgm@postgres',
31             'database' => 'pgm',
32         ],
33     ],
34
35     'EmailTransport' => [
36         'master' => [
37             'className' => 'Smtp',
38             'host' => 'mail.pgm.inf.br',
39             'port' => 587,
40             'username' => 'helpdesk@pgm.inf.br',
41             'password' => '}1e$9t-5',
42             'tls' => false,
43             'client' => null,
44         ],
45         'pgm' => [
46             'className' => 'Smtp',
47             'host' => 'mail.pgm.inf.br',
48             'port' => 587,
49             'username' => 'helpdesk@pgm.inf.br',
50             'password' => '}1e$9t-5',
51             'tls' => false,
52             'client' => null,
53         ],
54     ],
55 ];
