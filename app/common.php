<?php
// 应用公共文件
function returnJson($result)
{
    json($result)->send();
    exit;
}
