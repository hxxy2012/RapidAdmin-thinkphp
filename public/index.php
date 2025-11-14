<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2024 EnterprisePlus All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace think;

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
