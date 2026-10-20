<?php
// admin/logout.php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

logout_admin();
start_session();
flash('success', 'You have been logged out successfully.');
redirect('login.php');
