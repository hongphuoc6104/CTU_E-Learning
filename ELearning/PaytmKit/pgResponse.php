<?php

http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "Legacy Paytm callback endpoint is disabled in this project.";
exit;
