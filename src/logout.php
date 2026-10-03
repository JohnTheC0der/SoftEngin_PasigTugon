<?php
session_start();
session_unset();
session_destroy();
header('Location: ../public/Admin_Log_In.html');
exit;
