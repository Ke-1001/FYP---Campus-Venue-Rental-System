<?php
// This section prepares the user logout page.
session_start();
session_unset();
session_destroy();
header("Location: homepage.php");
exit();
